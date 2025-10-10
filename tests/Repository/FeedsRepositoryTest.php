<?php

namespace App\Tests\Repository;

use App\Entity\Feeds;
use App\Repository\FeedsRepository;
use App\Tests\DatabaseTestCase;

final class FeedsRepositoryTest extends DatabaseTestCase
{
    public function testUpsertManyInsertsAndUpdates(): void
    {
        $repo = static::getContainer()->get(FeedsRepository::class);
        $now = new \DateTimeImmutable(
            "now",
            new \DateTimeZone("Europe/Madrid")
        );

        $batch = [
            [
                "title" => "A",
                "url" => "https://a.t/1",
                "image" => null,
                "publishedAt" => $now,
                "source" => "elpais",
            ],
            [
                "title" => "B",
                "url" => "https://a.t/2",
                "image" => null,
                "publishedAt" => $now,
                "source" => "elpais",
            ],
        ];
        $r1 = $repo->upsertMany($batch);
        $this->assertSame(
            ["inserted" => 2, "updated" => 0, "errors" => 0],
            $r1
        );

        $batch[0]["title"] = "A updated";
        $r2 = $repo->upsertMany($batch);
        $this->assertSame(
            ["inserted" => 0, "updated" => 2, "errors" => 0],
            $r2
        );

        $all = $repo->findAll();
        $this->assertCount(2, $all);
    }

    public function testDeleteById(): void
    {
        $repo = static::getContainer()->get(FeedsRepository::class);
        $em = static::getContainer()
            ->get("doctrine")
            ->getManager();

        $f = new Feeds("T", "https://x.t", null, null, "elmundo");
        $em->persist($f);
        $em->flush();

        $this->assertSame(1, $repo->deleteById($f->getId()));
        $this->assertSame(0, $repo->deleteById($f->getId()));
    }
}
