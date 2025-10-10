<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\News;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

final class NewsRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private LoggerInterface $logger
    ) {
        parent::__construct($registry, News::class);
    }

    public function findOneBySourceAndUrl(string $source, string $url): ?News
    {
        return $this->findOneBy(["source" => $source, "url" => $url]);
    }

    /** @return array{inserted:int,updated:int,errors:int} */
    public function upsertMany(array $items): array
    {
        $em = $this->getEntityManager();
        $inserted = $updated = $errors = 0;

        $em->beginTransaction();
        try {
            foreach ($items as $it) {
                try {
                    $source = (string) ($it["source"] ?? "");
                    $url = (string) ($it["url"] ?? "");
                    $title = (string) ($it["title"] ?? "");
                    if ($source === "" || $url === "" || $title === "") {
                        $errors++;
                        continue;
                    }

                    $existing = $this->findOneBySourceAndUrl($source, $url);
                    if ($existing) {
                        $existing->updateFrom(
                            $title,
                            $it["image"] ?? null,
                            $it["publishedAt"] ?? null
                        );
                        $updated++;
                    } else {
                        $news = new News(
                            $title,
                            $url,
                            $it["image"] ?? null,
                            $it["publishedAt"] ?? null,
                            $source
                        );
                        $em->persist($news);
                        $inserted++;
                    }
                } catch (\Throwable $e) {
                    $errors++;
                    $this->logger->warning("News upsert error", [
                        "exception" => $e,
                        "item" => $it,
                    ]);
                }
            }
            $em->flush();
            $em->commit();
        } catch (\Throwable $e) {
            $em->rollback();
            $this->logger->error("News upsert transaction failed", [
                "exception" => $e,
            ]);
            throw $e;
        }

        return compact("inserted", "updated", "errors");
    }
}
