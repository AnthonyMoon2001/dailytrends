<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\FeedResponseDto;
use App\Entity\Feeds;
use App\Repository\FeedsRepository;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class FeedService
{
    public function __construct(private FeedsRepository $repository) {}

    /** @return array */
    public function list(): array
    {
        $rows = $this->repository
            ->createQueryBuilder("n")
            ->orderBy("n.createdAt", "DESC")
            ->getQuery()
            ->getResult();

        $items = array_map(fn(Feeds $n) => $this->mapToDto($n), $rows);

        return [
            "items" => $items,
        ];
    }

    /** @return array */
    public function get(int $id): array
    {
        $n = $this->repository->find($id);
        if (!$n) {
            return ["ERROR" => "No existe ninuna noticia con esa id"];
        }
        return ["item" => $this->mapToDto($n)];
    }

    private function mapToDto(Feeds $n): FeedResponseDto
    {
        return new FeedResponseDto(
            id: (int) $n->getId(),
            title: $n->getTitle(),
            url: $n->getUrl(),
            image: $n->getImage(),
            publishedAt: $n
                ->getPublishedAt()
                ?->format(\DateTimeInterface::ATOM),
            source: $n->getSource(),
            createdAt: $n->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $n->getUpdatedAt()->format(\DateTimeInterface::ATOM)
        );
    }

    /** @return array */
    public function delete(int $id): array
    {
        $affected = $this->repository->deleteById($id);
        if (!$affected) {
            return ["ERROR" => "No existe ninuna noticia con esa id"];
        }
        return ["OK" => "Ha sido eliminada correectamente"];
    }

    /** @return array */
    public function create(
        string $title,
        string $url,
        ?string $image,
        ?\DateTimeImmutable $publishedAt,
        string $source,
    ): array {
        $normUrl = mb_substr(trim($url), 0, 1024);
        $normImage = $image ? mb_substr(trim($image), 0, 1024) : null;
        $normTitle = mb_substr(trim($title), 0, 255);
        $normSource = mb_substr(trim($source), 0, 50);

        $urlHash = hash("sha256", $normUrl);

        $existing = $this->repository->findOneBy([
            "source" => $normSource,
            "urlHash" => $urlHash,
        ]);
        if ($existing) {
            return ["El feed ya existe para ese source+url"];
        }

        $feeds = new Feeds(
            $normTitle,
            $normUrl,
            $normImage,
            $publishedAt,
            $normSource
        );
        $this->repository->save($feeds);

        return ["item" => $this->mapToDto($feeds), "OK" => "Se ha creado correctamente"];
    }
}
