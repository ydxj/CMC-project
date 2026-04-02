<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Services\ExcelBulkService;
use Illuminate\Http\Request;

class ModulesController extends Controller
{
    public function downloadTemplate(ExcelBulkService $excelBulkService)
    {
        return $excelBulkService->downloadTemplate('modules');
    }

    public function importExcel(Request $request, ExcelBulkService $excelBulkService)
    {
        return $excelBulkService->import($request, 'modules');
    }

    public function index(Request $request)
    {
        $query = Module::query()->with(['filiere.pole']);

        $limit = $request->query('limit');
        $offset = $request->query('offset');

        if ($limit === null && $offset === null) {
            return response()->json($query->get());
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
            'nom_module' => 'required|string|max:150',
            'volume_horaire' => 'required|integer|min:1',
            'id_filiere' => 'required|integer|exists:filieres,id',
        ]);

        $module = Module::create([
            'nom_module' => $data['nom_module'],
            'volume_horaire' => $data['volume_horaire'],
            'id_filiere' => $data['id_filiere'],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $module->id,
                'nom_module' => $module->nom_module,
                'volume_horaire' => $module->volume_horaire,
                'id_filiere' => $module->id_filiere,
            ],
        ]);
    }

    public function show($id)
    {
        $module = Module::with(['filiere.pole'])->find($id);

        if (!$module) {
            return response()->json([
                'success' => false,
                'message' => 'Module not found',
            ], 404);
        }

        return response()->json($module);
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
