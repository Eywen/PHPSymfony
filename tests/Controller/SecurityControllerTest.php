<?php

/**
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://miw.etsisi.upm.es/ E.T.S. de Ingeniería de Sistemas Informáticos
 */

namespace App\Tests\Controller;

use Faker\Factory as FakerFactoryAlias;
use Generator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SecurityControllerTest
 *
 * @package App\Tests\Controller
 * @group   controllers
 *
 * @coversDefaultClass \App\Controller\SecurityController
 */
class SecurityControllerTest extends BaseTestCase
{
    /**
     * POST /api/v1/login_check 200 Ok
     *
     * @param string $testEmail
     * @param string $testPasswd
     * @dataProvider userProvider()
     * @return void
     */
    public function testLogincheckAction200Ok(string $testEmail, string $testPasswd): void
    {
        $data = [
            'email' => $testEmail,
            'password' => $testPasswd
        ];

        // Request body
        self::$client->request(
            Request::METHOD_POST,
            '/api/v1/login_check',
            [],
            [],
            [ 'CONTENT_TYPE' => 'application/json' ],
            (string) json_encode($data)
        );
        $response = self::$client->getResponse();
        self::assertTrue($response->isSuccessful());
        $json_resp = json_decode(strval($response->getContent()), true);
        self::assertArrayHasKey('access_token', $json_resp);
        self::assertArrayHasKey('token_type', $json_resp);
        self::assertArrayHasKey('expires_in', $json_resp);
        self::assertNotNull($response->headers->get('Authorization'));

        // Form
        self::$client->request(
            Request::METHOD_POST,
            '/api/v1/login_check',
            $data,
            [],
            [ 'CONTENT_TYPE' => 'application/x-www-form-urlencoded' ]
        );
        $response = self::$client->getResponse();
        self::assertTrue($response->isSuccessful());
        $json_resp = json_decode(strval($response->getContent()), true);
        self::assertArrayHasKey('access_token', $json_resp);
        self::assertArrayHasKey('token_type', $json_resp);
        self::assertArrayHasKey('expires_in', $json_resp);
        self::assertNotNull($response->headers->get('Authorization'));

        // Urlencoded request body
        $data = 'email=' . urlencode($testEmail);
        $data .= '&password=' . urlencode($testPasswd);
        self::$client->request(
            Request::METHOD_POST,
            '/api/v1/login_check',
            [],
            [],
            [ 'CONTENT_TYPE' => 'text/plain' ],
            $data
        );
        $response = self::$client->getResponse();
        self::assertTrue($response->isSuccessful());
        $json_resp = json_decode(strval($response->getContent()), true);
        self::assertArrayHasKey('access_token', $json_resp);
        self::assertArrayHasKey('token_type', $json_resp);
        self::assertArrayHasKey('expires_in', $json_resp);
        self::assertNotNull($response->headers->get('Authorization'));
    }

    /**
     * POST /api/v1/login_check 401 UNAUTHORIZED
     *
     * @param string|null $testEmail
     * @param string|null $testPasswd
     * @return void
     * @dataProvider fakeUserProvider()
     */
    public function testLogincheckAction401Unauthorized(?string $testEmail, ?string $testPasswd): void
    {
        $data = [
            'email' => $testEmail,
            'password' => $testPasswd ?? ''
        ];

        self::$client->request(
            Request::METHOD_POST,
            '/api/v1/login_check',
            [],
            [],
            [ 'CONTENT_TYPE' => 'application/json' ],
            (string) json_encode($data)
        );
        $response = self::$client->getResponse();
        $this->checkResponseErrorMessage($response, Response::HTTP_UNAUTHORIZED);
        // self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $json_resp = json_decode(strval($response->getContent()), true);
        self::assertArrayNotHasKey('access_token', $json_resp);
        self::assertNull($response->headers->get('Authorization'));
    }

    /**
     * User provider
     *
     * @return Generator<mixed> name => [ username, password]
     */
    public function userProvider(): Generator
    {
        yield 'role_user'  => [ $_ENV['ROLE_USER_EMAIL'], $_ENV['ROLE_USER_PASSWD'] ];
        yield 'role_admin' => [ $_ENV['ADMIN_USER_EMAIL'], $_ENV['ADMIN_USER_PASSWD'] ];
    }

    /**
     * Fake User provider
     *
     * @return Generator<mixed> name => [ username, password]
     */
    public function fakeUserProvider(): Generator
    {
        $faker = FakerFactoryAlias::create('es_ES');
        $email = $faker->email();
        $password = $faker->password();

        yield 'fakeUser1' => [ $email, $password ];
        yield 'fakeUser2' => [ null, null ];
        yield 'fakeUser3' => [ null, $_ENV['ROLE_USER_PASSWD'] ];
        yield 'fakeUser4' => [ $_ENV['ROLE_USER_EMAIL'], null ];
        yield 'fakeUser5' => [ $_ENV['ROLE_USER_EMAIL'], 'X' . $_ENV['ROLE_USER_PASSWD']];
    }
}
