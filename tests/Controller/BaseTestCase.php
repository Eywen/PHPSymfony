<?php

namespace App\Tests\Controller;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Faker\Factory as FakerFactoryAlias;
use Faker\Generator as FakerGeneratorAlias;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Throwable;

/**
 * Class BaseTestCase
 *
 * @package App\Tests\Controller
 */
class BaseTestCase extends WebTestCase
{
    /** @var array<string,mixed> $headers  */
    private static array $headers;

    protected static KernelBrowser $client;

    protected static FakerGeneratorAlias $faker;

    /** @var array<string,mixed> $role_user Role User */
    protected static array $role_user;

    /** @var array<string,mixed> $role_admin Role Admin */
    protected static array $role_admin;

    /**
     * This method is called before the first test of this test class is run.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        self::$headers = [];
        self::$client = static::createClient();
        self::$faker = FakerFactoryAlias::create('es_ES');

        // Role user
        self::$role_user = [
            User::EMAIL_ATTR => $_ENV['ROLE_USER_EMAIL'],
            User::PASSWD_ATTR => $_ENV['ROLE_USER_PASSWD'],
        ];

        // Role admin
        self::$role_admin = [
            User::EMAIL_ATTR => $_ENV['ADMIN_USER_EMAIL'],
            User::PASSWD_ATTR => $_ENV['ADMIN_USER_PASSWD'],
        ];

        try { // Regenera las tablas con todas las entidades mapeadas
            /** @var EntityManagerInterface $e_manager */
            $e_manager = self::bootKernel()
                ->getContainer()
                ->get('doctrine')
                ->getManager();

            $metadata = $e_manager
                ->getMetadataFactory()
                ->getAllMetadata();
            $sch_tool = new SchemaTool($e_manager);
            $sch_tool->dropDatabase();
            $sch_tool->updateSchema($metadata);
        } catch (Throwable $e) {
            fwrite(STDERR, 'EXCEPCIÓN: ' . $e->getCode() . ' - ' . $e->getMessage());
            exit(1);
        }

        // Obtener servicio Password Hasher
        /** @var UserPasswordHasher $passwordHasher */
        $passwordHasher = self::bootKernel()
            ->getContainer()
            ->get('security.user_password_hasher');

        // Insertar usuarios (roles admin y user)
        $role_admin = new User(
            self::$role_admin[User::EMAIL_ATTR],
            self::$role_admin[User::PASSWD_ATTR],
            [ 'ROLE_ADMIN' ]
        );
        // hash the password (based on the security.yaml config for the $user class)
        $hashedPassword = $passwordHasher->hashPassword(
            $role_admin,
            self::$role_admin[User::PASSWD_ATTR]
        );
        $role_admin->setPassword($hashedPassword);

        $role_user = new User(
            self::$role_user[User::EMAIL_ATTR],
            self::$role_user[User::PASSWD_ATTR]
        );
        // hash the password (based on the security.yaml config for the $user class)
        $hashedPassword = $passwordHasher->hashPassword(
            $role_user,
            self::$role_user[User::PASSWD_ATTR]
        );
        $role_user->setPassword($hashedPassword);

        $e_manager->persist($role_admin);
        $e_manager->persist($role_user);
        $e_manager->flush();
    }

    /**
     * Obtiene el JWT directamente de la ruta correspondiente
     *
     * @param   string  $useremail user email
     * @param   string  $password user password
     * @return  array<string,mixed>   cabeceras con el token obtenido
     */
    protected function getTokenHeaders(
        string $useremail,
        string $password
    ): array {
        $data = [
            User::EMAIL_ATTR => $useremail,
            User::PASSWD_ATTR => $password
        ];

        self::$client->request(
            Request::METHOD_POST,
            '/api/v1/login_check',
            [ ],
            [ ],
            [ 'CONTENT_TYPE' => 'application/json' ],
            (string) json_encode($data)
        );
        $response = self::$client->getResponse();
        // $json_resp = json_decode($response->getContent(), true);
        // (HTTP headers are referenced with HTTP_ prefix as PHP does)
        self::$headers = [
            'HTTP_ACCEPT'        => 'application/json',
            'HTTP_Authorization' => $response->headers->get('Authorization'),
        ];

        return self::$headers;
    }

    /**
     * Test response error messages
     *
     * @param Response $response
     * @param int $errorCode
     */
    protected function checkResponseErrorMessage(Response $response, int $errorCode): void
    {
        self::assertSame($errorCode, $response->getStatusCode());
        $r_body = (string) $response->getContent();
        self::assertJson($r_body);
        try {
            $r_data = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
            self::assertArrayHasKey(Message::CODE_ATTR, $r_data);
            self::assertArrayHasKey(Message::MESSAGE_ATTR, $r_data);
            self::assertSame($errorCode, $r_data[Message::CODE_ATTR]);
            // self::assertStringContainsString(
            //    strtolower(Response::$statusTexts[$errorCode]),
            //    strtolower($r_data[Message::MESSAGE_ATTR])
            // );
        } catch (Throwable $exception) {
            die('ERROR: ' . $exception->getMessage());
        }
    }
}
