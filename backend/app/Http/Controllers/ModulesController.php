<?php

namespace App\Http\Controllers;

use App\Models\Module;
use Illuminate\Http\Request;

class ModulesController extends Controller
{
    public function index()
    {
        return response()->json(Module::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom_module' => 'required|string|max:150',
            'volume_horaire' => 'required|integer|min:1',
            'id_formation' => 'sometimes|integer|exists:filieres,id',
            'id_filiere' => 'sometimes|integer|exists:filieres,id',
        ]);

        $idFiliere = $data['id_formation'] ?? $data['id_filiere'] ?? null;

        if ($idFiliere === null) {
            return response()->json([
                'success' => false,
                'message' => 'id_formation is required',
            ], 422);
        }

        Module::create([
            'nom_module' => $data['nom_module'],
            'volume_horaire' => $data['volume_horaire'],
            'id_filiere' => $idFiliere,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Module created successfully',
        ]);
    }
}
