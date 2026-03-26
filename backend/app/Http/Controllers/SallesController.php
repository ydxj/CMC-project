<?php

namespace App\Http\Controllers;

use App\Models\Salle;
use App\Models\Pole;
use App\Models\User;
use Illuminate\Http\Request;

class SallesController extends Controller
{
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

    private function validateChefPoleAccess(Request $request, int $poleId)
    {
        $chefPoleIds = $this->getChefPoleIds($request->user());
        if ($chefPoleIds !== null && !$chefPoleIds->contains($poleId)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: this pole does not belong to you',
            ], 403);
        }

        return null;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = Salle::with('pole');

        if ($user && $user->role !== User::ROLE_DOSI && $user->id_etablissement) {
            $query->where('id_etablissement', $user->id_etablissement);
        }

        $chefPoleIds = $this->getChefPoleIds($user);
        if ($chefPoleIds !== null) {
            $query->whereIn('id_pole', $chefPoleIds);
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
        $user = $request->user();
        $data = $request->validate([
            'nom_salle' => 'required|string|max:100',
            'type_salle' => 'required|in:SALLE,ATELIER',
            'id_pole' => 'nullable|integer|exists:poles,id',
            'id_etablissement' => 'nullable|integer|exists:etablissements,id',
        ]);

        if ($user && $user->role !== User::ROLE_DOSI) {
            if (!$user->id_etablissement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authenticated user does not have an establishment scope',
                ], 422);
            }

            $data['id_etablissement'] = (int) $user->id_etablissement;
        }

        if (empty($data['id_etablissement'])) {
            return response()->json([
                'success' => false,
                'message' => 'id_etablissement is required',
            ], 422);
        }

        // EFP salles are establishment-scoped (single-pole model), so they must not be tied to a pole.
        if ($user && $user->role === User::ROLE_DIRECTEUR_EFP) {
            $data['id_pole'] = null;
        }

        if (!empty($data['id_pole'])) {
            $poleExists = Pole::where('id', $data['id_pole'])
                ->where('id_etablissement', $data['id_etablissement'])
                ->exists();

            if (!$poleExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected pole does not belong to the target establishment',
                ], 422);
            }
        }

        if (!empty($data['id_pole'])) {
            $accessDenied = $this->validateChefPoleAccess($request, (int) $data['id_pole']);
            if ($accessDenied) {
                return $accessDenied;
            }
        }

        Salle::create($data);

        return response()->json([
            'success' => true,
            'data' => [
                'id_etablissement' => $data['id_etablissement'],
                'nom_salle' => $data['nom_salle'],
                'type_salle' => $data['type_salle'],
                'id_pole' => $data['id_pole'] ?? null,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $actor = $request->user();
        $salle = Salle::find($id);

        if (!$salle) {
            return response()->json([
                'success' => false,
                'message' => 'Salle not found',
            ], 404);
        }

        if ($actor && $actor->role !== User::ROLE_DOSI && $actor->id_etablissement && (int) $salle->id_etablissement !== (int) $actor->id_etablissement) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: salle belongs to another establishment',
            ], 403);
        }

        if (!empty($salle->id_pole)) {
            $existingAccessDenied = $this->validateChefPoleAccess($request, (int) $salle->id_pole);
            if ($existingAccessDenied) {
                return $existingAccessDenied;
            }
        }

        $data = $request->validate([
            'nom_salle' => 'sometimes|required|string|max:100',
            'type_salle' => 'sometimes|required|in:SALLE,ATELIER',
            'id_pole' => 'sometimes|nullable|integer|exists:poles,id',
            'id_etablissement' => 'sometimes|integer|exists:etablissements,id',
        ]);

        if ($actor && $actor->role !== User::ROLE_DOSI) {
            unset($data['id_etablissement']);
        }

        // Keep EFP salles at establishment level even during updates.
        if ($actor && $actor->role === User::ROLE_DIRECTEUR_EFP) {
            $data['id_pole'] = null;
        }

        $targetPoleId = array_key_exists('id_pole', $data) ? (int) $data['id_pole'] : (int) $salle->id_pole;
        if ($targetPoleId > 0) {
            $targetAccessDenied = $this->validateChefPoleAccess($request, $targetPoleId);
            if ($targetAccessDenied) {
                return $targetAccessDenied;
            }
        }

        $salle->update([
            'nom_salle' => $data['nom_salle'] ?? $salle->nom_salle,
            'type_salle' => $data['type_salle'] ?? $salle->type_salle,
            'id_pole' => array_key_exists('id_pole', $data) ? $data['id_pole'] : $salle->id_pole,
            'id_etablissement' => $data['id_etablissement'] ?? $salle->id_etablissement,
        ]);

        return response()->json([
            'success' => true,
            'data' => $salle,
        ]);
    }

    public function destroy($id)
    {
        $request = request();
        $actor = $request->user();
        $salle = Salle::find($id);

        if (!$salle) {
            return response()->json([
                'success' => false,
                'message' => 'Salle not found',
            ], 404);
        }

        if ($actor && $actor->role !== User::ROLE_DOSI && $actor->id_etablissement && (int) $salle->id_etablissement !== (int) $actor->id_etablissement) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: salle belongs to another establishment',
            ], 403);
        }

        if (!empty($salle->id_pole)) {
            $accessDenied = $this->validateChefPoleAccess($request, (int) $salle->id_pole);
            if ($accessDenied) {
                return $accessDenied;
            }
        }

        $salle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Salle deleted successfully',
        ]);
    }
}
