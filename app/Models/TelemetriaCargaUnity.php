<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelemetriaCargaUnity extends Model
{
    use HasFactory;

    protected $table = 'tb_telemetria_cargas_unity';
    protected $primaryKey = 'id_telemetria';

    protected $fillable = [
        'id_usuario',
        'nombre_usuario',
        'tiempo_carga_segundos',
        'tipo_conexion',
        'operador',
        'modelo_dispositivo',
        'ip_origen',
        'user_agent',
    ];

    protected $casts = [
        'tiempo_carga_segundos' => 'float',
    ];
}
