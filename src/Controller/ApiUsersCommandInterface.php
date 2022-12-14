<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiUsersController
 *
 * @package App\Controller
 *
 */
interface ApiUsersCommandInterface
{
    /**
     * DELETE Action<br>
     * Summary: Removes the User resource.<br>
     * Notes: Deletes the user identified by &#x60;userId&#x60;.
     *
     * @param int $userId User id
     */
    public function deleteAction(Request $request, int $userId): Response;

    /**
     * POST action<br>
     * Summary: Creates a User resource.
     *
     * @param Request $request request
     */
    public function postAction(Request $request): Response;

    /**
     * PUT action<br>
     * Summary: Updates the User resource.<br>
     * Notes: Updates the user identified by &#x60;userId&#x60;.
     *
     * @param Request $request request
     * @param int $userId User id
     */
    public function putAction(Request $request, int $userId): Response;
}
