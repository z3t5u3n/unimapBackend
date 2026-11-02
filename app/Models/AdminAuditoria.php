<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAuditoria extends Model
{
    protected $table = 'tb_admin_auditoria';
    protected $primaryKey = 'id_auditoria';
    public $timestamps = false;
    const CREATED_AT = 'fecha_accion';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_admin',
        'admin_username',
        'tipo_accion',
        'modulo',
        'descripcion',
        'detalles_json',
        'ip_origen',
        'fecha_accion',
    ];

    protected $casts = [
        'detalles_json' => 'array',
        'fecha_accion' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'id_admin', 'id_admin');
    }
}
