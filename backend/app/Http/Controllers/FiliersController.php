<?php

namespace App\Http\Controllers;

use App\Models\Filiere;
use Illuminate\Http\Request;

class FiliersController extends Controller
{
    public function index()
    {
        return response()->json(Filiere::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom_formation' => 'sometimes|string|max:150',
            'nom_filiere' => 'sometimes|string|max:150',
            'id_pole' => 'required|integer|exists:poles,id',
        ]);

        $nomFiliere = $data['nom_formation'] ?? $data['nom_filiere'] ?? null;

        if ($nomFiliere === null) {
            return response()->json([
                'success' => false,
                'message' => 'nom_formation is required',
            ], 422);
        }

        Filiere::create([
            'nom_filiere' => $nomFiliere,
            'id_pole' => $data['id_pole'],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'nom_filiere' => $nomFiliere,
                'id_pole' => $data['id_pole'],
            ],
        ]);
    }
}
