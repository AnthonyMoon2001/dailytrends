<?php

namespace App\Tests\Service;

use App\Repository\FeedsRepository;
use App\Service\FeedService;
use App\Tests\DatabaseTestCase;

final class FeedServiceTest extends DatabaseTestCase
{
    public function testCreateAndDuplicate(): void
    {
        $svc = new FeedService(
            static::getContainer()->get(FeedsRepository::class)
        );

        $r1 = $svc->create("T", "https://x.t", null, null, "foo");
        $this->assertArrayHasKey("item", $r1);

        $r2 = $svc->create("T2", "https://x.t", null, null, "foo");
        $this->assertEquals(["El feed ya existe para ese source+url"], $r2);
    }
}
