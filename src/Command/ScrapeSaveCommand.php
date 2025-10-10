<?php

declare(strict_types=1);

namespace App\Command;

use App\Scraper\ScrapeAndSaveTopFeeds;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[
    AsCommand(
        name: "app:scrape:save",
        description: "Obtiene y guarda en MySQL las 5 noticias"
    )
]
final class ScrapeSaveCommand extends Command
{
    public function __construct(private ScrapeAndSaveTopFeeds $useCase)
    {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);

        $summary = $this->useCase->run();

        if (empty($summary)) {
            $io->warning('No hay scrapers con la tag "app.scraper"');
        }

        foreach ($summary as $source => $r) {
            $io->writeln(
                sprintf(
                    "%s => inserted: %d, updated: %d, errors: %d",
                    $source,
                    $r["inserted"],
                    $r["updated"],
                    $r["errors"]
                )
            );
        }

        $io->success("Done");
        return Command::SUCCESS;
    }
}
