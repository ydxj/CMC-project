<?php

namespace App\Http\Controllers;

use App\Models\ModuleGroupeFormateur;
use Illuminate\Http\Request;

class ModuleFormateurController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->query('limit');
        $offset = $request->query('offset');

        $query = ModuleGroupeFormateur::query()
            ->with(['module', 'groupe', 'formateur']);

        if ($request->has('id_module')) {
            $query->where('id_module', $request->query('id_module'));
        }

        if ($request->has('id_formateur')) {
            $query->where('id_formateur', $request->query('id_formateur'));
        }

        if ($request->has('id_groupe')) {
            $query->where('id_groupe', $request->query('id_groupe'));
        }

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
            'id_module' => 'required|integer|exists:modules,id',
            'id_formateur' => 'required|integer|exists:formateurs,id',
            'id_groupe' => 'required|integer|exists:groupes,id',
        ]);

        $assignment = ModuleGroupeFormateur::firstOrCreate([
            'id_module' => $data['id_module'],
            'id_groupe' => $data['id_groupe'],
            'id_formateur' => $data['id_formateur'],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $assignment->id,
                'id_module' => $data['id_module'],
                'id_groupe' => $data['id_groupe'],
                'id_formateur' => $data['id_formateur'],
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $assignment = ModuleGroupeFormateur::find($id);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Module-Groupe-Formateur assignment not found',
            ], 404);
        }

        $data = $request->validate([
            'id_module' => 'sometimes|required|integer|exists:modules,id',
            'id_formateur' => 'sometimes|required|integer|exists:formateurs,id',
            'id_groupe' => 'sometimes|required|integer|exists:groupes,id',
        ]);

        $assignment->update([
            'id_module' => $data['id_module'] ?? $assignment->id_module,
            'id_groupe' => $data['id_groupe'] ?? $assignment->id_groupe,
            'id_formateur' => $data['id_formateur'] ?? $assignment->id_formateur,
        ]);

        return response()->json([
            'success' => true,
            'data' => $assignment->load(['module', 'groupe', 'formateur']),
        ]);
    }

    public function destroy($id)
    {
        $assignment = ModuleGroupeFormateur::find($id);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Module-Groupe-Formateur assignment not found',
            ], 404);
        }

        $assignment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Module-Groupe-Formateur assignment deleted successfully',
        ]);
    }

    public function getFormateurModules(Request $request, $id_formateur)
    {
        $formateur = \App\Models\Formateur::find($id_formateur);

        if (!$formateur) {
            return response()->json([
                'success' => false,
                'message' => 'Formateur not found',
            ], 404);
        }

        $assignments = ModuleGroupeFormateur::where('id_formateur', $id_formateur)
            ->with(['module', 'groupe'])
            ->get();

        return response()->json([
            'success' => true,
            'formateur_id' => $id_formateur,
            'data' => $assignments,
        ]);
    }

    public function getModuleFormateurs(Request $request, $id_module)
    {
        $module = \App\Models\Module::find($id_module);

        if (!$module) {
            return response()->json([
                'success' => false,
                'message' => 'Module not found',
            ], 404);
        }

        $assignments = ModuleGroupeFormateur::where('id_module', $id_module)
            ->with(['formateur', 'groupe'])
            ->get();

        return response()->json([
            'success' => true,
            'module_id' => $id_module,
            'data' => $assignments,
        ]);
    }

    public function getGroupeModules(Request $request, $id_groupe)
    {
        $groupe = \App\Models\Groupe::find($id_groupe);

        if (!$groupe) {
            return response()->json([
                'success' => false,
                'message' => 'Groupe not found',
            ], 404);
        }

        $assignments = ModuleGroupeFormateur::where('id_groupe', $id_groupe)
            ->with(['module', 'formateur'])
            ->get();

        return response()->json([
            'success' => true,
            'groupe_id' => $id_groupe,
            'data' => $assignments,
        ]);
    }
}
