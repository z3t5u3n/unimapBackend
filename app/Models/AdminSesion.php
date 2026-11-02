<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminSesion extends Model
{
    protected $table = 'tb_admin_sesiones';
    protected $primaryKey = 'id_sesion';

    protected $fillable = [
        'id_admin',
        'username',
        'ip_acceso',
        'dispositivo',
        'fecha_inicio',
        'fecha_cierre',
        'estado',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'id_admin', 'id_admin');
    }
}
