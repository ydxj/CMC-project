<?php

namespace App\Http\Controllers;

use App\Models\EmploiDuTemps;
use App\Models\ModuleGroupeFormateur;
use Illuminate\Http\Request;

class EmploiDuTempsController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->query('limit');
        $offset = $request->query('offset');

        if ($limit === null && $offset === null) {
            return response()->json(EmploiDuTemps::all());
        }

        $query = EmploiDuTemps::query();

        if ($offset !== null) {
            $query->skip(max(0, (int) $offset));
        }

        if ($limit !== null) {
            $query->take(max(0, (int) $limit));
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date_seance' => 'required|date',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut',
            'id_groupe' => 'required|integer|exists:groupes,id',
            'id_module' => 'required|integer|exists:modules,id',
            'id_formateur' => 'required|integer|exists:formateurs,id',
            'id_salle' => 'required|integer|exists:salles,id',
        ]);

        $assignment = ModuleGroupeFormateur::firstOrCreate([
            'id_module' => $data['id_module'],
            'id_groupe' => $data['id_groupe'],
            'id_formateur' => $data['id_formateur'],
        ]);

        EmploiDuTemps::create([
            'date_seance' => $data['date_seance'],
            'heure_debut' => $data['heure_debut'],
            'heure_fin' => $data['heure_fin'],
            'id_module_groupe_formateur' => $assignment->id,
            'id_salle' => $data['id_salle'],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'date_seance' => $data['date_seance'],
                'heure_debut' => $data['heure_debut'],
                'heure_fin' => $data['heure_fin'],
                'id_groupe' => $data['id_groupe'],
                'id_module' => $data['id_module'],
                'id_formateur' => $data['id_formateur'],
                'id_salle' => $data['id_salle'],
            ],
        ]);
    }
}
