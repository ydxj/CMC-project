<?php

namespace App\Http\Controllers;

use App\Models\ModuleGroupeFormateur;
use Illuminate\Http\Request;

class ModuleFormateurController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_module' => 'required|integer|exists:modules,id',
            'id_formateur' => 'required|integer|exists:formateurs,id',
            'id_groupe' => 'sometimes|integer|exists:groupes,id',
        ]);

        if (!isset($data['id_groupe'])) {
            return response()->json([
                'success' => false,
                'message' => 'id_groupe is required',
            ], 422);
        }

        ModuleGroupeFormateur::firstOrCreate([
            'id_module' => $data['id_module'],
            'id_groupe' => $data['id_groupe'],
            'id_formateur' => $data['id_formateur'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Assignment created successfully',
        ]);
    }
}
