<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estudiante extends Model
{
    protected $table = 'tb_estudiante';
    protected $primaryKey = 'id_estudiante';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'correo_institucional',
        'id_carrera',
        'id_semestre',
        'fecha_registro',
    ];

    protected $casts = [
        'fecha_registro' => 'datetime',
        'id_carrera' => 'integer',
        'id_semestre' => 'integer',
    ];

    public function usuario()
    {
        return $this->belongsTo(UsuarioGeneral::class, 'id_usuario', 'id_usuario');
    }

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'id_carrera', 'id_carrera');
    }

    public function semestre()
    {
        return $this->belongsTo(Semestre::class, 'id_semestre', 'id_semestres');
    }
}
