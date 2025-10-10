<?php

namespace App\Tests\Scraper;

use App\Scraper\ElPaisScraper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ElPaisScraperTest extends TestCase
{
    public function testTopParsesUpToFiveItems(): void
    {
        $html =
            "" .
            '<article><a href="/2025/10/10/espana/foo.html">Titular 1</a><img src="https://img.t/i1.jpg"></article>' .
            '<article><a href="/2025/10/10/espana/bar.html">Titular 2</a></article>' .
            '<article><a href="/2025/10/10/espana/baz.html">Titular 3</a></article>';

        $client = new MockHttpClient(new MockResponse($html));
        $s = new ElPaisScraper($client);

        $items = $s->top();

        $this->assertNotEmpty($items);
        $this->assertLessThanOrEqual(5, count($items));
        $this->assertSame("elpais", $s->sourceKey());
        $this->assertArrayHasKey("title", $items[0]);
        $this->assertArrayHasKey("url", $items[0]);
        $this->assertArrayHasKey("source", $items[0]);
    }
}
