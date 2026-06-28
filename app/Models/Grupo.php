<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    protected $table      = 'grupos';
    protected $primaryKey = 'id_oferta';
    public    $timestamps = false;

    protected $fillable = [
        'id_materia', 'id_aula', 'id_periodo', 'id_profesor',
        'id_horario', 'vacantes_max', 'vacantes_ocupadas', 'activo', 'codigo_grupo',
    ];
}
