<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends administrators to the dashboard and customers to their account after login or email verification.
 */
class HomeRedirectResponse implements LoginResponse, VerifyEmailResponse
{
    /**
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse(status: 204);
        }

        $home = $request->user()?->isAdmin()
            ? route('admin.dashboard')
            : config('fortify.home');

        return redirect()->intended($home);
    }
}
