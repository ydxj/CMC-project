<?php

namespace App\Http\Controllers;

use App\Models\Formateur;
use App\Models\Pole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        $actor = $request->user();
        $role = $request->query('role');
        $search = trim((string) $request->query('q', ''));
        $limit = $request->query('limit');
        $offset = $request->query('offset');
        $hasFilters = $role !== null || $search !== '';
        $hasPagination = $limit !== null || $offset !== null;

        if (!$hasFilters && !$hasPagination) {
            $query = User::select('id', 'nom', 'prenom', 'email', 'role', 'matricule', 'id_etablissement');

            if ($actor && $actor->role !== User::ROLE_DOSI && $actor->id_etablissement) {
                $query->where('id_etablissement', $actor->id_etablissement);
            }

            return response()->json(
                $query->get()
            );
        }

        $baseQuery = User::query();

        if ($actor && $actor->role !== User::ROLE_DOSI && $actor->id_etablissement) {
            $baseQuery->where('id_etablissement', $actor->id_etablissement);
        }

        if ($role !== null) {
            $baseQuery->where('role', $role);
        }

        if ($search !== '') {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('matricule', 'like', "%{$search}%");
            });
        }

        $total = (clone $baseQuery)->count();

        $query = (clone $baseQuery)
            ->select('users.id', 'users.nom', 'users.prenom', 'users.email', 'users.role', 'users.matricule', 'users.id_etablissement');

        if ($role === User::ROLE_FORMATEUR) {
            $query
                ->leftJoin('formateurs', 'formateurs.id_user', '=', 'users.id')
                ->leftJoin('formateur_pole', 'formateur_pole.id_formateur', '=', 'formateurs.id')
                ->leftJoin('poles', 'poles.id', '=', 'formateur_pole.id_pole')
                ->addSelect(DB::raw("COALESCE(GROUP_CONCAT(DISTINCT poles.nom_pole ORDER BY poles.nom_pole SEPARATOR '||'), '') as pole_names"))
                ->groupBy('users.id', 'users.nom', 'users.prenom', 'users.email', 'users.role', 'users.matricule', 'users.id_etablissement');
        }

        $query->orderBy('users.nom')->orderBy('users.prenom');

        if ($offset !== null) {
            $query->skip(max(0, (int) $offset));
        }

        if ($limit !== null) {
            $query->take(max(0, (int) $limit));
        }

        $rows = $query->get()->map(function ($item) use ($role) {
            $payload = [
                'id' => $item->id,
                'nom' => $item->nom,
                'prenom' => $item->prenom,
                'email' => $item->email,
                'role' => $item->role,
                'matricule' => $item->matricule,
                'id_etablissement' => $item->id_etablissement,
            ];

            if ($role === User::ROLE_FORMATEUR) {
                $rawPoleNames = (string) ($item->pole_names ?? '');
                $payload['pole_names'] = $rawPoleNames !== ''
                    ? array_values(array_filter(explode('||', $rawPoleNames)))
                    : [];
            }

            return $payload;
        })->values();

        return response()->json([
            'data' => $rows,
            'total' => $total,
            'limit' => $limit !== null ? max(0, (int) $limit) : null,
            'offset' => $offset !== null ? max(0, (int) $offset) : 0,
        ]);
    }

    public function store(Request $request)
    {
        $actor = $request->user();
        $data = $request->validate([
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:' . implode(',', User::ROLES),
            'matricule' => 'required|string|max:50',
            'id_etablissement' => 'nullable|required_unless:role,DOSI|integer|exists:etablissements,id',
            'type_formateur' => 'nullable|required_if:role,FORMATEUR|in:FP,FV',
            'MHS' => 'nullable|required_if:role,FORMATEUR|integer|min:0',
            'NBR_semaine' => 'nullable|required_if:role,FORMATEUR|integer|min:0',
            'id_pole' => 'nullable|required_if:role,FORMATEUR|integer|exists:poles,id',
        ]);

        if ($actor && $actor->role !== User::ROLE_DOSI) {
            if (!$actor->id_etablissement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authenticated user does not have an establishment scope',
                ], 422);
            }

            $data['id_etablissement'] = (int) $actor->id_etablissement;
        }

        if (($data['role'] ?? null) === User::ROLE_FORMATEUR && !empty($data['id_pole']) && !empty($data['id_etablissement'])) {
            $poleInScope = Pole::where('id', $data['id_pole'])
                ->where('id_etablissement', $data['id_etablissement'])
                ->exists();

            if (!$poleInScope) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected pole does not belong to the target establishment',
                ], 422);
            }
        }

        $user = DB::transaction(function () use ($data) {
            $userData = [
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
                'matricule' => $data['matricule'],
                'id_etablissement' => $data['id_etablissement'] ?? null,
            ];

            $user = User::create($userData);

            if ($data['role'] === User::ROLE_FORMATEUR) {
                $formateur = Formateur::create([
                    'id_user' => $user->id,
                    'id_etablissement' => $data['id_etablissement'],
                    'type_formateur' => $data['type_formateur'] ?? null,
                    'MHS' => $data['MHS'] ?? null,
                    'NBR_semaine' => $data['NBR_semaine'] ?? null,
                ]);

                if (!empty($data['id_pole'])) {
                    DB::table('formateur_pole')->insert([
                        'id_formateur' => $formateur->id,
                        'id_pole' => $data['id_pole'],
                    ]);
                }
            }

            return $user;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'email' => $data['email'],
                'role' => $data['role'],
                'matricule' => $data['matricule'],
                'id_etablissement' => $data['id_etablissement'] ?? null,
                'formateur' => $data['role'] === User::ROLE_FORMATEUR ? [
                    'type_formateur' => $data['type_formateur'] ?? null,
                    'MHS' => $data['MHS'] ?? null,
                    'NBR_semaine' => $data['NBR_semaine'] ?? null,
                    'id_pole' => $data['id_pole'] ?? null,
                ] : null,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $actor = $request->user();
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        if ($actor && $actor->role !== User::ROLE_DOSI && $actor->id_etablissement && (int) $user->id_etablissement !== (int) $actor->id_etablissement) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: user belongs to another establishment',
            ], 403);
        }

        $data = $request->validate([
            'nom' => 'sometimes|required|string|max:100',
            'prenom' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'password' => 'sometimes|required|string|min:6',
            'role' => 'sometimes|required|in:' . implode(',', User::ROLES),
            'matricule' => 'sometimes|required|string|max:50',
            'id_etablissement' => 'sometimes|nullable|integer|exists:etablissements,id',
        ]);

        if ($actor && $actor->role !== User::ROLE_DOSI) {
            unset($data['id_etablissement']);
        }

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update([
            'nom' => $data['nom'] ?? $user->nom,
            'prenom' => $data['prenom'] ?? $user->prenom,
            'email' => $data['email'] ?? $user->email,
            'role' => $data['role'] ?? $user->role,
            'matricule' => $data['matricule'] ?? $user->matricule,
            'id_etablissement' => $data['id_etablissement'] ?? $user->id_etablissement,
            'password' => $data['password'] ?? $user->password,
        ]);

        return response()->json([
            'success' => true,
            'data' => $user->only('id', 'nom', 'prenom', 'email', 'role', 'matricule', 'id_etablissement'),
        ]);
    }

    public function destroy($id)
    {
        $actor = request()->user();
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        if ($actor && $actor->role !== User::ROLE_DOSI && $actor->id_etablissement && (int) $user->id_etablissement !== (int) $actor->id_etablissement) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: user belongs to another establishment',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ]);
    }
}
