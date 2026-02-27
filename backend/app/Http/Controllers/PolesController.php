<?php

namespace App\Http\Controllers;

use App\Models\Pole;
use Illuminate\Http\Request;

class PolesController extends Controller
{
    public function index()
    {
        return response()->json(Pole::all());
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
