<?php

/**
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationSuccessResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationFailureHandler;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * Class SecurityController
 */
class SecurityController extends AbstractController
{
    // Ruta al controlador de seguridad
    public final const PATH_LOGIN_CHECK = '/api/v1/login_check';

    public final const USER_ATTR_PASSWD = 'password';
    public final const USER_ATTR_EMAIL  = 'email';

    /**
     * SecurityController constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param AuthenticationSuccessHandler $successHandler
     * @param AuthenticationFailureHandler $failureHandler
     * @param UserPasswordHasherInterface $passwordHasher
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AuthenticationSuccessHandler $successHandler,
        private readonly AuthenticationFailureHandler $failureHandler,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    /**
     * @Route(
     *     path=SecurityController::PATH_LOGIN_CHECK,
     *     name="app_security_logincheck",
     *     methods={ Request::METHOD_POST }
     * )
     * @param Request $request
     * @return JWTAuthenticationSuccessResponse|Response
     * @throws \JsonException
     */
    public function logincheckAction(Request $request): JWTAuthenticationSuccessResponse|Response
    {
        // Obtención datos: Form | JSON | URLencoded
        $email = '';
        $password = '';
        if ($request->headers->get('content-type') === 'application/x-www-form-urlencoded') {   // Formulario
            $email = $request->request->get(self::USER_ATTR_EMAIL);
            $password = $request->request->get(self::USER_ATTR_PASSWD);
        } elseif (
            ($req_data = json_decode((string) $request->getContent(), true))
            && (json_last_error() === JSON_ERROR_NONE)
        ) {  // Contenido JSON
            $email = $req_data[self::USER_ATTR_EMAIL];
            $password = $req_data[self::USER_ATTR_PASSWD];
        } else {    // URL codificado
            foreach (explode('&', (string) $request->getContent()) as $param) {
                $keyValuePair = explode('=', $param, 2);
                if ($keyValuePair[0] === self::USER_ATTR_EMAIL) {
                    $email = urldecode($keyValuePair[1]);
                }
                if ($keyValuePair[0] === self::USER_ATTR_PASSWD) {
                    $password = urldecode($keyValuePair[1]);
                }
            }
        }

        $user = (null !== $email)
            ? $this->entityManager
                ->getRepository(User::class)
                ->findOneBy([ self::USER_ATTR_EMAIL => $email ])
            : null;

        if (!$user instanceof User || !$this->passwordHasher->isPasswordValid($user, strval($password))) {
            return $this->failureHandler->onAuthenticationFailure(
                $request,
                new BadCredentialsException()
            );
        }

        /** @var JsonResponse $response */
        $response = $this->successHandler->handleAuthenticationSuccess($user);
        $jwt = json_decode((string) $response->getContent(), null, 512, JSON_THROW_ON_ERROR)->token;
        $response->setData(
            [
                'token_type' => 'Bearer',
                'access_token' => $jwt,
                'expires_in' => 2 * 60 * 60,
            ]
        );
        $response->headers->set('Authorization', 'Bearer ' . $jwt);
        return $response;
    }
}
