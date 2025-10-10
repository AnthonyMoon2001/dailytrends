<?php

declare(strict_types=1);

namespace App\DTO;

final class FeedResponseDto
{
    public function __construct(
        public int $id,
        public string $title,
        public string $url,
        public ?string $image,
        public ?string $publishedAt,
        public string $source,
        public string $createdAt,
        public string $updatedAt
    ) {}
}
