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
            return response()->json(Salle::all());
        }

        $query = Salle::query();

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
}
