<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionExist
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $session = DB::connection('account')->table(config('session.table'))->where('id', $request->session()->getId())->first();

        if (! DB::connection('account')->table(config('session.table'))->where('id', $session?->group)->where('group', $session?->group)->where('user_id', $request->user()->id)->exists()) {
            auth()->logout();

            return redirect()->route('login');
        }

        return $next($request);
    }
}
