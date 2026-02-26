<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $table = 'modules';

    protected $fillable = [
        'nom_module',
        'volume_horaire',
        'id_filiere',
    ];

    public $timestamps = false;

    public function filiere()
    {
        return $this->belongsTo(Filiere::class, 'id_filiere');
    }

    public function moduleGroupeFormateurs()
    {
        return $this->hasMany(ModuleGroupeFormateur::class, 'id_module');
    }
}
