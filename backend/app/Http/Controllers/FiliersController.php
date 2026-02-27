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
            'nom_formation' => 'required|string|max:150',
            'nom_filiere' => 'required|string|max:150',
            'id_pole' => 'required|integer|exists:poles,id',
        ]);

        Filiere::create([
            'nom_filiere' => $data['nom_filiere'],
            'id_pole' => $data['id_pole'],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'nom_filiere' => $data['nom_filiere'],
                'id_pole' => $data['id_pole'],
            ],
        ]);
    }
}
