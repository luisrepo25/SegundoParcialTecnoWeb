<?php

namespace App\Http\Middleware;

use App\Models\ConfiguracionSitio;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'asset_url'   => rtrim(asset(''), '/'),
            'sitio'       => fn () => $this->sitioConfig(),
            'flash' => [
                'credenciales' => $request->session()->get('credenciales'),
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }

    private function sitioConfig(): array
    {
        try {
            $c = ConfiguracionSitio::actual();
            return [
                'nombre_institucion' => $c->nombre_institucion,
                'descripcion'        => $c->descripcion,
                'email'              => $c->email,
                'telefono_1'         => $c->telefono_1,
                'telefono_2'         => $c->telefono_2,
                'direccion'          => $c->direccion,
                'facebook_url'       => $c->facebook_url,
                'instagram_url'      => $c->instagram_url,
                'youtube_url'        => $c->youtube_url,
                'logo_url'           => $c->logo_url,
            ];
        } catch (\Throwable) {
            return ['nombre_institucion' => 'Instituto San Pablo', 'logo_url' => asset('images/logo.png')];
        }
    }
}
