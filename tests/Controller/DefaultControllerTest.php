<?php

/**
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://miw.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DefaultControllerTest
 *
 * @package App\Tests\Controller
 * @group   controllers
 */
class DefaultControllerTest extends BaseTestCase
{
    /**
     * Implements testHomeRedirect
     */
    public function testHomeRedirect(): void
    {
        // Request body
        self::$client->request(
            Request::METHOD_GET,
            '/'
        );
        self::assertResponseStatusCodeSame(Response::HTTP_TEMPORARY_REDIRECT);
        self::assertResponseRedirects();
    }
}
