<?php

namespace App\Entity;

use App\Repository\NewsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NewsRepository::class)]
#[ORM\Table(name: "news")]
#[
    ORM\UniqueConstraint(
        name: "uniq_source_urlhash",
        columns: ["source", "url_hash"]
    )
]
class News
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    private string $title;

    // Guarda la URL completa
    #[ORM\Column(type: "string", length: 1024)]
    private string $url;

    // Hash hex de 64 chars (sha256)
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
