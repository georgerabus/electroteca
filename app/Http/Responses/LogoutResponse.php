<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LogoutResponse implements LogoutResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request): Response
    {
        // Clear JWT cookies by setting them to expire immediately
        $response = redirect('/');
        
        // Clear access_token cookie
        $response->cookie('access_token', '', -1, '/', null, true, true, false, 'Strict');
        
        // Clear refresh_token cookie
        $response->cookie('refresh_token', '', -1, '/', null, true, true, false, 'Strict');
        
        return $response;
    }
}

