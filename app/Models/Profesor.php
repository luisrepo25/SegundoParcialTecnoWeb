<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profesor extends Model
{
    protected $table = 'profesores';
    protected $primaryKey = 'id_profesor';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario', 'legajo_profesor', 'especialidad',
        'titulo_maximo', 'fecha_contratacion', 'sueldo_base', 'observaciones', 'archivo_cv',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
