<?php

namespace App\Controller;

use App\Entity\User;
use App\Utility\Utils;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use function in_array;

/**
 * Class ApiUsersController
 *
 * @package App\Controller
 *
 * @Route(
 *     path=ApiUsersQueryInterface::RUTA_API,
 *     name="api_users_"
 * )
 */
class ApiUsersQueryController extends AbstractController implements ApiUsersQueryInterface
{
    private const HEADER_CACHE_CONTROL = 'Cache-Control';
    private const HEADER_ETAG = 'ETag';
    private const HEADER_ALLOW = 'Allow';

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @see ApiUsersQueryInterface
     *
     * @Route(
     *     path=".{_format}/{sort?id}",
     *     defaults={ "_format": "json", "sort": "id" },
     *     requirements={
     *         "sort": "id|email|roles",
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_GET },
     *     name="cget"
     * )
     *
     * @Security(
     *     expression="is_granted('IS_AUTHENTICATED_FULLY')",
     *     statusCode=401,
     *     message="`Unauthorized`: Invalid credentials."
     * )
     * @throws JsonException
     */
    public function cgetAction(Request $request): Response
    {
        $order = $request->get('sort');
        $users = $this->entityManager
            ->getRepository(User::class)
            ->findBy([], [ $order => 'ASC' ]);
        $format = Utils::getFormat($request);

        // No hay usuarios?
        // @codeCoverageIgnoreStart
        if (empty($users)) {
            return Utils::errorMessage(Response::HTTP_NOT_FOUND, null, $format);    // 404
        }
        // @codeCoverageIgnoreEnd

        // Caching with ETag
        $etag = md5((string) json_encode($users, JSON_THROW_ON_ERROR));
        if (($etags = $request->getETags()) && (in_array($etag, $etags) || in_array('*', $etags))) {
            return new Response(null, Response::HTTP_NOT_MODIFIED); // 304
        }

        return Utils::apiResponse(
            Response::HTTP_OK,
            [ 'users' => array_map(fn ($u) =>  ['user' => $u], $users) ],
            $format,
            [
                self::HEADER_CACHE_CONTROL => 'private',
                self::HEADER_ETAG => $etag,
            ]
        );
    }

    /**
     * @see ApiUsersQueryInterface
     *
     * @Route(
     *     path="/{userId}.{_format}",
     *     defaults={ "_format": null },
     *     requirements={
     *          "userId": "\d+",
     *          "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_GET },
     *     name="get"
     * )
     *
     * @Security(
     *     expression="is_granted('IS_AUTHENTICATED_FULLY')",
     *     statusCode=401,
     *     message="`Unauthorized`: Invalid credentials."
     * )
     * @throws JsonException
     */
    public function getAction(Request $request, int $userId): Response
    {
        /** @var User $user */
        $user = $this->entityManager
            ->getRepository(User::class)
            ->find($userId);
        $format = Utils::getFormat($request);

        if (!$user instanceof User) {
            return Utils::errorMessage(Response::HTTP_NOT_FOUND, null, $format);    // 404
        }

        // Caching with ETag
        $etag = md5((string) json_encode($user, JSON_THROW_ON_ERROR) . $user->getPassword());
        if (($etags = $request->getETags()) && (in_array($etag, $etags) || in_array('*', $etags))) {
                return new Response(null, Response::HTTP_NOT_MODIFIED); // 304
        }

        return Utils::apiResponse(
            Response::HTTP_OK,
            [ User::USER_ATTR => $user ],
            $format,
            [
                self::HEADER_CACHE_CONTROL => 'private',
                self::HEADER_ETAG => $etag,
            ]
        );
    }

    /**
     * @see ApiUsersQueryInterface
     *
     * @Route(
     *     path="/{userId}.{_format}",
     *     defaults={ "userId" = 0, "_format": "json" },
     *     requirements={
     *          "userId": "\d+",
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_OPTIONS },
     *     name="options"
     * )
     */
    public function optionsAction(int $userId): Response
    {
        $methods = $userId !== 0
            ? [ Request::METHOD_GET, Request::METHOD_PUT, Request::METHOD_DELETE ]
            : [ Request::METHOD_GET, Request::METHOD_POST ];
        $methods[] = Request::METHOD_OPTIONS;

        return new Response(
            null,
            Response::HTTP_NO_CONTENT,
            [
                self::HEADER_ALLOW => implode(', ', $methods),
                self::HEADER_CACHE_CONTROL => 'public, inmutable'
            ]
        );
    }
}
