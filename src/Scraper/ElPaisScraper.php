<?php

namespace App\Scraper;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.scraper')]
final class ElPaisScraper implements ScraperInterface
{
    private const BASE = "https://elpais.com";
    private const LIMIT = 5;

    public function __construct(private HttpClientInterface $http) {}

    public function sourceKey(): string
    {
        return "elpais";
    }

    public function top(): array
    {
        $html = $this->http
            ->request("GET", self::BASE . "/", [
                "headers" => [
                    "user-agent" => "NewsTop5Bot/1.0",
                    "accept-language" => "es-ES,es;q=0.9",
                ],
                "timeout" => 12,
            ])
            ->getContent();

        $c = new Crawler($html);
        $items = [];
        $seen = [];
        $now = new \DateTimeImmutable(
            "now",
            new \DateTimeZone("Europe/Madrid")
        );

        foreach (
            $c->filter("article a[href], h1 a[href], h2 a[href], h3 a[href]")
            as $node
        ) {
            if (count($items) >= self::LIMIT) {
                break;
            }

            $a = new Crawler($node);
            $title = HtmlUtils::tidy($a->text(""));
            if (!$title) {
                continue;
            }

            $href = (string) ($a->attr("href") ?? "");
            $url = HtmlUtils::sanitizeUrl(
                HtmlUtils::absolutize($href, self::BASE)
            );

            $u = strtolower($url);
            if (
                !(
                    preg_match("#/20\d{2}/\d{2}/\d{2}/#", $u) ||
                    str_ends_with($u, ".html")
                )
            ) {
                continue;
            }

            $normUrl = mb_substr($url, 0, 1024);
            $key     = hash('sha256', $normUrl);

            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $image = $this->imageFromCard($a, self::BASE, true);

            $items[] = [
                "title" => $title,
                "url" => $url,
                "image" => $image,
                "publishedAt" => $now,
                "source" => $this->sourceKey(),
            ];
        }

        return $items;
    }

    /**
     * Extrae la imagen cercana al enlace dentro del contenedor de tarjeta (article/section/div/li).
     * Lee srcset/data-srcset/src/data-src y absolutiza. Opcionalmente filtra placeholders típicos de El País.
     */
    private function imageFromCard(
        Crawler $a,
        string $base,
        bool $filterPlaceholders = false
    ): ?string {
        $card = $a
            ->ancestors()
            ->filter("article, section, li, div")
            ->first();
        if (!$card->count()) {
            return null;
        }

        $n = $card->filter(
            "picture source[srcset], img[data-srcset], img[srcset]"
        );
        $src = null;
        if ($n->count()) {
            $src = HtmlUtils::parseSrcset(
                $n->first()->attr("srcset") ?? $n->first()->attr("data-srcset")
            );
        }
        if (!$src) {
            $m = $card->filter("img[data-src], img[src]");
            if ($m->count()) {
                $src =
                    $m->first()->attr("data-src") ?? $m->first()->attr("src");
            }
        }
        if (!$src) {
            return null;
        }

        if (!str_starts_with($src, "http")) {
            $src = HtmlUtils::absolutize($src, $base);
        }
        $src = HtmlUtils::sanitizeUrl($src);

        if ($filterPlaceholders) {
            $u = strtolower($src);
            if (
                str_contains($u, "favicon") ||
                str_contains($u, "/iconos/") ||
                str_contains($u, "/recorte/0x0/") ||
                str_ends_with($u, ".svg") ||
                preg_match("#/(logo|logotipo|default)#", $u)
            ) {
                return null;
            }
        }
        return $src;
    }
}
