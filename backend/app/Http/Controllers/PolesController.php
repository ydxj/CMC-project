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
            return response()->json(Pole::all());
        }

        $query = Pole::query();

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
}
