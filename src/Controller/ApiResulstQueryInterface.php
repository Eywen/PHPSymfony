<?php


namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface ApiResultQueryInterface
 *
 * @package App\Controller
 *
 */
interface ApiResulstQueryInterface
{
    public final const RUTA_API = '/api/v1/results';

    /**
     * CGET Action<br>
     * Summary: Retrieves the collection of Result resources.<br>
     * Notes: Returns all results from the system that the user has access to.
     */
    public function cgetAction(Request $request): Response;

    /**
     * GET Action<br>
     * Summary: Retrieves a Result resource based on a single ID.<br>
     * Notes: Returns the user identified by &#x60;userId&#x60;.
     *
     * @param int $resultId Result id
     */
    public function getAction(Request $request, int $resultId): Response;

    /**
     * Summary: Provides the list of HTTP supported methods<br>
     * Notes: Return a &#x60;Allow&#x60; header with a list of HTTP supported methods.
     *
     * @param int $resultId Result id
     */
    public function optionsAction(int $resultId): Response;
}