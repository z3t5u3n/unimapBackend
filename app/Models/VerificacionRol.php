<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerificacionRol extends Model
{
    protected $table = 'tb_verificaciones_rol';
    protected $primaryKey = 'id_verificacion';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'correo_institucional',
        'nuevo_tipo_usuario',
        'id_carrera',
        'id_semestre',
        'token',
        'expira_en',
        'utilizado',
        'fecha_creacion',
    ];

    protected $casts = [
        'expira_en' => 'datetime',
        'fecha_creacion' => 'datetime',
        'utilizado' => 'boolean',
    ];

    public function usuario()
    {
        return $this->belongsTo(UsuarioGeneral::class, 'id_usuario', 'id_usuario');
    }
}
