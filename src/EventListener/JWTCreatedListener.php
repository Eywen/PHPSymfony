<?php

/**
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://miw.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace App\EventListener;

use App\Entity\User;
use DateTime;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;

/**
 * Class JWTCreatedListener
 */
class JWTCreatedListener
{
    /**
     * @throws Exception
     */
    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $payload = $event->getData();
        /** @var User $user */
        $user = $event->getUser();

        // token expira en 2 horas
        $expiration = new DateTime('+2 hours');
        $payload['exp'] = $expiration->getTimestamp();

        $payload['id'] = $user->getId();
        $payload['email'] = $user->getEmail();

        $event->setData($payload);
    }
}
