<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockSpecialUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (strtolower($request->user()?->email ?? '') === 'd.grazule@saulesaptieka.lv') {
            abort(403);
        }

        return $next($request);
    }
}

