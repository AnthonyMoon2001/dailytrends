<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\FeedResponseDto;
use App\Entity\News;
use App\Queries\ListQuery;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class FeedService
{
    public function __construct(private ListQuery $listQuery) {}

    /** @return array */
    public function list(): array
    {
        $rows = $this->listQuery->execute();

        $items = array_map(fn(News $n) => $this->mapToDto($n), $rows);

        return [
            'items' => $items,
        ];
    }

    /** @return array */
    public function get(int $id): array
    {
        $n = $this->listQuery->findOneById($id);
        if (!$n) {
            return ['ERROR' => 'No existe ninuna noticia con esa id'];
        }
        return ['item' => $this->mapToDto($n)];
    }

    private function mapToDto(News $n): FeedResponseDto
    {
        return new FeedResponseDto(
            id: (int) $n->getId(),
            title: $n->getTitle(),
            url: $n->getUrl(),
            image: $n->getImage(),
            publishedAt: $n->getPublishedAt()?->format(\DateTimeInterface::ATOM),
            source: $n->getSource(),
            createdAt: $n->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $n->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
