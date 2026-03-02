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
            'id_formation' => 'required|integer|exists:filieres,id',
            'id_filiere' => 'required|integer|exists:filieres,id',
        ]);

        Groupe::create([
            'nom_groupe' => $data['nom_groupe'],
            'annee' => $data['annee'],
            'niveau' => $data['niveau'],
            'creneau' => $data['crenau'],
            'id_filiere' => $data['id_filiere'],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'nom_groupe' => $data['nom_groupe'],
                'annee' => $data['annee'],
                'niveau' => $data['niveau'],
                'creneau' => $data['crenau'],
                'id_filiere' => $data['id_filiere'],
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $groupe = Groupe::find($id);

        if (!$groupe) {
            return response()->json([
                'success' => false,
                'message' => 'Groupe not found',
            ], 404);
        }

        $data = $request->validate([
            'nom_groupe' => 'sometimes|required|string|max:100',
            'annee' => 'sometimes|required|string|max:20',
            'niveau' => 'sometimes|required|in:TS,T,Q,S,FQ,BP,PC',
            'crenau' => 'sometimes|required|in:CDJ,CDS',
            'id_filiere' => 'sometimes|required|integer|exists:filieres,id',
        ]);

        $groupe->update([
            'nom_groupe' => $data['nom_groupe'] ?? $groupe->nom_groupe,
            'annee' => $data['annee'] ?? $groupe->annee,
            'niveau' => $data['niveau'] ?? $groupe->niveau,
            'creneau' => $data['crenau'] ?? $groupe->creneau,
            'id_filiere' => $data['id_filiere'] ?? $groupe->id_filiere,
        ]);

        return response()->json([
            'success' => true,
            'data' => $groupe,
        ]);
    }

    public function destroy($id)
    {
        $groupe = Groupe::find($id);

        if (!$groupe) {
            return response()->json([
                'success' => false,
                'message' => 'Groupe not found',
            ], 404);
        }

        $groupe->delete();

        return response()->json([
            'success' => true,
            'message' => 'Groupe deleted successfully',
        ]);
    }
}
