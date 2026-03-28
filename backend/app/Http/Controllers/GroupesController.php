<?php

namespace App\Http\Controllers;

use App\Models\Groupe;
use App\Models\Pole;
use App\Models\Filiere;
use App\Models\User;
use App\Services\ExcelBulkService;
use Illuminate\Http\Request;

class GroupesController extends Controller
{
    public function downloadTemplate(ExcelBulkService $excelBulkService)
    {
        return $excelBulkService->downloadTemplate('groupes');
    }

    public function importExcel(Request $request, ExcelBulkService $excelBulkService)
    {
        return $excelBulkService->import($request, 'groupes');
    }

    private function getChefPoleIds($user)
    {
        if (!$user || $user->role !== User::ROLE_CHEF_POLE) {
            return null;
        }

        $query = Pole::where('id_chef', $user->id);
        if ($user->id_etablissement) {
            $query->where('id_etablissement', $user->id_etablissement);
        }

        return $query->pluck('id');
    }

    private function validateChefFiliereAccess(Request $request, int $filiereId)
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }

        $query = Filiere::where('id', $filiereId);

        if ($user->role === User::ROLE_CHEF_POLE) {
            $chefPoleIds = $this->getChefPoleIds($user);
            if ($chefPoleIds === null) {
                return null;
            }

            $query->whereIn('id_pole', $chefPoleIds);
        }

        if ($user->role !== User::ROLE_DOSI && $user->id_etablissement) {
            $query->where('id_etablissement', $user->id_etablissement);
        }

        $ownsFiliere = $query->exists();

        if (!$ownsFiliere) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: this filiere is outside your establishment scope',
            ], 403);
        }

        return null;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = Groupe::query()->with(['filiere.pole']);
        $chefPoleIds = $this->getChefPoleIds($user);
        if ($chefPoleIds !== null) {
            $query->whereHas('filiere', function ($q) use ($chefPoleIds) {
                $q->whereIn('id_pole', $chefPoleIds);
            });
        }

        if ($user && $user->role !== User::ROLE_DOSI && $user->id_etablissement) {
            $query->whereHas('filiere', function ($q) use ($user) {
                $q->where('id_etablissement', $user->id_etablissement);
            });
        }

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
            'nom_groupe' => 'required|string|max:100',
            'annee' => 'required|string|max:20',
            'niveau' => 'required|in:TS,T,Q,S,FQ,BP,PC',
            'crenau' => 'required|in:CDJ,CDS',
            'id_formation' => 'required|integer|exists:filieres,id',
            'id_filiere' => 'required|integer|exists:filieres,id',
        ]);

        $accessDenied = $this->validateChefFiliereAccess($request, (int) $data['id_filiere']);
        if ($accessDenied) {
            return $accessDenied;
        }

        Groupe::create([
            'nom_groupe' => $data['nom_groupe'],
            'annee' => $data['annee'],
            'niveau' => $data['niveau'],
            'creneau' => $data['crenau'],
            'id_filiere' => $data['id_filiere'],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'nom_groupe' => $data['nom_groupe'],
                'annee' => $data['annee'],
                'niveau' => $data['niveau'],
                'creneau' => $data['crenau'],
                'id_filiere' => $data['id_filiere'],
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $groupe = Groupe::find($id);

        if (!$groupe) {
            return response()->json([
                'success' => false,
                'message' => 'Groupe not found',
            ], 404);
        }

        $existingAccessDenied = $this->validateChefFiliereAccess($request, (int) $groupe->id_filiere);
        if ($existingAccessDenied) {
            return $existingAccessDenied;
        }

        $data = $request->validate([
            'nom_groupe' => 'sometimes|required|string|max:100',
            'annee' => 'sometimes|required|string|max:20',
            'niveau' => 'sometimes|required|in:TS,T,Q,S,FQ,BP,PC',
            'crenau' => 'sometimes|required|in:CDJ,CDS',
            'id_filiere' => 'sometimes|required|integer|exists:filieres,id',
        ]);

        $targetFiliereId = isset($data['id_filiere']) ? (int) $data['id_filiere'] : (int) $groupe->id_filiere;
        $targetAccessDenied = $this->validateChefFiliereAccess($request, $targetFiliereId);
        if ($targetAccessDenied) {
            return $targetAccessDenied;
        }

        $groupe->update([
            'nom_groupe' => $data['nom_groupe'] ?? $groupe->nom_groupe,
            'annee' => $data['annee'] ?? $groupe->annee,
            'niveau' => $data['niveau'] ?? $groupe->niveau,
            'creneau' => $data['crenau'] ?? $groupe->creneau,
            'id_filiere' => $data['id_filiere'] ?? $groupe->id_filiere,
        ]);

        return response()->json([
            'success' => true,
            'data' => $groupe,
        ]);
    }

    public function destroy($id)
    {
        $request = request();
        $groupe = Groupe::find($id);

        if (!$groupe) {
            return response()->json([
                'success' => false,
                'message' => 'Groupe not found',
            ], 404);
        }

        $accessDenied = $this->validateChefFiliereAccess($request, (int) $groupe->id_filiere);
        if ($accessDenied) {
            return $accessDenied;
        }

        $groupe->delete();

        return response()->json([
            'success' => true,
            'message' => 'Groupe deleted successfully',
        ]);
    }
}
