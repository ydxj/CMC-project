<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Formateur extends Model
{
    protected $table = 'formateurs';

    protected $fillable = [
        'id_user',
        'id_etablissement',
        'type_formateur',
        'MHS',
        'NBR_semaine',
    ];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function poles()
    {
        return $this->belongsToMany(Pole::class, 'formateur_pole', 'id_formateur', 'id_pole');
    }

    public function etablissement()
    {
        return $this->belongsTo(Etablissement::class, 'id_etablissement');
    }

    public function moduleGroupeFormateurs()
    {
        return $this->hasMany(ModuleGroupeFormateur::class, 'id_formateur');
    }
}
