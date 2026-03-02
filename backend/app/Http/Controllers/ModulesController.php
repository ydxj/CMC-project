<?php

namespace App\Http\Controllers;

use App\Models\Module;
use Illuminate\Http\Request;

class ModulesController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->query('limit');
        $offset = $request->query('offset');

        if ($limit === null && $offset === null) {
            return response()->json(Module::all());
        }

        $query = Module::query();

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
            'nom_module' => 'required|string|max:150',
            'volume_horaire' => 'required|integer|min:1',
            'id_formation' => 'required|integer|exists:filieres,id',
            'id_filiere' => 'required|integer|exists:filieres,id',
        ]);

        Module::create([
            'nom_module' => $data['nom_module'],
            'volume_horaire' => $data['volume_horaire'],
            'id_filiere' => $data['id_filiere'],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'nom_module' => $data['nom_module'],
                'volume_horaire' => $data['volume_horaire'],
                'id_filiere' => $data['id_filiere'],
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $module = Module::find($id);

        if (!$module) {
            return response()->json([
                'success' => false,
                'message' => 'Module not found',
            ], 404);
        }

        $data = $request->validate([
            'nom_module' => 'sometimes|required|string|max:150',
            'volume_horaire' => 'sometimes|required|integer|min:1',
            'id_filiere' => 'sometimes|required|integer|exists:filieres,id',
        ]);

        $module->update([
            'nom_module' => $data['nom_module'] ?? $module->nom_module,
            'volume_horaire' => $data['volume_horaire'] ?? $module->volume_horaire,
            'id_filiere' => $data['id_filiere'] ?? $module->id_filiere,
        ]);

        return response()->json([
            'success' => true,
            'data' => $module,
        ]);
    }

    public function destroy($id)
    {
        $module = Module::find($id);

        if (!$module) {
            return response()->json([
                'success' => false,
                'message' => 'Module not found',
            ], 404);
        }

        $module->delete();

        return response()->json([
            'success' => true,
            'message' => 'Module deleted successfully',
        ]);
    }
}
