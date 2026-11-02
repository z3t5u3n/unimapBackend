<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    protected $table = 'tb_admins';
    protected $primaryKey = 'id_admin';

    protected $fillable = [
        'username',
        'nombre',
        'password',
        'rol',
        'activo',
        'ultimo_acceso',
    ];

    protected $hidden = [
        'password',
    ];

    public function sesiones()
    {
        return $this->hasMany(AdminSesion::class, 'id_admin', 'id_admin');
    }

    public function auditorias()
    {
        return $this->hasMany(AdminAuditoria::class, 'id_admin', 'id_admin');
    }
}
