<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Etablissement extends Model
{
    protected $table = 'etablissements';

    protected $fillable = [
        'nom_etablissement',
        'type_etablissement',
        'region',
        'id_directeur',
    ];

    public function directeur()
    {
        return $this->belongsTo(User::class, 'id_directeur');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'id_etablissement');
    }

    public function poles()
    {
        return $this->hasMany(Pole::class, 'id_etablissement');
    }

    public function salles()
    {
        return $this->hasMany(Salle::class, 'id_etablissement');
    }

    public function filieres()
    {
        return $this->hasMany(Filiere::class, 'id_etablissement');
    }

    public function formateurs()
    {
        return $this->hasMany(Formateur::class, 'id_etablissement');
    }
}