<?php

namespace App\Http\Controllers\Propietario;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionSitio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ConfiguracionSitioController extends Controller
{
    public function index()
    {
        $config = ConfiguracionSitio::actual();

        return Inertia::render('Propietario/ConfiguracionSitio', [
            'config' => [
                'nombre_institucion' => $config->nombre_institucion,
                'descripcion'        => $config->descripcion,
                'email'              => $config->email,
                'telefono_1'         => $config->telefono_1,
                'telefono_2'         => $config->telefono_2,
                'direccion'          => $config->direccion,
                'facebook_url'       => $config->facebook_url,
                'instagram_url'      => $config->instagram_url,
                'youtube_url'        => $config->youtube_url,
                'logo_url'           => $config->logo_url,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'nombre_institucion' => 'required|string|max:150',
            'descripcion'        => 'nullable|string|max:500',
            'email'              => 'nullable|email|max:150',
            'telefono_1'         => 'nullable|string|max:30',
            'telefono_2'         => 'nullable|string|max:30',
            'direccion'          => 'nullable|string|max:200',
            'facebook_url'       => 'nullable|url|max:300',
            'instagram_url'      => 'nullable|url|max:300',
            'youtube_url'        => 'nullable|url|max:300',
            'logo'               => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
        ]);

        $config = ConfiguracionSitio::actual();

        if ($request->hasFile('logo')) {
            // Borrar logo anterior si existe
            if ($config->logo_path) {
                Storage::disk('public')->delete($config->logo_path);
            }
            $path = $request->file('logo')->store('sitio', 'public');
            $config->logo_path = $path;
        }

        $config->fill([
            'nombre_institucion' => $data['nombre_institucion'],
            'descripcion'        => $data['descripcion'] ?? null,
            'email'              => $data['email'] ?? null,
            'telefono_1'         => $data['telefono_1'] ?? null,
            'telefono_2'         => $data['telefono_2'] ?? null,
            'direccion'          => $data['direccion'] ?? null,
            'facebook_url'       => $data['facebook_url'] ?? null,
            'instagram_url'      => $data['instagram_url'] ?? null,
            'youtube_url'        => $data['youtube_url'] ?? null,
        ])->save();

        return back()->with('success', 'Configuración guardada correctamente.');
    }
}
