<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Groupe extends Model
{
    protected $table = 'groupes';

    protected $fillable = [
        'nom_groupe',
        'annee',
        'niveau',
        'creneau',
        'id_filiere',
        'id_etablissement',
    ];

    public $timestamps = false;

    public function filiere()
    {
        return $this->belongsTo(Filiere::class, 'id_filiere');
    }

    public function moduleGroupeFormateurs()
    {
        return $this->hasMany(ModuleGroupeFormateur::class, 'id_groupe');
    }
}
