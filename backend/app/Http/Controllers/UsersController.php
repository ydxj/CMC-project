<?php

namespace App\Http\Controllers;

use App\Models\Formateur;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->query('role');
        $search = trim((string) $request->query('q', ''));
        $limit = $request->query('limit');
        $offset = $request->query('offset');
        $hasFilters = $role !== null || $search !== '';
        $hasPagination = $limit !== null || $offset !== null;

        if (!$hasFilters && !$hasPagination) {
            return response()->json(
                User::select('id', 'nom', 'prenom', 'email', 'role', 'matricule')->get()
            );
        }

        $baseQuery = User::query();

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
            ->select('users.id', 'users.nom', 'users.prenom', 'users.email', 'users.role', 'users.matricule');

        if ($role === 'FORMATEUR') {
            $query
                ->leftJoin('formateurs', 'formateurs.id_user', '=', 'users.id')
                ->leftJoin('formateur_pole', 'formateur_pole.id_user', '=', 'formateurs.id')
                ->leftJoin('poles', 'poles.id', '=', 'formateur_pole.id_pole')
                ->addSelect(DB::raw("COALESCE(GROUP_CONCAT(DISTINCT poles.nom_pole ORDER BY poles.nom_pole SEPARATOR '||'), '') as pole_names"))
                ->groupBy('users.id', 'users.nom', 'users.prenom', 'users.email', 'users.role', 'users.matricule');
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
            ];

            if ($role === 'FORMATEUR') {
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
        $data = $request->validate([
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:DIRECTEUR,CHEF_POLE,FORMATEUR',
            'matricule' => 'required|string|max:50',
            'type_formateur' => 'nullable|required_if:role,FORMATEUR|in:FP,FV',
            'MHS' => 'nullable|required_if:role,FORMATEUR|integer|min:0',
            'NBR_semaine' => 'nullable|required_if:role,FORMATEUR|integer|min:0',
            'id_pole' => 'nullable|required_if:role,FORMATEUR|integer|exists:poles,id',
        ]);

        $user = DB::transaction(function () use ($data) {
            $userData = [
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
                'matricule' => $data['matricule'],
            ];

            $user = User::create($userData);

            if ($data['role'] === 'FORMATEUR') {
                $formateur = Formateur::create([
                    'id_user' => $user->id,
                    'type_formateur' => $data['type_formateur'] ?? null,
                    'MHS' => $data['MHS'] ?? null,
                    'NBR_semaine' => $data['NBR_semaine'] ?? null,
                ]);

                if (!empty($data['id_pole'])) {
                    DB::table('formateur_pole')->insert([
                        'id_user' => $formateur->id,
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
                'formateur' => $data['role'] === 'FORMATEUR' ? [
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
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $data = $request->validate([
            'nom' => 'sometimes|required|string|max:100',
            'prenom' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'password' => 'sometimes|required|string|min:6',
            'role' => 'sometimes|required|in:DIRECTEUR,CHEF_POLE,FORMATEUR',
            'matricule' => 'sometimes|required|string|max:50',
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update([
            'nom' => $data['nom'] ?? $user->nom,
            'prenom' => $data['prenom'] ?? $user->prenom,
            'email' => $data['email'] ?? $user->email,
            'role' => $data['role'] ?? $user->role,
            'matricule' => $data['matricule'] ?? $user->matricule,
            'password' => $data['password'] ?? $user->password,
        ]);

        return response()->json([
            'success' => true,
            'data' => $user->only('id', 'nom', 'prenom', 'email', 'role', 'matricule'),
        ]);
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ]);
    }
}
