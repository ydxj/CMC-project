<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Pole;
use App\Models\Filiere;
use App\Models\User;
use Illuminate\Http\Request;

class ModulesController extends Controller
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
        $query = Module::query()->with(['filiere.pole']);
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
            'nom_module' => 'required|string|max:150',
            'volume_horaire' => 'required|integer|min:1',
            'id_formation' => 'required|integer|exists:filieres,id',
            'id_filiere' => 'required|integer|exists:filieres,id',
        ]);

        $accessDenied = $this->validateChefFiliereAccess($request, (int) $data['id_filiere']);
        if ($accessDenied) {
            return $accessDenied;
        }

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

        $existingAccessDenied = $this->validateChefFiliereAccess($request, (int) $module->id_filiere);
        if ($existingAccessDenied) {
            return $existingAccessDenied;
        }

        $data = $request->validate([
            'nom_module' => 'sometimes|required|string|max:150',
            'volume_horaire' => 'sometimes|required|integer|min:1',
            'id_filiere' => 'sometimes|required|integer|exists:filieres,id',
        ]);

        $targetFiliereId = isset($data['id_filiere']) ? (int) $data['id_filiere'] : (int) $module->id_filiere;
        $targetAccessDenied = $this->validateChefFiliereAccess($request, $targetFiliereId);
        if ($targetAccessDenied) {
            return $targetAccessDenied;
        }

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
        $request = request();
        $module = Module::find($id);

        if (!$module) {
            return response()->json([
                'success' => false,
                'message' => 'Module not found',
            ], 404);
        }

        $accessDenied = $this->validateChefFiliereAccess($request, (int) $module->id_filiere);
        if ($accessDenied) {
            return $accessDenied;
        }

        $module->delete();

        return response()->json([
            'success' => true,
            'message' => 'Module deleted successfully',
        ]);
    }
}
