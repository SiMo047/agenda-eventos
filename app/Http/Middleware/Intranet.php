<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Intranet
{
    /**
     * Deja pasar solo a los usuarios con alguno de los roles indicados.
     * Uso: intranet:Administrador  o  intranet:Administrador,CreadorEventos
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user() || !in_array($request->user()->rol, $roles)) {

            return redirect("/");
        }
        return $next($request);
    }
}
