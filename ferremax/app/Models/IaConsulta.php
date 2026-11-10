<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IaConsulta extends Model
{
    protected $table = 'ia_consultas';
    protected $guarded = [];
    protected $casts = ['favorito' => 'boolean', 'ultimo_uso' => 'datetime'];
}
