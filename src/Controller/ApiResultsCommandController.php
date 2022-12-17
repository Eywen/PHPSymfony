<?php

namespace App\Controller;

use App\Entity\Result;
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
 * Class ApiResultsController
 *
 * @package App\Controller
 *
 * @Route(
 *     path=ApiResultsQueryInterface::RUTA_API,
 *     name="api_results_"
 * )
 */
class ApiResultsCommandController extends AbstractController implements ApiResultsCommandInterface
{
    private const ROLE_ADMIN = 'ROLE_ADMIN';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        //private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    /**
     * @see ApiResultsCommandInterface
     *
     * @Route(
     *     path="/{resultId}.{_format}",
     *     defaults={ "_format": null },
     *     requirements={
     *          "resultId": "\d+",
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
    public function deleteAction(Request $request, int $resultId): Response
    {
        $format = Utils::getFormat($request);
        // Puede borrar un resultado sólo si tiene ROLE_ADMIN
        if (!$this->isGranted(self::ROLE_ADMIN)) {
            return Utils::errorMessage( // 403
                Response::HTTP_FORBIDDEN,
                '`Forbidden`: you don\'t have permission to access',
                $format
            );
        }

        /** @var Result $result */
        $result = $this->entityManager
            ->getRepository(Result::class)
            ->find($resultId);

        if (!$result instanceof Result) {   // 404 - Not Found
            return Utils::errorMessage(Response::HTTP_NOT_FOUND, null, $format);
        }

        $this->entityManager->remove($result);
        $this->entityManager->flush();

        return Utils::apiResponse(Response::HTTP_NO_CONTENT);
    }

    /**
     * @see ApiResultsCommandInterface
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
        // Puede crear un resultado sólo si tiene ROLE_ADMIN
        if (!$this->isGranted(self::ROLE_ADMIN)) {
            return Utils::errorMessage( // 403
                Response::HTTP_FORBIDDEN,
                '`Forbidden`: you don\'t have permission to access',
                $format
            );
        }
        $body = $request->getContent();
        $postData = json_decode((string) $body, true, 512, JSON_THROW_ON_ERROR);

        if (!isset($postData[Result::RESULT_ATTR], $postData[Result::USER_ID_ATTR])) {
            // 422 - Unprocessable Entity -> Faltan datos
            return Utils::errorMessage(Response::HTTP_UNPROCESSABLE_ENTITY, null, $format);
        }

        // hay datos -> procesarlos
        $user_exist = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy([ User::USER_ID_ATTR => $postData[Result::USER_ID_ATTR] ]);

        $result_exist = $this->entityManager
                ->getRepository(Result::class)
                ->findOneBy([ Result::RESULT_USER_ATTR => $user_exist ]);
        $messageResultExist = "Ya existe el resultado para el usuario que intenta agregar.";
        if ($result_exist instanceof Result) {    // 400 - Bad Request
            return Utils::errorMessage(Response::HTTP_BAD_REQUEST, $messageResultExist, $format);
        }

        // 201 - Created
        $result = new Result(
            strval($postData[Result::RESULT_ID_ATTR]),
            strval($postData[Result::RESULT_USER_ATTR]),
            new DateTime('now')
        );

        $this->entityManager->persist($result);
        $this->entityManager->flush();

        return Utils::apiResponse(
            Response::HTTP_CREATED,
            [ Result::RESULT_ATTR => $result ],
            $format,
            [
                'Location' => $request->getScheme() . '://' . $request->getHttpHost() .
                    ApiResultsQueryInterface::RUTA_API . '/' . $result->getId(),
            ]
        );
    }

    /**
     * @see ApiResultsCommandInterface
     *
     * @Route(
     *     path="/{resultId}.{_format}",
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
    public function putAction(Request $request, int $resultId): Response
    {
        $format = Utils::getFormat($request);
        // Puede editar un resultado sólo si tiene ROLE_ADMIN
        /** @var Result $result */
        //$user = $this->getUser();
        if (
            //($user->getId() !== $userId)
            //&&
            !$this->isGranted(self::ROLE_ADMIN)
        ) {
            return Utils::errorMessage( // 403
                Response::HTTP_FORBIDDEN,
                '`Forbidden`: you don\'t have permission to access',
                $format
            );
        }
        $body = (string) $request->getContent();
        $postData = json_decode($body, true);

        /** @var Rsult $result */
        $result = $this->entityManager
            ->getRepository(Result::class)
            ->find($resultId);

        if (!$result instanceof Result) {    // 404 - Not Found
            return Utils::errorMessage(Response::HTTP_NOT_FOUND, null, $format);
        }

        // Optimistic Locking (strong validation)
        $etag = md5((string) json_encode($result, JSON_THROW_ON_ERROR) . $result->getResult());
        if (!$request->headers->has('If-Match') || $etag != $request->headers->get('If-Match')) {
            return Utils::errorMessage(
                Response::HTTP_PRECONDITION_FAILED,
                'PRECONDITION FAILED: one or more conditions given evaluated to false',
                $format
            ); // 412
        }

        if (isset($postData[Result::RESULT_ID_ATTR])) {
            $result_exist = $this->entityManager
                ->getRepository(Result::class)
                ->findOneBy([ Result::RESULT_ID_ATTR => $postData[Result::RESULT_ID_ATTR] ]);

            if ($result_exist instanceof Result) {    // 400 - Bad Request
                return Utils::errorMessage(Response::HTTP_BAD_REQUEST, null, $format);
            }
            $result->setResult($postData[Result::RESULT_ID_ATTR]);
        }

        //verificacion de que existe el usuario antes de agregarlo en la modificacion
        if (isset($postData[Result::USER_ID_ATTR])) {
            $user_exist = $this->entityManager
                ->getRepository(User::class)
                ->findOneBy([ User::USER_ID_ATTR => $postData[Result::USER_ID_ATTR] ]);

            if ($user_exist instanceof User) {    // 400 - Bad Request
                return Utils::errorMessage(Response::HTTP_BAD_REQUEST, null, $format);
            }
            $result->setUser($postData[User::USER_ATTR]);
        }

        $this->entityManager->flush();

        return Utils::apiResponse(
            209,                        // 209 - Content Returned
            [ Result::RESULT_ATTR => $result ],
            $format
        );
    }
}
