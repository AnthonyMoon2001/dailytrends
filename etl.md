# ETL — DailyTrends (El País + El Mundo, sin RSS)

Este documento describe **qué hace el proyecto**, **cómo está organizado** y **cómo ejecutarlo**: desde la **extracción** (scrapers), la **transformación** (limpieza/normalización) hasta la **carga** en **MySQL** (upsert idempotente). Incluye el porqué del `url_hash`, configuración de **Docker**, **comandos** y **solución de problemas**.

---


## 1) Estructura de carpetas (resumen)

```
src/
 ├─ Command/
 │   ├─ ScrapePreviewCommand.php   # muestra por consola los 5 titulares de cada medio
 │   └─ ScrapeSaveCommand.php      # ejecuta la ETL y guarda en BD
 ├─ Entity/
 │   └─ News.php                   # entidad persistente con UNIQUE(source, url_hash)
 ├─ Repository/
 │   └─ NewsRepository.php         # upsertMany: insert/update por (source, url_hash)
 └─ Scraper/
     ├─ ScraperInterface.php       # contrato común polimórfico
     ├─ HtmlUtils.php              # utilidades (sanitizeUrl, absolutize, parseSrcset, tidy)
     ├─ ElPaisScraper.php          # scraper específico de El País
     └─ ElMundoScraper.php         # scraper específico de El Mundo
```

---

## 2) ETL (Extract → Transform → Load)

### 2.1 Extract
- Peticiones HTTP a las **portadas** de cada medio.
- Selección de titulares mediante CSS:
  - `article h1 a[href], article h2 a[href], article h3 a[href]`
  - Se **evitan enlaces a comentarios** (ej. textos como “123 comentarios”).

### 2.2 Transform
- **Título**: limpieza (`HtmlUtils::tidy`).
- **URL**: convertir a **absoluta** (`absolutize`) y **normalizar** (`sanitizeUrl`) quitando parámetros de tracking (`utm_*`, `gclid`, `fbclid`, etc.).
- **Imagen**: desde la **card** de portada (sin segunda petición a la noticia):
- **Fecha**: se asigna **“hoy”** en zona **Europe/Madrid** (sin depender del detalle del artículo).

### 2.3 Load (upsert idempotente)
- **Clave técnica**: `(source, url_hash)` con `url_hash = sha256(url_normalizada)`.
- **UNIQUE (source, url_hash)** en la tabla `news`:
  - Si existe → **update** de `title`, `image`, `publishedAt`.
  - Si no existe → **insert**.
- **Transacción** por lote + **logs** de errores por ítem.

---

## 3) Por qué `url_hash`

- En MySQL con `utf8mb4`, un `UNIQUE(source, url)` cuando `url` es `VARCHAR(1024)` puede superar el **límite de índice (3072 bytes)** 
- Con `url_hash = sha256(url_normalizada)` usamos `UNIQUE(source, url_hash)`:
  - **Idempotencia** fiable (no duplica).
  - **Rendimiento** (índice pequeño).
  - **Evita duplicados** causados por parámetros de tracking.
- Se guarda **también** la **URL completa** (para mostrarla). El hash es **solo técnico**.

Esquema resumido de `news`:
```sql
id INT AUTO_INCREMENT PRIMARY KEY,
title VARCHAR(255) NOT NULL,
url VARCHAR(1024) NOT NULL,
url_hash VARCHAR(64) NOT NULL,             
image VARCHAR(1024) DEFAULT NULL,
published_at DATETIME DEFAULT NULL,      
source VARCHAR(50) NOT NULL,
created_at DATETIME NOT NULL,
updated_at DATETIME NOT NULL,
UNIQUE KEY uniq_source_urlhash (source, url_hash)
```

---

## 4) Scrapers (polimorfismo)

### 4.1 `ScraperInterface`
```php
interface ScraperInterface
{
    /** Devuelve SIEMPRE las 5 noticias del medio (title, url, image?, publishedAt?, source). */
    public function top(): array;

    /** Identificador corto del medio: ej. "elpais", "elmundo". */
    public function sourceKey(): string;
}
```

### 4.2 `ElPaisScraper`
- Fuente: `https://elpais.com/`
- Selección de titulares desde `article h1/h2/h3 a[href]`.
- Evita falsos positivos: enlace debe **parecer de artículo** (fecha en ruta o `.html`).
- Imagen desde la card; se filtran **placeholders** (logos, SVGs, “E”).
- `publishedAt` = hoy (Europe/Madrid).

### 4.3 `ElMundoScraper`
- Fuente: `https://www.elmundo.es/`
- Selección igual que arriba y **se evita “comentarios”**.
- Imagen desde la card: si hay `srcset`, se elige la **mayor** (p.ej. `500w`).
- `publishedAt` = hoy (Europe/Madrid).

### 4.4 `HtmlUtils` (utilidades clave)
- `tidy(string|null): ?string` → limpia espacios y entidades de títulos/textos.
- `absolutize(string $href, string $base): string` → convierte `href` relativo en **absoluto**.
- `parseSrcset(?string): ?string` → extrae una URL válida de un `srcset`.
- `sanitizeUrl(string): string` → **normaliza** URLs (elimina tracking) → base del hash estable.

---

## 5) Persistencia

### 5.1 Entidad `News`
- Campos: `id`, `title`, `url`, `url_hash`, `image`, `publishedAt`, `source`, `createdAt`, `updatedAt`.
- En el **constructor**:
  - Recorta longitudes máximas seguras.
  - Asigna zona `Europe/Madrid`.
  - Calcula `url_hash = sha256(url_normalizada)`.

### 5.2 Repositorio `NewsRepository`
- `upsertMany(array $items): array{inserted:int,updated:int,errors:int}`:
  1) Valida mínimos (`source`, `url`, `title`).
  2) Normaliza URL y calcula **hash**.
  3) Busca por `(source, url_hash)`.
  4) `update` si existe o `insert` si no.
  5) `flush()` al final y **transacción** por lote.
  6) **Logs** de errores por ítem y de transacción.

---

## 6) Servicio de aplicación

`App\Service\ScrapeAndSaveTopNews`  
- Recibe `iterable<ScraperInterface>` gracias a la **tag** `app.scraper` (DI).  
- Ejecuta `top()` de cada scraper y delega en el repositorio `upsertMany()`.  
- Devuelve un **resumen** por medio: `inserted/updated/errors`.

---

## 7) Comandos

### 7.1 Ejecutar ETL y guardar en BD
```bash
docker compose exec php php bin/console app:scrape:save
# Salida esperada, p. ej.:
# elpais  => inserted: 5, updated: 0, errors: 0
# elmundo => inserted: 5, updated: 0, errors: 0
```

---

## 8) Configuración de servicios / DI

**Etiquetar** los scrapers para el `TaggedIterator` del servicio:

```yaml
# config/services.yaml (fragmento)
services:
  _defaults:
    autowire: true
    autoconfigure: true

  App\:
    resource: '../src/'
    exclude:
      - '../src/Entity/'
      - '../src/Kernel.php'

  App\Scraper\ElPaisScraper:
    tags: ['app.scraper']

  App\Scraper\ElMundoScraper:
    tags: ['app.scraper']
```

*(Alternativa: en cada clase de scraper, usar atributo `#[AutoconfigureTag('app.scraper')]`.)*

---

## 9) Docker / Base de datos (MySQL)

### 9.1 `.env.local`
Ejemplo para conectar al servicio `db` del `docker-compose`:
```env
DATABASE_URL="mysql://root:TU_PASSWORD@db:3306/dailytrends?serverVersion=8.0&charset=utf8mb4"
```

### 9.2 Arrancar y migrar
```bash
docker compose up -d --wait
docker compose exec php php bin/console make:migration
docker compose exec php php bin/console doctrine:migrations:migrate -n
docker compose exec php php bin/console doctrine:schema:validate
```

---
