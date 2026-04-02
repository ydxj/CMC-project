<?php

namespace App\Http\Controllers;

use App\Models\Secteur;
use App\Services\ExcelBulkService;
use Illuminate\Http\Request;

class SecteursController extends Controller
{
    public function downloadTemplate(ExcelBulkService $excelBulkService)
    {
        return $excelBulkService->downloadTemplate('secteurs');
    }

    public function importExcel(Request $request, ExcelBulkService $excelBulkService)
    {
        return $excelBulkService->import($request, 'secteurs');
    }

    public function index(Request $request)
    {
        $query = Secteur::query()->orderByDesc('id');

        $page = $request->query('page');
        $perPage = max(1, min((int) $request->query('per_page', 25), 100));
        $limit = $request->query('limit');
        $offset = $request->query('offset');

        if ($page !== null) {
            return response()->json($query->paginate($perPage));
        }

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
            'nom_secteur' => 'required|string|max:150',
        ]);

        $secteur = Secteur::create([
            'nom_secteur' => $data['nom_secteur'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $secteur,
        ]);
    }

    public function show($id)
    {
        $secteur = Secteur::find($id);

        if (!$secteur) {
            return response()->json([
                'success' => false,
                'message' => 'Secteur not found',
            ], 404);
        }

        return response()->json($secteur);
    }

    public function update(Request $request, $id)
    {
        $secteur = Secteur::find($id);

        if (!$secteur) {
            return response()->json([
                'success' => false,
                'message' => 'Secteur not found',
            ], 404);
        }

        $data = $request->validate([
            'nom_secteur' => 'sometimes|required|string|max:150',
        ]);

        $secteur->update([
            'nom_secteur' => $data['nom_secteur'] ?? $secteur->nom_secteur,
        ]);

        return response()->json([
            'success' => true,
            'data' => $secteur,
        ]);
    }

    public function destroy($id)
    {
        $secteur = Secteur::find($id);

        if (!$secteur) {
            return response()->json([
                'success' => false,
                'message' => 'Secteur not found',
            ], 404);
        }

        $secteur->delete();

        return response()->json([
            'success' => true,
            'message' => 'Secteur deleted successfully',
        ]);
    }
}
