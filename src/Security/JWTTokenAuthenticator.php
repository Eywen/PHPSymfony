<?php

/**
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://miw.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace App\Security;

use App\Utility\Utils;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Guard\JWTTokenAuthenticator as BaseAuthenticator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class JWTTokenAuthenticator
 */
class JWTTokenAuthenticator extends BaseAuthenticator
{
    /**
     * Does the authenticator support the given Request?
     *
     * If this returns false, the authenticator will be skipped.
     */
    public function supports(Request $request): bool
    {
        return parent::supports($request) && $request->isMethod(Request::METHOD_POST);
    }

    /**
     * If getUser() returns a User object, this method is called.
     * Your job is to verify if the credentials are correct.
     *
     * @param mixed $credentials
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return !empty($user->getRoles());
    }

    /**
     * This is called if authentication fails.
     *
     * @param Request $request
     * @param AuthenticationException $authException
     * @return JsonResponse|Response|null
     */
    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $authException
    ): JsonResponse|Response|null {
        $data = [
            'code' => Response::HTTP_FORBIDDEN,
            'message' => Response::$statusTexts[Response::HTTP_FORBIDDEN] . ': ' .
                $authException->getMessageKey(),
        ];
        return new JsonResponse($data, Response::HTTP_FORBIDDEN);   // 403
    }

    /**
     * Returns a response that directs the user to authenticate.
     *
     * This is called when an anonymous request accesses a resource that
     * requires authentication. The job of this method is to return some
     * response that "helps" the user start into the authentication process.
     *
     * Examples:
     *
     * - For a form login, you might redirect to the login page
     *
     *     return new RedirectResponse('/login');
     *
     * - For an API token authentication system, you return a 401 response
     *
     *     return new Response('Auth header required', 401);
     *
     * @param Request $request
     * @param AuthenticationException|null $authException
     * @return Response
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return Utils::errorMessage(
            Response::HTTP_UNAUTHORIZED,
            Response::$statusTexts[401],
            Utils::getFormat($request)
        );
    }
}
