<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoUsuario extends Model
{
    protected $table = 'tb_tipo_usuario';
    protected $primaryKey = 'id_tipo';
    public $timestamps = false;
}
