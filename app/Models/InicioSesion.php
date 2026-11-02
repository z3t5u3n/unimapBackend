<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InicioSesion extends Model
{
    protected $table = 'tb_inicio_sesion';
    protected $primaryKey = 'id_sesion';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'ip_acceso',
        'dispositivo',
        'fecha_ingreso',
    ];

    public function usuario()
    {
        return $this->belongsTo(UsuarioGeneral::class, 'id_usuario', 'id_usuario');
    }
}
