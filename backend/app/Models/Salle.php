<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Salle extends Model
{
    protected $table = 'salles';

    protected $fillable = [
        'nom_salle',
        'type_salle',
        'id_pole',
    ];

    public $timestamps = false;

    public function pole()
    {
        return $this->belongsTo(Pole::class, 'id_pole');
    }

    public function emploisDuTemps()
    {
        return $this->hasMany(EmploiDuTemps::class, 'id_salle');
    }
}
