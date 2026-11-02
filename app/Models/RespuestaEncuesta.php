<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RespuestaEncuesta extends Model
{
    protected $table = 'tb_respuestas_encuesta';
    protected $primaryKey = 'id_respuesta';

    protected $fillable = [
        'id_encuesta',
        'id_usuario',
        'calificacion_general',
        'facilidad_uso',
        'calidad_mapa_3d',
        'comentarios',
    ];

    public function encuesta()
    {
        return $this->belongsTo(Encuesta::class, 'id_encuesta', 'id_encuesta');
    }
}
