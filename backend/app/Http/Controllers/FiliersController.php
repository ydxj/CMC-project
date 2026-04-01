<?php

namespace App\Http\Controllers;

use App\Models\Filiere;
use App\Services\ExcelBulkService;
use Illuminate\Http\Request;

class FiliersController extends Controller
{
    public function downloadTemplate(ExcelBulkService $excelBulkService)
    {
        return $excelBulkService->downloadTemplate('filiers');
    }

    public function importExcel(Request $request, ExcelBulkService $excelBulkService)
    {
        return $excelBulkService->import($request, 'filiers');
    }

    public function index(Request $request)
    {
        $query = Filiere::with(['pole', 'secteur']);

        $query->orderByDesc('id');

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
            'nom_filiere' => 'required|string|max:150',
            'id_secteur' => 'nullable|integer|exists:secteurs,id',
            'id_pole' => 'nullable|integer|exists:poles,id',
        ]);

        $filiere = Filiere::create([
            'id_secteur' => $data['id_secteur'] ?? null,
            'nom_filiere' => $data['nom_filiere'],
            'id_pole' => $data['id_pole'] ?? null,
        ]);

        $filiere->load('secteur');

        return response()->json([
            'success' => true,
            'data' => $filiere,
        ]);
    }

    public function show($id)
    {
        $filiere = Filiere::with('pole')->find($id);

        if (!$filiere) {
            return response()->json([
                'success' => false,
                'message' => 'Filiere not found',
            ], 404);
        }

        return response()->json($filiere);
    }

    public function update(Request $request, $id)
    {
        $filiere = Filiere::find($id);

        if (!$filiere) {
            return response()->json([
                'success' => false,
                'message' => 'Filiere not found',
            ], 404);
        }

        $data = $request->validate([
            'nom_filiere' => 'sometimes|required|string|max:150',
            'id_secteur' => 'sometimes|nullable|integer|exists:secteurs,id',
            'id_pole' => 'sometimes|nullable|integer|exists:poles,id',
        ]);

        $filiere->update([
            'nom_filiere' => $data['nom_filiere'] ?? $filiere->nom_filiere,
            'id_secteur' => array_key_exists('id_secteur', $data) ? $data['id_secteur'] : $filiere->id_secteur,
            'id_pole' => array_key_exists('id_pole', $data) ? $data['id_pole'] : $filiere->id_pole,
        ]);

        $filiere->load('secteur');

        return response()->json([
            'success' => true,
            'data' => $filiere,
        ]);
    }

    public function destroy($id)
    {
        $filiere = Filiere::find($id);

        if (!$filiere) {
            return response()->json([
                'success' => false,
                'message' => 'Filiere not found',
            ], 404);
        }

        $filiere->delete();

        return response()->json([
            'success' => true,
            'message' => 'Filiere deleted successfully',
        ]);
    }
}
