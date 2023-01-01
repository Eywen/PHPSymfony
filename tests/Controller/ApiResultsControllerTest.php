<?php

namespace App\Tests\Controller;

use App\Entity\Result;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiResultsControllerTest
 *
 * @package App\Tests\Controller
 * @group   controllers
 *
 * @coversDefaultClass \App\Controller\ApiResultsQueryController
 */
class ApiResultsControllerTest extends BaseTestCase
{
    private const RUTA_API = '/api/v1/results';

    /** @var array<string,string> $adminHeaders */
    private static array $adminHeaders;

    /**
     * Test OPTIONS /resultss[/resultId] 204 No Content
     *
     * @covers ::__construct
     * @covers ::optionsAction
     * @return void
     */
    public function testOptionsUserAction204NoContent(): void
    {
        // OPTIONS /api/v1/results
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

        // OPTIONS /api/v1/results/{id}
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
     * Test POST /results 201 Created
     *
     * @return array<string,string> result data
     */
    public function testPostResultAction201Created(): array
    {
        $p_data = [
            Result::RESULT_ATTR => self::$faker->randomDigitNotNull(),
            Result::USER_ID_ATTR => 1, //debe ser un id de usuario que exista en user

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
        $result = json_decode(strval($response->getContent()), true);
        self::assertNotEmpty($result[Result::RESULT_ATTR][User::USER_ID_ATTR]);
        self::assertSame($p_data[Result::RESULT_ATTR], $result[Result::RESULT_ATTR][Result::RESULT_ATTR]);


        return $result['result'];
    }

    /**
     * Test GET /results 200 Ok
     *
     * @depends testPostResultAction201Created
     *
     * @return string ETag header
     */
    public function testCGetResultAction200Ok(): string
    {
        self::$client->request(Request::METHOD_GET, self::RUTA_API, [], [], self::$adminHeaders);
        $response = self::$client->getResponse();
        self::assertTrue($response->isSuccessful());
        self::assertNotNull($response->getEtag());
        $r_body = strval($response->getContent());
        self::assertJson(strval($r_body));
        $results = json_decode($r_body, true);
        self::assertArrayHasKey('results', $results);

        return (string) $response->getEtag();
    }

    /**
     * Test GET /users 304 NOT MODIFIED
     *
     * @param string $etag returned by testCGetUserAction200Ok
     *
     * @depends testCGetResultAction200Ok
     */
    public function testCGetResultAction304NotModified(string $etag): void
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
     * Test GET /results 200 Ok (with XML header)
     *
     * @param   array<string,string> $result result returned by testPostResultAction201()
     * @return  void
     * @depends testPostResultAction201Created
     */
    public function testCGetUserAction200XmlOk(array $result): void
    {
        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API . '/' . $result['id'],
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
     * Test GET /results/{resultId} 200 Ok
     *
     * @param   array<string,string> $result result returned by testPostResultAction201()
     * @return  string ETag header
     * @depends testPostResultAction201Created
     */
    public function testGetResultAction200Ok(array $result): string
    {
        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API . '/' . $result['id'],
            [],
            [],
            self::$adminHeaders
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertNotNull($response->getEtag());
        $r_body = (string) $response->getContent();
        self::assertJson($r_body);
        $result_aux = json_decode($r_body, true);


        return (string) $response->getEtag();
    }

    /**
     * Test GET /results/{resultId} 304 NOT MODIFIED
     *
     * @param array<string,string> $result result returned by testPostResultAction201Created()
     * @param string $etag returned by testGetResultAction200Ok
     * @return string Entity Tag
     *
     * @depends testPostResultAction201Created
     * @depends testGetResultAction200Ok
     */
    public function testGetResultAction304NotModified(array $result, string $etag): string
    {
        $headers = array_merge(
            self::$adminHeaders,
            [ 'HTTP_If-None-Match' => [$etag] ]
        );
        self::$client->request(Request::METHOD_GET, self::RUTA_API . '/' . $result['id'], [], [], $headers);
        $response = self::$client->getResponse();
        self::assertSame(Response::HTTP_NOT_MODIFIED, $response->getStatusCode());

        return $etag;
    }

    /**
     * Test POST /results 400 Bad Request
     *
     * @param   array<string,string> $result user returned by testPostResultAction201Created()
     * @return  array<string,string> user data
     * @depends testPostResultAction201Created
     */
    public function testPostResultAction400BadRequest(array $result): array
    {
        $p_data = [
            Result::RESULT_ATTR => $result[Result::RESULT_ATTR],
            Result::RESULT_ID_ATTR => 1, // id que ya existe en la bbdd
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

        return $result;
    }

    /**
     * Test PUT /results/{resultId} 209 Content Returned
     *
     * @param   array<string,string> $result result returned by testPostResultAction201()
     * @param   string $etag returned by testGetResultAction304NotModified()
     * @return  array<string,string> modified user data
     * @depends testPostResultAction201Created
     * @depends testGetResultAction304NotModified
     * @depends testCGetResultAction304NotModified
     * @depends testPostResultAction400BadRequest
     */
    public function testPutResultAction209ContentReturned(array $result, string $etag): array
    {
        $p_data = [
            Result::RESULT_ATTR => self::$faker->randomDigitNotNull(),
            Result::USER_ID_ATTR => 1,
        ];

        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API . '/' . $result['id'],
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
        $result_aux = json_decode($r_body, true);
        self::assertSame($result[User::USER_ID_ATTR], $result_aux[Result::RESULT_ATTR][User::USER_ID_ATTR]);
        self::assertSame($p_data[Result::USER_ID_ATTR], $result_aux[Result::RESULT_ATTR][User::USER_ATTR][User::USER_ID_ATTR]);

        return $result_aux['result'];
    }

    /**
     * Test PUT /results/{resultId} 400 Bad Request
     *
     * @param   array<string,string> $result result returned by testPutResultAction209()
     * @return  void
     * @depends testPutResultAction209ContentReturned
     */
    public function testPutResultAction400BadRequest(array $result): void
    {
        $p_data = [
            Result::USER_ID_ATTR => 250
        ];
        self::$client->request(
            Request::METHOD_HEAD,
            self::RUTA_API . '/' . $result['id'],
            [],
            [],
            self::$adminHeaders
        );
        $etag = self::$client->getResponse()->getEtag();
        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API . '/' . $result['id'],
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
     * Test PUT /results/{resultId} 412 PRECONDITION_FAILED
     *
     * @param   array<string,string> $result result returned by testPutResultAction209ContentReturned()
     * @return  void
     * @depends testPutResultAction209ContentReturned
     */
    public function testPutResultAction412PreconditionFailed(array $result): void
    {
        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API . '/' . $result['id'],
            [],
            [],
            self::$adminHeaders
        );
        $response = self::$client->getResponse();
        $this->checkResponseErrorMessage($response, Response::HTTP_PRECONDITION_FAILED);
    }


}
