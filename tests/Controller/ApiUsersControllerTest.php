<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Faker\Factory as FakerFactoryAlias;
use Generator;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiUsersControllerTest
 *
 * @package App\Tests\Controller
 * @group   controllers
 *
 * @coversDefaultClass \App\Controller\ApiUsersQueryController
 */
class ApiUsersControllerTest extends BaseTestCase
{
    private const RUTA_API = '/api/v1/users';

    /** @var array<string,string> $adminHeaders */
    private static array $adminHeaders;

    /**
     * Test OPTIONS /users[/userId] 204 No Content
     *
     * @covers ::__construct
     * @covers ::optionsAction
     * @return void
     */
    public function testOptionsUserAction204NoContent(): void
    {
        // OPTIONS /api/v1/users
        self::$client->request(
            Request::METHOD_OPTIONS,
            self::RUTA_API
        );
        $response = self::$client->getResponse();

        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode()
        );
        self::assertNotEmpty($response->headers->get('Allow'));

        // OPTIONS /api/v1/users/{id}
        self::$client->request(
            Request::METHOD_OPTIONS,
            self::RUTA_API . '/' . self::$faker->numberBetween(1, 100)
        );

        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode()
        );
        self::assertNotEmpty($response->headers->get('Allow'));
    }

//    /**
//     * Test GET /users 404 Not Found
//     *
//     * @return void
//     */
//    public function testCGetAction404(): void
//    {
//        $headers = [];
//        self::$client->request(
//            Request::METHOD_GET,
//            self::RUTA_API,
//            [],
//            [],
//            $headers
//        );
//        $response = self::$client->getResponse();
//        $this->checkResponseErrorMessage($response, Response::HTTP_NOT_FOUND);
//    }

    /**
     * Test POST /users 201 Created
     *
     * @return array<string,string> user data
     */
    public function testPostUserAction201Created(): array
    {
        $p_data = [
            User::EMAIL_ATTR => self::$faker->email(),
            User::PASSWD_ATTR => self::$faker->password(),
            User::ROLES_ATTR => [ self::$faker->word() ],
        ];
        self::$adminHeaders = $this->getTokenHeaders(
            self::$role_admin[User::EMAIL_ATTR],
            self::$role_admin[User::PASSWD_ATTR]
        );

        // 201
        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API,
            [],
            [],
            self::$adminHeaders,
            strval(json_encode($p_data))
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertTrue($response->isSuccessful());
        self::assertNotNull($response->headers->get('Location'));
        self::assertJson(strval($response->getContent()));
        $user = json_decode(strval($response->getContent()), true)[User::USER_ATTR];
        self::assertNotEmpty($user['id']);
        self::assertSame($p_data[User::EMAIL_ATTR], $user[User::EMAIL_ATTR]);
        self::assertContains(
            $p_data[User::ROLES_ATTR][0],
            $user[User::ROLES_ATTR]
        );

        return $user;
    }

    /**
     * Test GET /users 200 Ok
     *
     * @depends testPostUserAction201Created
     *
     * @return string ETag header
     */
    public function testCGetUserAction200Ok(): string
    {
        self::$client->request(Request::METHOD_GET, self::RUTA_API, [], [], self::$adminHeaders);
        $response = self::$client->getResponse();
        self::assertTrue($response->isSuccessful());
        self::assertNotNull($response->getEtag());
        $r_body = strval($response->getContent());
        self::assertJson($r_body);
        $users = json_decode($r_body, true);
        self::assertArrayHasKey('users', $users);

        return (string) $response->getEtag();
    }

    /**
     * Test GET /users 304 NOT MODIFIED
     *
     * @param string $etag returned by testCGetUserAction200Ok
     *
     * @depends testCGetUserAction200Ok
     */
    public function testCGetUserAction304NotModified(string $etag): void
    {
        $headers = array_merge(
            self::$adminHeaders,
            [ 'HTTP_If-None-Match' => [$etag] ]
        );
        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API,
            [],
            [],
            $headers
        );
        $response = self::$client->getResponse();
        self::assertSame(Response::HTTP_NOT_MODIFIED, $response->getStatusCode());
    }

    /**
     * Test GET /users 200 Ok (with XML header)
     *
     * @param   array<string,string> $user user returned by testPostUserAction201()
     * @return  void
     * @depends testPostUserAction201Created
     */
    public function testCGetUserAction200XmlOk(array $user): void
    {
        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API . '/' . $user['id'],
            [],
            [],
            array_merge(
                self::$adminHeaders,
                [ 'HTTP_ACCEPT' => 'application/xml' ]
            )
        );
        $response = self::$client->getResponse();
        self::assertTrue($response->isSuccessful(), strval($response->getContent()));
        self::assertNotNull($response->getEtag());
        self::assertTrue($response->headers->contains('content-type', 'application/xml'));
    }

    /**
     * Test GET /users/{userId} 200 Ok
     *
     * @param   array<string,string> $user user returned by testPostUserAction201()
     * @return  string ETag header
     * @depends testPostUserAction201Created
     */
    public function testGetUserAction200Ok(array $user): string
    {
        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API . '/' . $user['id'],
            [],
            [],
            self::$adminHeaders
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertNotNull($response->getEtag());
        $r_body = (string) $response->getContent();
        self::assertJson($r_body);
        $user_aux = json_decode($r_body, true)[User::USER_ATTR];
        self::assertSame($user['id'], $user_aux['id']);

        return (string) $response->getEtag();
    }

    /**
     * Test GET /users/{userId} 304 NOT MODIFIED
     *
     * @param array<string,string> $user user returned by testPostUserAction201Created()
     * @param string $etag returned by testGetUserAction200Ok
     * @return string Entity Tag
     *
     * @depends testPostUserAction201Created
     * @depends testGetUserAction200Ok
     */
    public function testGetUserAction304NotModified(array $user, string $etag): string
    {
        $headers = array_merge(
            self::$adminHeaders,
            [ 'HTTP_If-None-Match' => [$etag] ]
        );
        self::$client->request(Request::METHOD_GET, self::RUTA_API . '/' . $user['id'], [], [], $headers);
        $response = self::$client->getResponse();
        self::assertSame(Response::HTTP_NOT_MODIFIED, $response->getStatusCode());

        return $etag;
    }

    /**
     * Test POST /users 400 Bad Request
     *
     * @param   array<string,string> $user user returned by testPostUserAction201Created()
     * @return  array<string,string> user data
     * @depends testPostUserAction201Created
     */
    public function testPostUserAction400BadRequest(array $user): array
    {
        $p_data = [
            User::EMAIL_ATTR => $user[User::EMAIL_ATTR], // mismo e-mail
            User::PASSWD_ATTR => self::$faker->password(),
        ];
        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API,
            [],
            [],
            self::$adminHeaders,
            strval(json_encode($p_data))
        );
        $this->checkResponseErrorMessage(
            self::$client->getResponse(),
            Response::HTTP_BAD_REQUEST
        );

        return $user;
    }

    /**
     * Test PUT /users/{userId} 209 Content Returned
     *
     * @param   array<string,string> $user user returned by testPostUserAction201()
     * @param   string $etag returned by testGetUserAction304NotModified()
     * @return  array<string,string> modified user data
     * @depends testPostUserAction201Created
     * @depends testGetUserAction304NotModified
     * @depends testCGetUserAction304NotModified
     * @depends testPostUserAction400BadRequest
     */
    public function testPutUserAction209ContentReturned(array $user, string $etag): array
    {
        $role = self::$faker->word();
        $p_data = [
            User::EMAIL_ATTR => self::$faker->email(),
            User::PASSWD_ATTR => self::$faker->password(),
            User::ROLES_ATTR => [ $role ],
        ];

        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API . '/' . $user['id'],
            [],
            [],
            array_merge(
                self::$adminHeaders,
                [ 'HTTP_If-Match' => $etag ]
            ),
            strval(json_encode($p_data))
        );
        $response = self::$client->getResponse();

        self::assertSame(209, $response->getStatusCode());
        $r_body = (string) $response->getContent();
        self::assertJson($r_body);
        $user_aux = json_decode($r_body, true)[User::USER_ATTR];
        $user_aux[User::PASSWD_ATTR] = $p_data[User::PASSWD_ATTR];
        self::assertSame($user['id'], $user_aux['id']);
        self::assertSame($p_data[User::EMAIL_ATTR], $user_aux[User::EMAIL_ATTR]);
        self::assertContains(
            $role,
            $user_aux[User::ROLES_ATTR]
        );

        return $user_aux;
    }

    /**
     * Test PUT /users/{userId} 400 Bad Request
     *
     * @param   array<string,string> $user user returned by testPutUserAction209()
     * @return  void
     * @depends testPutUserAction209ContentReturned
     */
    public function testPutUserAction400BadRequest(array $user): void
    {
        // e-mail already exists
        $p_data = [
            User::EMAIL_ATTR => $user[User::EMAIL_ATTR]
        ];
        self::$client->request(
            Request::METHOD_HEAD,
            self::RUTA_API . '/' . $user['id'],
            [],
            [],
            self::$adminHeaders
        );
        $etag = self::$client->getResponse()->getEtag();
        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API . '/' . $user['id'],
            [],
            [],
            array_merge(
                self::$adminHeaders,
                [ 'HTTP_If-Match' => $etag ]
            ),
            strval(json_encode($p_data))
        );
        $response = self::$client->getResponse();
        $this->checkResponseErrorMessage($response, Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test PUT /users/{userId} 412 PRECONDITION_FAILED
     *
     * @param   array<string,string> $user user returned by testPutUserAction209ContentReturned()
     * @return  void
     * @depends testPutUserAction209ContentReturned
     */
    public function testPutUserAction412PreconditionFailed(array $user): void
    {
        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API . '/' . $user['id'],
            [],
            [],
            self::$adminHeaders
        );
        $response = self::$client->getResponse();
        $this->checkResponseErrorMessage($response, Response::HTTP_PRECONDITION_FAILED);
    }

    /**
     * Test PUT /users/{userId} 403 FORBIDDEN - try to promote the user to admin role
     *
     * @param string[] $user returned by testPutUserAction209ContentReturned()
     * @return  void
     * @depends testPutUserAction209ContentReturned
     */
    public function testPutUserAction403Forbidden(array $user): void
    {
        $userHeaders = $this->getTokenHeaders(
            $user[User::EMAIL_ATTR],
            $user[User::PASSWD_ATTR]
        );
        // get the user's etag
        self::$client->request(
            Request::METHOD_HEAD,
            self::RUTA_API . '/' . $user['id'],
            [],
            [],
            $userHeaders
        );
        $etag = self::$client->getResponse()->getEtag();

        // try to promote the user to admin role
        $p_data = [
            User::ROLES_ATTR => [ 'ROLE_ADMIN' ],
        ];
        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API . '/' . $user['id'],
            [],
            [],
            array_merge(
                $userHeaders,
                [ 'HTTP_If-Match' => $etag ]
            ),
            strval(json_encode($p_data))
        );
        $response = self::$client->getResponse();
        $this->checkResponseErrorMessage($response, Response::HTTP_FORBIDDEN);
    }

    /**
     * Test DELETE /users/{userId} 204 No Content
     *
     * @param   array<string,string> $user user returned by testPostUserAction400BadRequest()
     * @return  int userId
     * @depends testPostUserAction400BadRequest
     * @depends testPutUserAction400BadRequest
     * @depends testPutUserAction412PreconditionFailed
     * @depends testPutUserAction403Forbidden
     * @depends testCGetUserAction200XmlOk
     */
    public function testDeleteUserAction204NoContent(array $user): int
    {
        self::$client->request(
            Request::METHOD_DELETE,
            self::RUTA_API . '/' . $user['id'],
            [],
            [],
            self::$adminHeaders
        );
        $response = self::$client->getResponse();

        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode()
        );
        self::assertEmpty($response->getContent());

        return intval($user['id']);
    }

    /**
     * Test POST /users 422 Unprocessable Entity
     *
     * @param null|string $email
     * @param null|string $password
     * @dataProvider userProvider422
     * @return void
     * @depends testPutUserAction209ContentReturned
     */
    public function testPostUserAction422UnprocessableEntity(?string $email, ?string $password): void
    {
        $p_data = [
            User::EMAIL_ATTR => $email,
            User::PASSWD_ATTR => $password
        ];

        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API,
            [],
            [],
            self::$adminHeaders,
            strval(json_encode($p_data))
        );
        $response = self::$client->getResponse();
        $this->checkResponseErrorMessage($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Test GET    /users 401 UNAUTHORIZED
     * Test POST   /users 401 UNAUTHORIZED
     * Test GET    /users/{userId} 401 UNAUTHORIZED
     * Test PUT    /users/{userId} 401 UNAUTHORIZED
     * Test DELETE /users/{userId} 401 UNAUTHORIZED
     *
     * @param string $method
     * @param string $uri
     * @dataProvider providerRoutes401()
     * @return void
     * @uses \App\EventListener\ExceptionListener
     */
    public function testUserStatus401Unauthorized(string $method, string $uri): void
    {
        self::$client->request(
            $method,
            $uri,
            [],
            [],
            [ 'HTTP_ACCEPT' => 'application/json' ]
        );
        $this->checkResponseErrorMessage(
            self::$client->getResponse(),
            Response::HTTP_UNAUTHORIZED
        );
    }

    /**
     * Test GET    /users/{userId} 404 NOT FOUND
     * Test PUT    /users/{userId} 404 NOT FOUND
     * Test DELETE /users/{userId} 404 NOT FOUND
     *
     * @param string $method
     * @param int $userId user id. returned by testDeleteUserAction204()
     * @return void
     * @dataProvider providerRoutes404
     * @depends      testDeleteUserAction204NoContent
     */
    public function testUserStatus404NotFound(string $method, int $userId): void
    {
        self::$client->request(
            $method,
            self::RUTA_API . '/' . $userId,
            [],
            [],
            self::$adminHeaders
        );
        $this->checkResponseErrorMessage(
            self::$client->getResponse(),
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Test POST   /users 403 FORBIDDEN
     * Test PUT    /users/{userId} 403 FORBIDDEN
     * Test DELETE /users/{userId} 403 FORBIDDEN
     *
     * @param string $method
     * @param string $uri
     * @dataProvider providerRoutes403()
     * @return void
     * @uses \App\EventListener\ExceptionListener
     */
    public function testUserStatus403Forbidden(string $method, string $uri): void
    {
        $userHeaders = $this->getTokenHeaders(
            self::$role_user[User::EMAIL_ATTR],
            self::$role_user[User::PASSWD_ATTR]
        );
        self::$client->request($method, $uri, [], [], $userHeaders);
        $this->checkResponseErrorMessage(
            self::$client->getResponse(),
            Response::HTTP_FORBIDDEN
        );
    }

    /**
     * * * * * * * * * *
     * P R O V I D E R S
     * * * * * * * * * *
     */

    /**
     * User provider (incomplete) -> 422 status code
     *
     * @return Generator user data [email, password]
     */
    #[ArrayShape([
        'no_email' => "array",
        'no_passwd' => "array",
        'nothing' => "array"
    ])]
    public function userProvider422(): Generator
    {
        $faker = FakerFactoryAlias::create('es_ES');
        $email = $faker->email();
        $password = $faker->password();

        yield 'no_email'  => [ null,   $password ];
        yield 'no_passwd' => [ $email, null      ];
        yield 'nothing'   => [ null,   null      ];
    }

    /**
     * Route provider (expected status: 401 UNAUTHORIZED)
     *
     * @return Generator name => [ method, url ]
     */
    #[ArrayShape([
        'cgetAction401' => "array",
        'getAction401' => "array",
        'postAction401' => "array",
        'putAction401' => "array",
        'deleteAction401' => "array"
    ])]
    public function providerRoutes401(): Generator
    {
        yield 'cgetAction401'   => [ Request::METHOD_GET,    self::RUTA_API ];
        yield 'getAction401'    => [ Request::METHOD_GET,    self::RUTA_API . '/1' ];
        yield 'postAction401'   => [ Request::METHOD_POST,   self::RUTA_API ];
        yield 'putAction401'    => [ Request::METHOD_PUT,    self::RUTA_API . '/1' ];
        yield 'deleteAction401' => [ Request::METHOD_DELETE, self::RUTA_API . '/1' ];
    }

    /**
     * Route provider (expected status 404 NOT FOUND)
     *
     * @return Generator name => [ method ]
     */
    #[ArrayShape([
        'getAction404' => "array",
        'putAction404' => "array",
        'deleteAction404' => "array"
    ])]
    public function providerRoutes404(): Generator
    {
        yield 'getAction404'    => [ Request::METHOD_GET ];
        yield 'putAction404'    => [ Request::METHOD_PUT ];
        yield 'deleteAction404' => [ Request::METHOD_DELETE ];
    }

    /**
     * Route provider (expected status: 403 FORBIDDEN)
     *
     * @return Generator name => [ method, url ]
     */
    #[ArrayShape([
        'postAction403' => "array",
        'putAction403' => "array",
        'deleteAction403' => "array"
    ])]
    public function providerRoutes403(): Generator
    {
        yield 'postAction403'   => [ Request::METHOD_POST,   self::RUTA_API ];
        yield 'putAction403'    => [ Request::METHOD_PUT,    self::RUTA_API . '/1' ];
        yield 'deleteAction403' => [ Request::METHOD_DELETE, self::RUTA_API . '/1' ];
    }
}
