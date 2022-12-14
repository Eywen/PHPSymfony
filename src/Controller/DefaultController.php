<?php

/**
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace App\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DefaultController
 *
 * @package App\Controller
 */
class DefaultController
{
    /**
     * Redirect home page
     *
     * @Route(
     *     path="/",
     *     name="homepage",
     *     methods={ Request::METHOD_GET }
     * )
     */
    public function homeRedirect(): Response
    {
        return new RedirectResponse(
            '/api-docs/index.html',
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }
}
