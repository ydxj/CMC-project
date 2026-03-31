<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleGroupeFormateur extends Model
{
    protected $table = 'module_groupe_formateur';

    protected $fillable = [
        'id_module',
        'id_groupe',
        'id_formateur',
        'id_etablissement',
    ];

    public $timestamps = false;

    public function module()
    {
        return $this->belongsTo(Module::class, 'id_module');
    }

    public function groupe()
    {
        return $this->belongsTo(Groupe::class, 'id_groupe');
    }

    public function formateur()
    {
        return $this->belongsTo(Formateur::class, 'id_formateur');
    }

    public function etablissement()
    {
        return $this->belongsTo(Etablissement::class, 'id_etablissement');
    }

    public function emploisDuTemps()
    {
        return $this->hasMany(EmploiDuTemps::class, 'id_module_groupe_formateur');
    }
}
