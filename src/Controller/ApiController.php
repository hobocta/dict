<?php

/** @noinspection PhpUnused */

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Request\WordDto;
use App\Service\DictService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
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
    public function __construct(private readonly DictService $dictService)
    {
    }

    /**
     * @param WordDto $wordDto
     *
     * @return Response
     */
    #[Route('/api/word', methods: ['POST'])]
    public function main(
        #[MapRequestPayload] WordDto $wordDto
    ): Response {
        return $this->json($this->dictService->getWordDtoCached($wordDto->getWord()));
    }
}
