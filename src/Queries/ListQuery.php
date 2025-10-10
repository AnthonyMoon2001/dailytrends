<?php

declare(strict_types=1);

namespace App\Queries;

use App\Entity\News;
use App\Repository\NewsRepository;

final class ListQuery
{
    public function __construct(private NewsRepository $repo) {}

    public function execute(): array
    {
        return $this->repo->createQueryBuilder('n')
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneById(int $id): ?News
    {
        return $this->repo->find($id);
    }
}
