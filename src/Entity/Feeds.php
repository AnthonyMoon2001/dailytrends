<?php

namespace App\Entity;

use App\Repository\FeedsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FeedsRepository::class)]
#[ORM\Table(name: "feeds")]
#[
    ORM\UniqueConstraint(
        name: "uniq_source_urlhash",
        columns: ["source", "url_hash"]
    )
]
class Feeds
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    private string $title;

    #[ORM\Column(type: "string", length: 1024)]
    private string $url;

    #[ORM\Column(name: "url_hash", type: "string", length: 64)]
    private string $urlHash;

    #[ORM\Column(type: "string", length: 1024, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: "datetime_immutable", nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(type: "string", length: 50)]
    private string $source;

    #[ORM\Column(type: "datetime_immutable")]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: "datetime_immutable")]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        string $title,
        string $url,
        ?string $image,
        ?\DateTimeImmutable $publishedAt,
        string $source
    ) {
        $this->title = mb_substr($title, 0, 255);
        $this->url = mb_substr($url, 0, 1024);
        $this->urlHash = hash("sha256", $this->url);
        $this->image = $image ? mb_substr($image, 0, 1024) : null;
        $this->publishedAt = $publishedAt;
        $this->source = $source;

        $now = new \DateTimeImmutable(
            "now",
            new \DateTimeZone("Europe/Madrid")
        );
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function applyFullUpdate(
        string $title,
        string $url,
        ?string $image,
        ?\DateTimeImmutable $publishedAt,
        string $source
    ): void {
        $this->title = mb_substr($title, 0, 255);
        $this->url = mb_substr($url, 0, 1024);
        $this->urlHash = hash("sha256", $this->url);
        $this->image = $image ? mb_substr($image, 0, 1024) : null;
        $this->publishedAt = $publishedAt;
        $this->source = mb_substr($source, 0, 50);
        $this->updatedAt = new \DateTimeImmutable(
            "now",
            new \DateTimeZone("Europe/Madrid")
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getUrlHash(): string
    {
        return $this->urlHash;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function updateFrom(
        string $title,
        ?string $image,
        ?\DateTimeImmutable $publishedAt
    ): void {
        $this->title = mb_substr($title, 0, 255);
        $this->image = $image ? mb_substr($image, 0, 1024) : null;
        $this->publishedAt = $publishedAt;
        $this->updatedAt = new \DateTimeImmutable(
            "now",
            new \DateTimeZone("Europe/Madrid")
        );
    }
}
