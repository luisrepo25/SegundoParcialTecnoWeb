<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionSitio extends Model
{
    protected $table = 'configuracion_sitio';

    protected $fillable = [
        'nombre_institucion',
        'descripcion',
        'email',
        'telefono_1',
        'telefono_2',
        'direccion',
        'facebook_url',
        'instagram_url',
        'youtube_url',
        'logo_path',
    ];

    // Singleton: siempre id=1
    public static function actual(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'nombre_institucion' => 'Instituto San Pablo',
        ]);
    }

    // URL pública del logo
    public function getLogoUrlAttribute(): string
    {
        if ($this->logo_path && file_exists(public_path('storage/' . $this->logo_path))) {
            return asset('storage/' . $this->logo_path);
        }
        return asset('images/logo.png');
    }
}
