<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Semestre extends Model
{
    protected $table = 'tb_semestre';
    protected $primaryKey = 'id_semestres';
    public $timestamps = false;

    protected $fillable = [
        'semestre',
    ];
}
