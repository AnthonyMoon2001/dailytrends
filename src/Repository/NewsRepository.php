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
        $inserted = 0;
        $updated = 0;
        $errors = 0;
        $em->beginTransaction();
        try {
            foreach ($items as $i) {

                $normUrl   = mb_substr((string)$i['url'], 0, 1024);
                $normImage = isset($i['image']) && $i['image'] !== null
                    ? mb_substr((string)$i['image'], 0, 1024)
                    : null;
                $urlHash   = hash('sha256', $normUrl);

                $news = $this->findOneBy([
                    'source'  => (string)$i['source'],
                    'urlHash' => $urlHash,
                ]);

                if ($news) {
                    $news->updateFrom(
                        (string)$i['title'],
                        $normImage,
                        $i['publishedAt'] instanceof \DateTimeImmutable ? $i['publishedAt'] : null
                    );
                    $updated++;
                } else {
                    $news = new News(
                        (string)$i['title'],
                        $normUrl,
                        $normImage,
                        $i['publishedAt'] instanceof \DateTimeImmutable ? $i['publishedAt'] : null,
                        (string)$i['source']
                    );
                    $em->persist($news);
                    $inserted++;
                }
            }

            $em->flush();
            $em->commit();
        } catch (\Throwable $e) {
            dd($e);
            $em->rollback();
            $errors = count($items);
            $inserted = 0;
            $updated  = 0;
        }

        return ['inserted' => $inserted, 'updated' => $updated, 'errors' => $errors];
    }
}
