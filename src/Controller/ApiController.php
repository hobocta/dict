<?php

/** @noinspection PhpUnused */

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Api\Request\TranslationRequestDto;
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
     * @return Response
     */
    #[Route('/api/languages', methods: ['GET'])]
    public function languages(): Response {
        return $this->json($this->dictService->getLanguagesDtoCached());
    }

    /**
     * @param TranslationRequestDto $translationRequestDto
     *
     * @return Response
     */
    #[Route('/api/translation', methods: ['POST'])]
    public function word(
        #[MapRequestPayload] TranslationRequestDto $translationRequestDto
    ): Response {
        return $this->json(
            $this->dictService->getWordDtoCached(
                $translationRequestDto->getSourceLanguageId(),
                $translationRequestDto->getTargetLanguageId(),
                $translationRequestDto->getWordId()
            )
        );
    }
}
