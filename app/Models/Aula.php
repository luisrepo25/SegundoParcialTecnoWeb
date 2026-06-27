<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aula extends Model
{
    protected $table = 'aulas';
    protected $primaryKey = 'id_aula';
    public $timestamps = false;

    protected $fillable = [
        'nombre', 'capacidad', 'ubicacion', 'tipo', 'activo',
    ];
}
