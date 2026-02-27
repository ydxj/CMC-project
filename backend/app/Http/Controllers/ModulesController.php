<?php

namespace App\Http\Controllers;

use App\Models\Module;
use Illuminate\Http\Request;

class ModulesController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->query('limit');
        $offset = $request->query('offset');

        if ($limit === null && $offset === null) {
            return response()->json(Module::all());
        }

        $query = Module::query();

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
            'data' => [
                'nom_module' => $data['nom_module'],
                'volume_horaire' => $data['volume_horaire'],
                'id_filiere' => $idFiliere,
            ],
        ]);
    }
}
