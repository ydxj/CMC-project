<?php

namespace App\Http\Controllers;

use App\Models\Groupe;
use Illuminate\Http\Request;

class GroupesController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->query('limit');
        $offset = $request->query('offset');

        if ($limit === null && $offset === null) {
            return response()->json(Groupe::all());
        }

        $query = Groupe::query();

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
            'nom_groupe' => 'required|string|max:100',
            'annee' => 'required|string|max:20',
            'niveau' => 'required|in:TS,T,Q,S,FQ,BP,PC',
            'crenau' => 'required|in:CDJ,CDS',
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

        Groupe::create([
            'nom_groupe' => $data['nom_groupe'],
            'annee' => $data['annee'],
            'niveau' => $data['niveau'],
            'creneau' => $data['crenau'],
            'id_filiere' => $idFiliere,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'nom_groupe' => $data['nom_groupe'],
                'annee' => $data['annee'],
                'niveau' => $data['niveau'],
                'creneau' => $data['crenau'],
                'id_filiere' => $idFiliere,
            ],
        ]);
    }
}
