<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pole extends Model
{
    protected $table = 'poles';

    protected $fillable = [
        'id_etablissement',
        'nom_pole',
        'id_chef',
    ];

    public $timestamps = false;

    public function chef()
    {
        return $this->belongsTo(User::class, 'id_chef');
    }

    public function salles()
    {
        return $this->hasMany(Salle::class, 'id_pole');
    }

    public function filieres()
    {
        return $this->hasMany(Filiere::class, 'id_pole');
    }

    public function formateurs()
    {
        return $this->belongsToMany(Formateur::class, 'formateur_pole', 'id_pole', 'id_formateur');
    }

    public function etablissement()
    {
        return $this->belongsTo(Etablissement::class, 'id_etablissement');
    }
}
