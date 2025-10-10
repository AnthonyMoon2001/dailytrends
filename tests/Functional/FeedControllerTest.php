<?php

namespace App\Tests\Functional;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class FeedControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function setUp(): void
    {
        self::ensureKernelShutdown();

        $this->client = static::createClient();
        $this->client->catchExceptions(false);
        $em = static::getContainer()
            ->get("doctrine")
            ->getManager();
        $tool = new SchemaTool($em);
        $tool->dropDatabase();
        $tool->createSchema($em->getMetadataFactory()->getAllMetadata());
    }

    public function testListInitiallyEmpty(): void
    {
        $this->client->request("GET", "/feeds");
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey("items", $data);
        $this->assertCount(0, $data["items"]);
    }

    public function testCreateThenShow(): void
    {
        $payload = [
            "title" => "Titular",
            "url" => "https://ejemplo.com/x",
            "image" => null,
            "publishedAt" => "2025-10-10",
            "source" => "elmundo",
        ];

        $this->client->request(
            "POST",
            "/feeds",
            [],
            [],
            ["CONTENT_TYPE" => "application/json"],
            json_encode($payload)
        );
        $this->assertResponseStatusCodeSame(201);

        $created = json_decode(
            $this->client->getResponse()->getContent(),
            true
        );
        $id = $created["item"]["id"] ?? null;
        $this->assertNotNull($id);

        $this->client->request("GET", "/feeds/$id");
        $this->assertResponseIsSuccessful();
    }

    public function testValidationErrors(): void
    {
        $this->client->request(
            "POST",
            "/feeds",
            [],
            [],
            ["CONTENT_TYPE" => "application/json"],
            json_encode(["title" => "", "url" => "not-a-url", "source" => ""])
        );
        $this->assertResponseStatusCodeSame(422);
    }
}
