<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// Modelo de la tb_usuario_general
class UsuarioGeneral extends Model
{
    //Estructura de tb_usuario_general
    protected $table = "tb_usuario_general";
    protected $primaryKey = "id_general";
    public $timestamps = false;

    // los campos segundo la tabla
    protected $fillable = [
        'id_usuario',
        'nombres',
        'apellidos',
        'tipo_documento',
        'numero_documento',
        'correo_institucional',
        'tipo_usuario',
        'fecha_sys',
    ];
}
