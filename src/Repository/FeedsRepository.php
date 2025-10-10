<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Feeds;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

final class FeedsRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private LoggerInterface $logger
    ) {
        parent::__construct($registry, Feeds::class);
    }

    public function findOneBySourceAndUrl(string $source, string $url): ?Feeds
    {
        return $this->findOneBy(["source" => $source, "url" => $url]);
    }

    /** @return array{inserted:int,updated:int,errors:int} */
    public function upsertMany(array $items): array
    {
        $em = $this->getEntityManager();
        $inserted = 0;
        $updated = 0;
        $errors = 0;
        $em->beginTransaction();
        try {
            foreach ($items as $i) {
                $normUrl = mb_substr((string) $i["url"], 0, 1024);
                $normImage =
                    isset($i["image"]) && $i["image"] !== null
                    ? mb_substr((string) $i["image"], 0, 1024)
                    : null;
                $urlHash = hash("sha256", $normUrl);

                $feeds = $this->findOneBy([
                    "source" => (string) $i["source"],
                    "urlHash" => $urlHash,
                ]);

                if ($feeds) {
                    $feeds->updateFrom(
                        (string) $i["title"],
                        $normImage,
                        $i["publishedAt"] instanceof \DateTimeImmutable
                            ? $i["publishedAt"]
                            : null
                    );
                    $updated++;
                } else {
                    $feeds = new Feeds(
                        (string) $i["title"],
                        $normUrl,
                        $normImage,
                        $i["publishedAt"] instanceof \DateTimeImmutable
                            ? $i["publishedAt"]
                            : null,
                        (string) $i["source"]
                    );
                    $em->persist($feeds);
                    $inserted++;
                }
            }

            $em->flush();
            $em->commit();
        } catch (\Throwable $e) {
            $em->rollback();
            $errors = count($items);
            $inserted = 0;
            $updated = 0;
        }

        return [
            "inserted" => $inserted,
            "updated" => $updated,
            "errors" => $errors,
        ];
    }

    public function deleteById(int $id): int
    {
        return $this->createQueryBuilder("n")
            ->delete()
            ->where("n.id = :id")
            ->setParameter("id", $id)
            ->getQuery()
            ->execute();
    }

    public function save(Feeds $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }
}
