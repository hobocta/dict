<?php

/** @noinspection PhpUnused */

declare(strict_types=1);

/** @noinspection PhpUnused */

namespace App\Controller;

use App\Service\DictService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class PageController
 */
class PageController extends AbstractController
{
    /**
     * PageController constructor.
     *
     * @param DictService $dictService
     * @param string $env
     */
    public function __construct(private readonly DictService $dictService, private readonly string $env)
    {
    }

    #[Route('/', methods: ['GET'])]
    public function main(): Response
    {
        return $this->render(
            'main.html.twig',
            [
                'languages' => $this->dictService->getLanguagesDtoCached(),
                'env' => $this->env,
            ]
        );
    }
}
