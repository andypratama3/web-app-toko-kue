<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $user = $request->user();

        if ($user->hasRole('admin')) {
            $redirectUrl = route('admin.dashboard', ['region' => $user->region]);
        } elseif ($user->hasRole('kurir')) {
            $redirectUrl = route('kurir.dashboard', ['region' => $user->region]);
        } else {
            // Fallback ke dashboard default jika role tidak dikenali
            $redirectUrl = config('fortify.home');
        }

        return $request->wantsJson()
                    ? new JsonResponse(['two_factor' => false])
                    : redirect()->intended($redirectUrl);
    }
}
