<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiliereModulePhase extends Model
{
    protected $table = 'filiere_module_phases';

    protected $fillable = [
        'id_filiere',
        'id_module',
        'id_previous_module',
    ];

    public function filiere()
    {
        return $this->belongsTo(Filiere::class, 'id_filiere');
    }

    public function module()
    {
        return $this->belongsTo(Module::class, 'id_module');
    }

    public function previousModule()
    {
        return $this->belongsTo(Module::class, 'id_previous_module');
    }
}
