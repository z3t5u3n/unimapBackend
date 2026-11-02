<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LugarRecorrido extends Model
{
    protected $table = 'tb_lugares_recorridos';
    protected $primaryKey = 'id_recorrido';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'lugar_mesh',
        'origen_busqueda',
        'fecha_busqueda',
    ];

    public function usuario()
    {
        return $this->belongsTo(UsuarioGeneral::class, 'id_usuario', 'id_usuario');
    }
}
