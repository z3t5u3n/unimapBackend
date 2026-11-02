<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReporteMapa extends Model
{
    protected $table = 'tb_reportes_mapa';
    protected $primaryKey = 'id_reporte';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'tipo_error',
        'lugar_afectado',
        'descripcion',
        'estado',
        'fecha_reporte',
    ];

    public function usuario()
    {
        return $this->belongsTo(UsuarioGeneral::class, 'id_usuario', 'id_usuario');
    }
}
