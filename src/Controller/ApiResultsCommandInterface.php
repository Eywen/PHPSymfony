<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiResultsController
 *
 * @package App\Controller
 *
 */
interface ApiResultsCommandInterface
{
    /**
     * DELETE Action<br>
     * Summary: Removes the Result resource.<br>
     * Notes: Deletes the result identified by &#x60;resultId&#x60;.
     *
     * @param int $result User id
     */
    public function deleteAction(Request $request, int $resultId): Response;

    /**
     * POST action<br>
     * Summary: Creates a Result resource.
     *
     * @param Request $request request
     */
    public function postAction(Request $request): Response;

    /**
     * PUT action<br>
     * Summary: Updates the Result resource.<br>
     * Notes: Updates the result identified by &#x60;resultId&#x60;.
     *
     * @param Request $request request
     * @param int $resultId User id
     */
    public function putAction(Request $request, int $resultId): Response;
}
