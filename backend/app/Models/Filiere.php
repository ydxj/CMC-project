<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Filiere extends Model
{
    protected $table = 'filieres';

    protected $fillable = [
        'id_secteur',
        'nom_filiere',
        'id_pole',
    ];

    public $timestamps = false;

    public function pole()
    {
        return $this->belongsTo(Pole::class, 'id_pole');
    }

    public function groupes()
    {
        return $this->hasMany(Groupe::class, 'id_filiere');
    }

    public function modules()
    {
        return $this->hasMany(Module::class, 'id_filiere');
    }

    public function etablissement()
    {
        return $this->belongsTo(Etablissement::class, 'id_etablissement');
    }

    public function secteur()
    {
        return $this->belongsTo(Secteur::class, 'id_secteur');
    }
}
