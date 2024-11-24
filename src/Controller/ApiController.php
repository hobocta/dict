<?php

/** @noinspection PhpUnused */

namespace App\Controller;

use App\Service\DictService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class ApiController
 */
class ApiController extends AbstractController
{
    /**
     * ApiController constructor.
     *
     * @param DictService $dictService
     */
    public function __construct(private DictService $dictService)
    {
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/api/word', methods: ['POST'])]
    public function main(
        Request $request
    ): Response {
        // @todo move to param converter
        $requestBody = json_decode($request->getContent(), true, JSON_THROW_ON_ERROR);

        $word = $requestBody['word'] ?? null;

        if (empty($word)) {
            return new Response('Empty word', Response::HTTP_BAD_REQUEST);
        }

        if (!preg_match('/^[a-z]+$/', $word)) {
            return new Response('Incorrect word', Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->dictService->getEntriesCached($word));
    }
}
