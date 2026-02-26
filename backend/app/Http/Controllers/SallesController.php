<?php

namespace App\Http\Controllers;

use App\Models\Salle;
use Illuminate\Http\Request;

class SallesController extends Controller
{
    public function index()
    {
        return response()->json(Salle::all());
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
            'message' => 'Salle created successfully',
        ]);
    }
}
