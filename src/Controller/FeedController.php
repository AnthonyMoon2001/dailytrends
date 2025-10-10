<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\FeedResponseDto;
use App\Service\FeedService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route("/feeds")]
#[OA\Tag(name: "Feeds")]
final class FeedController extends AbstractController
{
    public function __construct(private FeedService $service) {}

    #[Route("", name: "feeds_list", methods: ["GET"])]
    #[
        OA\Get(
            summary: "Lista feeds",
            responses: [
                new OA\Response(
                    response: 200,
                    description: "OK",
                    content: new OA\JsonContent(
                        properties: [
                            new OA\Property(
                                property: "items",
                                type: "array",
                                items: new OA\Items(
                                    ref: new Model(type: FeedResponseDto::class)
                                )
                            ),
                        ]
                    )
                ),
            ]
        )
    ]
    public function list(): JsonResponse
    {
        return $this->json($this->service->list());
    }

    #[Route("/{id<\d+>}", name: "feeds_show", methods: ["GET"])]
    #[
        OA\Get(
            summary: "Detalle de un feed",
            responses: [
                new OA\Response(
                    response: 200,
                    description: "OK",
                    content: new OA\JsonContent(
                        properties: [
                            new OA\Property(
                                property: "item",
                                ref: new Model(type: FeedResponseDto::class)
                            ),
                        ]
                    )
                ),
                new OA\Response(response: 404, description: "No encontrado"),
            ]
        )
    ]
    public function show(int $id): JsonResponse
    {
        return $this->json($this->service->get($id));
    }

    #[Route("/{id<\d+>}", name: "feeds_delete", methods: ["DELETE"])]
    #[
        OA\Delete(
            summary: "Elimina un feed",
            responses: [
                new OA\Response(response: 204, description: "Eliminado"),
                new OA\Response(response: 404, description: "No encontrado"),
            ]
        )
    ]
    public function delete(int $id): JsonResponse
    {
        $this->service->delete($id);
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route("", name: "feeds_create", methods: ["POST"])]
    #[
        OA\Post(
            summary: "Crea un feed",
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "title",
                            type: "string",
                            example: "Titular"
                        ),
                        new OA\Property(
                            property: "url",
                            type: "string",
                            example: "https://ejemplo.com/x"
                        ),
                        new OA\Property(
                            property: "image",
                            type: "string",
                            nullable: true,
                            example: "https://ejemplo.com/img.jpg"
                        ),
                        new OA\Property(
                            property: "publishedAt",
                            type: "string",
                            nullable: true,
                            example: "2025-10-10"
                        ),
                        new OA\Property(
                            property: "source",
                            type: "string",
                            example: "elmundo"
                        ),
                    ]
                )
            ),
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Creado",
                    content: new OA\JsonContent(
                        properties: [
                            new OA\Property(
                                property: "item",
                                ref: new Model(type: FeedResponseDto::class)
                            ),
                        ]
                    )
                ),
                new OA\Response(
                    response: 409,
                    description: "Duplicado (source+url)"
                ),
                new OA\Response(response: 422, description: "Validación"),
                new OA\Response(response: 400, description: "JSON inválido"),
            ]
        )
    ]
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
            source: (string) $data["source"]
        );

        return $this->json($result, Response::HTTP_CREATED);
    }

    #[Route("/{id<\d+>}", name: "feeds_update", methods: ["PUT"])]
    #[
        OA\Put(
            summary: "Actualiza un feed",
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "title", type: "string"),
                        new OA\Property(property: "url", type: "string"),
                        new OA\Property(
                            property: "image",
                            type: "string",
                            nullable: true
                        ),
                        new OA\Property(
                            property: "publishedAt",
                            type: "string",
                            nullable: true,
                            example: "2025-10-10"
                        ),
                        new OA\Property(
                            property: "source",
                            type: "string",
                            example: "elpais"
                        ),
                    ]
                )
            ),
            responses: [
                new OA\Response(
                    response: 200,
                    description: "OK",
                    content: new OA\JsonContent(
                        properties: [
                            new OA\Property(
                                property: "item",
                                ref: new Model(type: FeedResponseDto::class)
                            ),
                        ]
                    )
                ),
                new OA\Response(response: 404, description: "No encontrado"),
                new OA\Response(
                    response: 409,
                    description: "Duplicado"
                ),
                new OA\Response(response: 422, description: "Validación"),
            ]
        )
    ]
    public function update(
        int $id,
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
                                "Formato inválido. Usa YYYY-MM-DD.",
                            ],
                        ],
                    ],
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
        }

        $r = $this->service->update(
            id: $id,
            title: (string) $data["title"],
            url: (string) $data["url"],
            image: $data["image"] ?? null,
            publishedAt: $publishedAt,
            source: (string) $data["source"]
        );

        return $this->json($r, Response::HTTP_OK);
    }
}
