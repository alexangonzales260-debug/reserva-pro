<?php

namespace App\Http\Middleware;

use App\Support\CurrentNegocio;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentNegocio
{
    /**
     * Set the active negocio from the authenticated owner's session.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user !== null && $user->negocio_id !== null) {
            CurrentNegocio::set($user->negocio_id);
        }

        return $next($request);
    }
}
