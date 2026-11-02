<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

//Modelo de la tb_documentos

class Documento extends Model
{
    // Estructura de la tb_documentos
    protected $table = "tb_documentos";
    protected $primaryKey = "id_documento";
    public $timestamps = false;
}
