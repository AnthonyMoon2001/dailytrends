<?php

namespace App\Tests\Scraper;

use App\Scraper\ElMundoScraper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ElMundoScraperTest extends TestCase
{
    public function testTopParsesSomeItems(): void
    {
        $html =
            '<article><a href="/espana/1">Noticia EM 1</a><img src="https://img.t/x.jpg"></article>';
        $client = new MockHttpClient(new MockResponse($html));
        $s = new ElMundoScraper($client);

        $items = $s->top();

        $this->assertNotEmpty($items);
        $this->assertLessThanOrEqual(5, count($items));
        $this->assertSame("elmundo", $s->sourceKey());
    }
}
