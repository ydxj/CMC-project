<?php

namespace App\Http\Controllers;

use App\Models\Pole;
use Illuminate\Http\Request;

class PolesController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->query('limit');
        $offset = $request->query('offset');

        if ($limit === null && $offset === null) {
            return response()->json(Pole::with('chef')->get());
        }

        $query = Pole::with('chef');

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
            'nom_pole' => 'required|string|max:100',
            'id_chef' => 'nullable|integer|exists:users,id',
        ]);

        Pole::create($data);

        return response()->json([
            'success' => true,
            'data' => [
                'nom_pole' => $data['nom_pole'],
                'id_chef' => $data['id_chef'],
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $pole = Pole::find($id);

        if (!$pole) {
            return response()->json([
                'success' => false,
                'message' => 'Pole not found',
            ], 404);
        }

        $data = $request->validate([
            'nom_pole' => 'sometimes|required|string|max:100',
            'id_chef' => 'nullable|integer|exists:users,id',
        ]);

        $pole->update([
            'nom_pole' => $data['nom_pole'] ?? $pole->nom_pole,
            'id_chef' => $data['id_chef'] ?? $pole->id_chef,
        ]);

        return response()->json([
            'success' => true,
            'data' => $pole,
        ]);
    }

    public function destroy($id)
    {
        $pole = Pole::find($id);

        if (!$pole) {
            return response()->json([
                'success' => false,
                'message' => 'Pole not found',
            ], 404);
        }

        $pole->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pole deleted successfully',
        ]);
    }

    public function getFormateurs($id)
    {
        $pole = Pole::find($id);

        if (!$pole) {
            return response()->json([
                'success' => false,
                'message' => 'Pole not found',
            ], 404);
        }

        $formateurs = $pole->formateurs()->with('user')->get();

        return response()->json([
            'success' => true,
            'data' => $formateurs,
        ]);
    }

    public function getSalles($id)
    {
        $pole = Pole::find($id);

        if (!$pole) {
            return response()->json([
                'success' => false,
                'message' => 'Pole not found',
            ], 404);
        }

        $salles = $pole->salles;

        return response()->json([
            'success' => true,
            'data' => $salles,
        ]);
    }

    public function getFilieres($id)
    {
        $pole = Pole::find($id);

        if (!$pole) {
            return response()->json([
                'success' => false,
                'message' => 'Pole not found',
            ], 404);
        }

        $filieres = $pole->filieres;

        return response()->json([
            'success' => true,
            'data' => $filieres,
        ]);
    }
}
