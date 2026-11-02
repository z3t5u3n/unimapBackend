<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tester extends Model
{
    use HasFactory;

    protected $table = 'tb_testers';
    protected $primaryKey = 'id_tester';

    protected $fillable = [
        'username',
        'nombre',
        'password',
        'dispositivo',
        'observaciones',
        'activo',
        'ultimo_acceso',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'ultimo_acceso' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
