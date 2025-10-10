<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\FeedService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

#[Route('/feeds')]
final class FeedController extends AbstractController
{
    public function __construct(private FeedService $service) {}

    #[Route('', name: 'feeds_list', methods: ['GET'])]
    public function list(Request $req): JsonResponse
    {
        $page = max(1, (int) $req->query->get('page', 1));
        $per  = min(100, max(1, (int) $req->query->get('per_page', 20)));
        $src  = $req->query->get('source');

        return $this->json($this->service->list($page, $per, $src ?: null));
    }

    #[Route('/{id<\d+>}', name: 'feeds_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        return $this->json($this->service->get($id));
    }

    #[Route('/{id<\d+>}', name: 'feeds_delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $this->service->delete($id, true);

        return new Response("null", Response::HTTP_NO_CONTENT);
    }
}
