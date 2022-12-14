<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface ApiUsersQueryInterface
 *
 * @package App\Controller
 *
 */
interface ApiUsersQueryInterface
{
    public final const RUTA_API = '/api/v1/users';

    /**
     * CGET Action<br>
     * Summary: Retrieves the collection of User resources.<br>
     * Notes: Returns all users from the system that the user has access to.
     */
    public function cgetAction(Request $request): Response;

    /**
     * GET Action<br>
     * Summary: Retrieves a User resource based on a single ID.<br>
     * Notes: Returns the user identified by &#x60;userId&#x60;.
     *
     * @param int $userId User id
     */
    public function getAction(Request $request, int $userId): Response;

    /**
     * Summary: Provides the list of HTTP supported methods<br>
     * Notes: Return a &#x60;Allow&#x60; header with a list of HTTP supported methods.
     *
     * @param  int $userId User id
     */
    public function optionsAction(int $userId): Response;
}
