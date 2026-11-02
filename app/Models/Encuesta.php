<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Encuesta extends Model
{
    protected $table = 'tb_encuestas';
    protected $primaryKey = 'id_encuesta';

    protected $fillable = [
        'titulo',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function respuestas()
    {
        return $this->hasMany(RespuestaEncuesta::class, 'id_encuesta', 'id_encuesta');
    }
}
