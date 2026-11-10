<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IaConfig extends Model
{
    protected $table = 'ia_configs';
    protected $guarded = [];
    protected $casts = ['resumen' => 'boolean'];
    protected $hidden = ['clave'];
}
