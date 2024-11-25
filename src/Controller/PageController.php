<?php

/** @noinspection PhpUnused */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class PageController
 */
class PageController extends AbstractController
{
    #[Route('/', methods: ['GET'])]
    public function main(): Response
    {
        return $this->render('main.html.twig');
    }
}
