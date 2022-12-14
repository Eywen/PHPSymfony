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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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
class ApiUsersCommandController extends AbstractController implements ApiUsersCommandInterface
{
    private const ROLE_ADMIN = 'ROLE_ADMIN';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    /**
     * @see ApiUsersCommandInterface
     *
     * @Route(
     *     path="/{userId}.{_format}",
     *     defaults={ "_format": null },
     *     requirements={
     *          "userId": "\d+",
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_DELETE },
     *     name="delete"
     * )
     *
     * @Security(
     *     expression="is_granted('IS_AUTHENTICATED_FULLY')",
     *     statusCode=401,
     *     message="`Unauthorized`: Invalid credentials."
     * )
     */
    public function deleteAction(Request $request, int $userId): Response
    {
        $format = Utils::getFormat($request);
        // Puede borrar un usuario sólo si tiene ROLE_ADMIN
        if (!$this->isGranted(self::ROLE_ADMIN)) {
            return Utils::errorMessage( // 403
                Response::HTTP_FORBIDDEN,
                '`Forbidden`: you don\'t have permission to access',
                $format
            );
        }

        /** @var User $user */
        $user = $this->entityManager
            ->getRepository(User::class)
            ->find($userId);

        if (!$user instanceof User) {   // 404 - Not Found
            return Utils::errorMessage(Response::HTTP_NOT_FOUND, null, $format);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return Utils::apiResponse(Response::HTTP_NO_CONTENT);
    }

    /**
     * @see ApiUsersCommandInterface
     *
     * @Route(
     *     path=".{_format}",
     *     defaults={ "_format": null },
     *     requirements={
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_POST },
     *     name="post"
     * )
     *
     * @Security(
     *     expression="is_granted('IS_AUTHENTICATED_FULLY')",
     *     statusCode=401,
     *     message="`Unauthorized`: Invalid credentials."
     * )
     * @throws JsonException
     */
    public function postAction(Request $request): Response
    {
        $format = Utils::getFormat($request);
        // Puede crear un usuario sólo si tiene ROLE_ADMIN
        if (!$this->isGranted(self::ROLE_ADMIN)) {
            return Utils::errorMessage( // 403
                Response::HTTP_FORBIDDEN,
                '`Forbidden`: you don\'t have permission to access',
                $format
            );
        }
        $body = $request->getContent();
        $postData = json_decode((string) $body, true, 512, JSON_THROW_ON_ERROR);

        if (!isset($postData[User::EMAIL_ATTR], $postData[User::PASSWD_ATTR])) {
            // 422 - Unprocessable Entity -> Faltan datos
            return Utils::errorMessage(Response::HTTP_UNPROCESSABLE_ENTITY, null, $format);
        }

        // hay datos -> procesarlos
        $user_exist = $this->entityManager
                ->getRepository(User::class)
                ->findOneBy([ User::EMAIL_ATTR => $postData[User::EMAIL_ATTR] ]);

        if ($user_exist instanceof User) {    // 400 - Bad Request
            return Utils::errorMessage(Response::HTTP_BAD_REQUEST, null, $format);
        }

        // 201 - Created
        $user = new User(
            strval($postData[User::EMAIL_ATTR]),
            strval($postData[User::PASSWD_ATTR])
        );
        // hash the password (based on the security.yaml config for the $user class)
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            strval($postData[User::PASSWD_ATTR])
        );
        $user->setPassword($hashedPassword);
        // roles
        if (isset($postData[User::ROLES_ATTR])) {
            $user->setRoles($postData[User::ROLES_ATTR]);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return Utils::apiResponse(
            Response::HTTP_CREATED,
            [ User::USER_ATTR => $user ],
            $format,
            [
                'Location' => $request->getScheme() . '://' . $request->getHttpHost() .
                    ApiUsersQueryInterface::RUTA_API . '/' . $user->getId(),
            ]
        );
    }

    /**
     * @see ApiUsersCommandInterface
     *
     * @Route(
     *     path="/{userId}.{_format}",
     *     defaults={ "_format": null },
     *     requirements={
     *          "userId": "\d+",
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_PUT },
     *     name="put"
     * )
     *
     * @Security(
     *     expression="is_granted('IS_AUTHENTICATED_FULLY')",
     *     statusCode=401,
     *     message="`Unauthorized`: Invalid credentials."
     * )
     * @throws JsonException
     */
    public function putAction(Request $request, int $userId): Response
    {
        $format = Utils::getFormat($request);
        // Puede editar otro usuario diferente sólo si tiene ROLE_ADMIN
        /** @var User $user */
        $user = $this->getUser();
        if (
            ($user->getId() !== $userId)
            && !$this->isGranted(self::ROLE_ADMIN)
        ) {
            return Utils::errorMessage( // 403
                Response::HTTP_FORBIDDEN,
                '`Forbidden`: you don\'t have permission to access',
                $format
            );
        }
        $body = (string) $request->getContent();
        $postData = json_decode($body, true);

        /** @var User $user */
        $user = $this->entityManager
            ->getRepository(User::class)
            ->find($userId);

        if (!$user instanceof User) {    // 404 - Not Found
            return Utils::errorMessage(Response::HTTP_NOT_FOUND, null, $format);
        }

        // Optimistic Locking (strong validation)
        $etag = md5((string) json_encode($user, JSON_THROW_ON_ERROR) . $user->getPassword());
        if (!$request->headers->has('If-Match') || $etag != $request->headers->get('If-Match')) {
            return Utils::errorMessage(
                Response::HTTP_PRECONDITION_FAILED,
                'PRECONDITION FAILED: one or more conditions given evaluated to false',
                $format
            ); // 412
        }

        if (isset($postData[User::EMAIL_ATTR])) {
            $user_exist = $this->entityManager
                ->getRepository(User::class)
                ->findOneBy([ User::EMAIL_ATTR => $postData[User::EMAIL_ATTR] ]);

            if ($user_exist instanceof User) {    // 400 - Bad Request
                return Utils::errorMessage(Response::HTTP_BAD_REQUEST, null, $format);
            }
            $user->setEmail($postData[User::EMAIL_ATTR]);
        }

        // password
        if (isset($postData[User::PASSWD_ATTR])) {
            // hash the password (based on the security.yaml config for the $user class)
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $postData[User::PASSWD_ATTR]
            );
            $user->setPassword($hashedPassword);
        }

        // roles
        if (isset($postData[User::ROLES_ATTR])) {
            if (
                in_array(self::ROLE_ADMIN, $postData[User::ROLES_ATTR], true)
                && !$this->isGranted(self::ROLE_ADMIN)
            ) {
                return Utils::errorMessage( // 403
                    Response::HTTP_FORBIDDEN,
                    '`Forbidden`: you don\'t have permission to access',
                    $format
                );
            }
            $user->setRoles($postData[User::ROLES_ATTR]);
        }

        $this->entityManager->flush();

        return Utils::apiResponse(
            209,                        // 209 - Content Returned
            [ User::USER_ATTR => $user ],
            $format
        );
    }
}
