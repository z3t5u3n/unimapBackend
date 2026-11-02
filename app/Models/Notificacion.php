<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $table = 'tb_notificaciones';
    protected $primaryKey = 'id_notificacion';

    protected $fillable = [
        'titulo',
        'mensaje',
        'tipo',
        'activo',
        'creado_por',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];
}
