<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\FeedResponseDto;
use App\Entity\News;
use App\Query\News\ListQuery;

final class FeedService
{
    public function __construct(private ListQuery $listQuery) {}

    public function list(): array
    {
        $rows = $this->listQuery->allOrderedByCreatedAtDesc();

        $items = array_map(function (News $item): FeedResponseDto {
            return new FeedResponseDto(
                id: (int) $item->getId(),
                title: $item->getTitle(),
                url: $item->getUrl(),
                image: $item->getImage(),
                publishedAt: $item
                    ->getPublishedAt()
                    ?->format(\DateTimeInterface::ATOM),
                source: $item->getSource(),
                createdAt: $item
                    ->getCreatedAt()
                    ->format(\DateTimeInterface::ATOM),
                updatedAt: $item
                    ->getUpdatedAt()
                    ->format(\DateTimeInterface::ATOM)
            );
        }, $rows);

        return ["items" => $items];
    }
}
