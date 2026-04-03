<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmploiDuTemps extends Model
{
    protected $table = 'emploi_du_temps';

    protected $fillable = [
        'date_seance',
        'heure_debut',
        'heure_fin',
        'id_module_groupe_formateur',
        'id_module',
        'id_salle',
    ];

    public $timestamps = false;

    public function moduleGroupeFormateur()
    {
        return $this->belongsTo(ModuleGroupeFormateur::class, 'id_module_groupe_formateur');
    }

    public function module()
    {
        return $this->belongsTo(Module::class, 'id_module');
    }

    public function salle()
    {
        return $this->belongsTo(Salle::class, 'id_salle');
    }
}
