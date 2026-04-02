<?php

namespace App\Http\Controllers;

use App\Models\Groupe;
use App\Models\Pole;
use App\Models\Filiere;
use App\Models\User;
use App\Services\ExcelBulkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

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

            $query->where(function ($q) use ($chefPoleIds) {
                $q->whereIn('id_pole', $chefPoleIds)
                    ->orWhereNull('id_pole');
            });
        }

        if (($user->role === User::ROLE_DIRECTEUR_CMC || $user->role === User::ROLE_DIRECTEUR_EFP) && $user->id_etablissement) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('id_pole')
                    ->orWhereHas('pole', function ($poleQ) use ($user) {
                        $poleQ->where('id_etablissement', $user->id_etablissement);
                    });
            });
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
        $hasGroupEtablissement = Schema::hasColumn('groupes', 'id_etablissement');
        $chefPoleIds = $this->getChefPoleIds($user);
        if ($chefPoleIds !== null) {
            $query->whereHas('filiere', function ($q) use ($chefPoleIds) {
                $q->where(function ($qq) use ($chefPoleIds) {
                    $qq->whereIn('id_pole', $chefPoleIds)
                        ->orWhereNull('id_pole');
                });
            });
        }

        if ($hasGroupEtablissement && $user && $user->role !== User::ROLE_DOSI && $user->id_etablissement) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('id_etablissement')
                    ->orWhere('id_etablissement', $user->id_etablissement);
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
        $hasGroupEtablissement = Schema::hasColumn('groupes', 'id_etablissement');
        $data = $request->validate([
            'nom_groupe' => 'required|string|max:100',
            'annee' => 'required|string|max:20',
            'niveau' => 'required|in:TS,T,Q,S,FQ,BP,PC',
            'crenau' => 'required|in:CDJ,CDS',
            'id_formation' => 'required|integer|exists:filieres,id',
            'id_filiere' => 'required|integer|exists:filieres,id',
            'id_etablissement' => ($hasGroupEtablissement ? 'nullable' : 'sometimes').'|integer|exists:etablissements,id',
        ]);

        $user = $request->user();
        $idEtablissement = $data['id_etablissement'] ?? null;
        if ($user && $user->role !== User::ROLE_DOSI) {
            $idEtablissement = $user->id_etablissement;
        }

        $accessDenied = $this->validateChefFiliereAccess($request, (int) $data['id_filiere']);
        if ($accessDenied) {
            return $accessDenied;
        }

        $createPayload = [
            'nom_groupe' => $data['nom_groupe'],
            'annee' => $data['annee'],
            'niveau' => $data['niveau'],
            'creneau' => $data['crenau'],
            'id_filiere' => $data['id_filiere'],
        ];
        if ($hasGroupEtablissement) {
            $createPayload['id_etablissement'] = $idEtablissement;
        }

        Groupe::create($createPayload);

        return response()->json([
            'success' => true,
            'data' => [
                'nom_groupe' => $data['nom_groupe'],
                'annee' => $data['annee'],
                'niveau' => $data['niveau'],
                'creneau' => $data['crenau'],
                'id_filiere' => $data['id_filiere'],
                'id_etablissement' => $hasGroupEtablissement ? $idEtablissement : null,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $groupe = Groupe::find($id);
        $hasGroupEtablissement = Schema::hasColumn('groupes', 'id_etablissement');

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
            'id_etablissement' => ($hasGroupEtablissement ? 'sometimes|nullable' : 'sometimes').'|integer|exists:etablissements,id',
        ]);

        $user = $request->user();
        $targetEtablissementId = $hasGroupEtablissement
            ? (array_key_exists('id_etablissement', $data) ? $data['id_etablissement'] : $groupe->id_etablissement)
            : null;
        if ($user && $user->role !== User::ROLE_DOSI) {
            $targetEtablissementId = $user->id_etablissement;
        }

        $targetFiliereId = isset($data['id_filiere']) ? (int) $data['id_filiere'] : (int) $groupe->id_filiere;
        $targetAccessDenied = $this->validateChefFiliereAccess($request, $targetFiliereId);
        if ($targetAccessDenied) {
            return $targetAccessDenied;
        }

        $updatePayload = [
            'nom_groupe' => $data['nom_groupe'] ?? $groupe->nom_groupe,
            'annee' => $data['annee'] ?? $groupe->annee,
            'niveau' => $data['niveau'] ?? $groupe->niveau,
            'creneau' => $data['crenau'] ?? $groupe->creneau,
            'id_filiere' => $data['id_filiere'] ?? $groupe->id_filiere,
        ];
        if ($hasGroupEtablissement) {
            $updatePayload['id_etablissement'] = $targetEtablissementId;
        }

        $groupe->update($updatePayload);

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
