<?php

namespace App\Http\Controllers;

use App\Models\Salle;
use Illuminate\Http\Request;

class SallesController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->query('limit');
        $offset = $request->query('offset');

        if ($limit === null && $offset === null) {
            return response()->json(Salle::with('pole')->get());
        }

        $query = Salle::with('pole');

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
            'nom_salle' => 'required|string|max:100',
            'type_salle' => 'required|in:SALLE,ATELIER',
            'id_pole' => 'required|integer|exists:poles,id',
        ]);

        Salle::create($data);

        return response()->json([
            'success' => true,
            'data' => [
                'nom_salle' => $data['nom_salle'],
                'type_salle' => $data['type_salle'],
                'id_pole' => $data['id_pole'],
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $salle = Salle::find($id);

        if (!$salle) {
            return response()->json([
                'success' => false,
                'message' => 'Salle not found',
            ], 404);
        }

        $data = $request->validate([
            'nom_salle' => 'sometimes|required|string|max:100',
            'type_salle' => 'sometimes|required|in:SALLE,ATELIER',
            'id_pole' => 'sometimes|required|integer|exists:poles,id',
        ]);

        $salle->update([
            'nom_salle' => $data['nom_salle'] ?? $salle->nom_salle,
            'type_salle' => $data['type_salle'] ?? $salle->type_salle,
            'id_pole' => $data['id_pole'] ?? $salle->id_pole,
        ]);

        return response()->json([
            'success' => true,
            'data' => $salle,
        ]);
    }

    public function destroy($id)
    {
        $salle = Salle::find($id);

        if (!$salle) {
            return response()->json([
                'success' => false,
                'message' => 'Salle not found',
            ], 404);
        }

        $salle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Salle deleted successfully',
        ]);
    }
}
