<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionModulo extends Model
{
    use HasFactory;

    protected $table = 'tb_configuracion_modulos';
    protected $primaryKey = 'id_config';

    protected $fillable = [
        'clave',
        'mostrar_mapa_3d',
        'mostrar_mapa_gps',
        'mostrar_recorridos',
    ];

    protected $casts = [
        'mostrar_mapa_3d' => 'boolean',
        'mostrar_mapa_gps' => 'boolean',
        'mostrar_recorridos' => 'boolean',
    ];
}
