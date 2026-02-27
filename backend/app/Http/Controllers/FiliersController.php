<?php

namespace App\Http\Controllers;

use App\Models\Filiere;
use Illuminate\Http\Request;

class FiliersController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->query('limit');
        $offset = $request->query('offset');

        if ($limit === null && $offset === null) {
            return response()->json(Filiere::all());
        }

        $query = Filiere::query();

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
