<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ContadorVisitas
{
    public function handle(Request $request, Closure $next): Response
    {
        // Solo contar peticiones GET que renderizan una página (no parciales de Inertia)
        if ($request->isMethod('GET') && !$request->hasHeader('X-Inertia-Partial-Component')) {
            $pagina = $request->route()?->getName() ?? $request->path();

            try {
                DB::table('page_views')->upsert(
                    ['pagina' => $pagina, 'visitas' => 1],
                    ['pagina'],
                    ['visitas' => DB::raw('page_views.visitas + 1')]
                );
                $visitas = DB::table('page_views')->where('pagina', $pagina)->value('visitas') ?? 1;
            } catch (\Throwable) {
                $visitas = 0;
            }

            $request->attributes->set('visitas_pagina', $visitas);
        }

        return $next($request);
    }
}
