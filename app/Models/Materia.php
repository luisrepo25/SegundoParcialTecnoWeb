<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Materia extends Model
{
    protected $table = 'materias';
    protected $primaryKey = 'id_materia';
    public $timestamps = false;

    protected $fillable = [
        'codigo', 'nombre', 'duracion_meses',
        'costo_mensual', 'creditos', 'activo', 'id_materia_requisito',
    ];

    public function requisito()
    {
        return $this->belongsTo(Materia::class, 'id_materia_requisito', 'id_materia');
    }
}
