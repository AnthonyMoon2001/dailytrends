<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\FeedService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route("/feeds")]
final class FeedController extends AbstractController
{
    public function __construct(private FeedService $service) {}

    #[Route("", name: "feeds_list", methods: ["GET"])]
    public function list(Request $req): JsonResponse
    {
        $page = max(1, (int) $req->query->get("page", 1));
        $per = min(100, max(1, (int) $req->query->get("per_page", 20)));
        $src = $req->query->get("source");

        return $this->json($this->service->list($page, $per, $src ?: null));
    }

    #[Route("/{id<\d+>}", name: "feeds_show", methods: ["GET"])]
    public function show(int $id): JsonResponse
    {
        return $this->json($this->service->get($id));
    }

    #[Route("/{id<\d+>}", name: "feeds_delete", methods: ["DELETE"])]
    public function delete(int $id): JsonResponse
    {
        return $this->json($this->service->delete($id));
    }

    #[Route("", name: "feeds_create", methods: ["POST"])]
    public function create(
        Request $req,
        ValidatorInterface $validator
    ): JsonResponse {
        try {
            $data = json_decode(
                $req->getContent(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException) {
            return $this->json(
                ["error" => "JSON inválido"],
                Response::HTTP_BAD_REQUEST
            );
        }

        $constraints = new Assert\Collection(
            fields: [
                "title" => [new Assert\NotBlank(), new Assert\Length(max: 255)],
                "url" => [
                    new Assert\NotBlank(),
                    new Assert\Url(),
                    new Assert\Length(max: 1024),
                ],
                "source" => [new Assert\NotBlank(), new Assert\Length(max: 50)],
                "image" => new Assert\Optional([
                    new Assert\Url(),
                    new Assert\Length(max: 1024),
                ]),
                "publishedAt" => new Assert\Optional([new Assert\Date()]),
            ],
            allowExtraFields: false
        );

        $publishedAt = null;
        if (!empty($data["publishedAt"])) {
            $tz = new \DateTimeZone("Europe/Madrid");
            $publishedAt = \DateTimeImmutable::createFromFormat(
                "!Y-m-d",
                $data["publishedAt"],
                $tz
            );
            if ($publishedAt === false) {
                return $this->json(
                    [
                        "errors" => [
                            "publishedAt" => [
                                "Formato inválido. Usa YYYY-MM-DD (p. ej. 2025-10-10).",
                            ],
                        ],
                    ],
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
        }

        $violations = $validator->validate($data, $constraints);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[$v->getPropertyPath()][] = $v->getMessage();
            }
            return $this->json(
                ["errors" => $errors],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $result = $this->service->create(
            title: (string) $data["title"],
            url: (string) $data["url"],
            image: $data["image"] ?? null,
            publishedAt: $publishedAt,
            source: (string) $data["source"],
        );

        return $this->json($result);
    }
}
