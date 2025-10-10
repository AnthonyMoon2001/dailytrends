<?php

declare(strict_types=1);

namespace App\Queries;

use App\Entity\Feeds;
use App\Repository\FeedsRepository;

final class ListQuery
{
    public function __construct(private FeedsRepository $repo) {}

    public function execute(): array
    {
        return $this->repo->createQueryBuilder('n')
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneById(int $id): ?Feeds
    {
        return $this->repo->find($id);
    }
}
