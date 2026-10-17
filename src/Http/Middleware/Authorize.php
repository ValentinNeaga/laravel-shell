<?php

namespace Jakyeru\LaravelShell\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class Authorize
{
    /**
     * Handle the incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Gate::allows('viewLaravelShell'), 403);

        return $next($request);
    }
}
