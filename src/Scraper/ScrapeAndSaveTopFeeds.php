<?php

namespace App\Scraper;

use App\Repository\FeedsRepository;
use App\Scraper\ScraperInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

final class ScrapeAndSaveTopFeeds
{
    /** @param iterable<ScraperInterface> $scrapers */
    public function __construct(
        #[TaggedIterator('app.scraper')] private iterable $scrapers,
        private FeedsRepository $repo,
        private LoggerInterface $logger,
    ) {}

    /** @return array<string,array{inserted:int,updated:int,errors:int}> */
    public function run(): array
    {
        $summary = [];
        foreach ($this->scrapers as $scraper) {
            try {
                $items = $scraper->top();
                $summary[$scraper->sourceKey()] = $this->repo->upsertMany($items);
            } catch (\Throwable $e) {
                $this->logger->error('Scraper failed', [
                    'source' => $scraper->sourceKey(),
                    'exception' => $e
                ]);
                $summary[$scraper->sourceKey()] = ['inserted' => 0, 'updated' => 0, 'errors' => 5];
            }
        }
        return $summary;
    }
}
