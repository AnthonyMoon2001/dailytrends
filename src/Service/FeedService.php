<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\FeedResponseDto;
use App\Entity\Feeds;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Repository\FeedsRepository;

final class FeedService
{
    public function __construct(private FeedsRepository $repository,) {}

    /** @return array */
    public function list(): array
    {
        $rows = $this->repository->createQueryBuilder('n')
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $items = array_map(fn(Feeds $n) => $this->mapToDto($n), $rows);

        return [
            'items' => $items,
        ];
    }

    /** @return array */
    public function get(int $id): array
    {
        $n = $this->repository->find($id);
        if (!$n) {
            return ['ERROR' => 'No existe ninuna noticia con esa id'];
        }
        return ['item' => $this->mapToDto($n)];
    }

    private function mapToDto(Feeds $n): FeedResponseDto
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

    public function delete(int $id): array
    {
        $affected = $this->repository->deleteById($id);
        if (!$affected) {
            return ['ERROR' => 'No existe ninuna noticia con esa id'];
        }
        return ['OK' => 'Ha sido eliminada correectamente'];
    }
}
