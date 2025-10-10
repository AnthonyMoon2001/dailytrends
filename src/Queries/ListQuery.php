<?php

declare(strict_types=1);

namespace App\Query\News;

use App\Entity\News;
use App\Repository\NewsRepository;

final class ListQuery
{
    public function __construct(private NewsRepository $repo) {}

    public function allOrderedByCreatedAtDesc(): array
    {
        return $this->repo
            ->createQueryBuilder("n")
            ->orderBy("n.createdAt", "DESC")
            ->getQuery()
            ->getResult();
    }
}
