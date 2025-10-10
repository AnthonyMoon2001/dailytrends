<?php

namespace App\Scraper;

interface ScraperInterface
{
    public function top(): array;
    public function sourceKey(): string;
}
