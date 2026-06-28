<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cronograma extends Model
{
    protected $table = 'cronogramas';
    protected $primaryKey = 'id_cronograma';
    public $timestamps = false;

    protected $fillable = [
        'id_periodo', 'nombre', 'tipo_periodo',
        'fecha_inicio', 'fecha_fin', 'activo', 'modalidad',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'activo' => 'boolean',
    ];

    protected $appends = ['estado', 'alcance'];

    public function getEstadoAttribute(): string
    {
        if (!$this->activo) {
            return 'INACTIVO';
        }

        $now = now()->startOfDay();
        $inicio = \Carbon\Carbon::parse($this->fecha_inicio)->startOfDay();
        $fin = \Carbon\Carbon::parse($this->fecha_fin)->startOfDay();

        if ($now->gt($fin)) {
            return 'CERRADA';
        }

        if ($now->lt($inicio)) {
            return 'PRÓXIMA';
        }

        return 'ABIERTA';
    }

    public function getAlcanceAttribute(): string
    {
        $labels = [
            'semestral'  => 'Semestral',
            'mensual'    => 'Mensual',
            'anual'      => 'Anual',
            'intensivo'  => 'Intensivo',
        ];

        return $this->modalidad ? ($labels[$this->modalidad] ?? strtoupper($this->modalidad)) : 'GLOBAL';
    }
}
