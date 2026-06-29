<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    protected $table = 'carreras';
    protected $primaryKey = 'id_carrera';
    public $timestamps = false;

    protected $fillable = [
        'codigo', 'nombre', 'descripcion',
        'tipo', 'modalidad', 'max_materias', 'duracion_niveles',
        'costo_carrera_completa', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo'                 => 'boolean',
            'costo_carrera_completa' => 'decimal:2',
            'duracion_niveles'       => 'integer',
            'max_materias'           => 'integer',
        ];
    }
}
