<?php

namespace App\Http\Controllers;

use App\Models\Etablissement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EtablissementsController extends Controller
{
    public function index(Request $request)
    {
        $query = Etablissement::query()
            ->with('directeur:id,nom,prenom,email,role,id_etablissement')
            ->leftJoin('ville as v', 'etablissements.city_id', '=', 'v.id')
            ->leftJoin('region as r', 'v.region', '=', 'r.id')
            ->select([
                'etablissements.*',
                'v.ville as city_name',
                'v.region as region_id',
                'r.region as region_name',
            ])
            ->orderByDesc('etablissements.id');

        $page = $request->query('page');
        $perPage = max(1, min((int) $request->query('per_page', 25), 100));

        if ($page !== null) {
            return response()->json($query->paginate($perPage));
        }

        $limit = $request->query('limit');
        $offset = $request->query('offset');

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
            'nom_etablissement' => 'required|string|max:150|unique:etablissements,nom_etablissement',
            'type_etablissement' => 'required|in:CMC,EFP',
            'city_id' => 'required|integer|exists:ville,id',
            'id_directeur' => 'nullable|integer|exists:users,id',
        ]);

        if (!empty($data['id_directeur'])) {
            $directeur = User::find($data['id_directeur']);
            if (!$directeur || !in_array($directeur->role, [User::ROLE_DIRECTEUR_CMC, User::ROLE_DIRECTEUR_EFP], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected user must be a directeur (DIRECTEUR_CMC or DIRECTEUR_EFP)',
                ], 422);
            }

            if (($data['type_etablissement'] === 'CMC' && $directeur->role !== User::ROLE_DIRECTEUR_CMC)
                || ($data['type_etablissement'] === 'EFP' && $directeur->role !== User::ROLE_DIRECTEUR_EFP)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Directeur role must match type_etablissement',
                ], 422);
            }
        }

        $etablissement = DB::transaction(function () use ($data) {
            $etablissement = Etablissement::create([
                'nom_etablissement' => $data['nom_etablissement'],
                'type_etablissement' => $data['type_etablissement'],
                'city_id' => $data['city_id'],
                'id_directeur' => $data['id_directeur'] ?? null,
            ]);

            if (!empty($data['id_directeur'])) {
                User::where('id', $data['id_directeur'])->update([
                    'id_etablissement' => $etablissement->id,
                ]);
            }

            return $etablissement;
        });

        return response()->json([
            'success' => true,
            'data' => $etablissement->load('directeur:id,nom,prenom,email,role,id_etablissement'),
        ], 201);
    }

    public function show($id)
    {
        $etablissement = Etablissement::with('directeur:id,nom,prenom,email,role,id_etablissement')->find($id);

        if (!$etablissement) {
            return response()->json([
                'success' => false,
                'message' => 'Etablissement not found',
            ], 404);
        }

        return response()->json($etablissement);
    }

    public function update(Request $request, $id)
    {
        $etablissement = Etablissement::find($id);

        if (!$etablissement) {
            return response()->json([
                'success' => false,
                'message' => 'Etablissement not found',
            ], 404);
        }

        $data = $request->validate([
            'nom_etablissement' => 'sometimes|required|string|max:150|unique:etablissements,nom_etablissement,' . $id,
            'type_etablissement' => 'sometimes|required|in:CMC,EFP',
            'city_id' => 'sometimes|required|integer|exists:ville,id',
            'id_directeur' => 'sometimes|nullable|integer|exists:users,id',
        ]);

        if (array_key_exists('id_directeur', $data) && !empty($data['id_directeur'])) {
            $directeur = User::find($data['id_directeur']);
            if (!$directeur || !in_array($directeur->role, [User::ROLE_DIRECTEUR_CMC, User::ROLE_DIRECTEUR_EFP], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected user must be a directeur (DIRECTEUR_CMC or DIRECTEUR_EFP)',
                ], 422);
            }

            $targetType = $data['type_etablissement'] ?? $etablissement->type_etablissement;
            if (($targetType === 'CMC' && $directeur->role !== User::ROLE_DIRECTEUR_CMC)
                || ($targetType === 'EFP' && $directeur->role !== User::ROLE_DIRECTEUR_EFP)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Directeur role must match type_etablissement',
                ], 422);
            }
        }

        DB::transaction(function () use ($etablissement, $data) {
            $previousDirecteurId = $etablissement->id_directeur;

            $etablissement->update([
                'nom_etablissement' => $data['nom_etablissement'] ?? $etablissement->nom_etablissement,
                'type_etablissement' => $data['type_etablissement'] ?? $etablissement->type_etablissement,
                'city_id' => $data['city_id'] ?? $etablissement->city_id,
                'id_directeur' => array_key_exists('id_directeur', $data) ? $data['id_directeur'] : $etablissement->id_directeur,
            ]);

            if (array_key_exists('id_directeur', $data)) {
                if (!empty($previousDirecteurId) && (int) $previousDirecteurId !== (int) ($data['id_directeur'] ?? 0)) {
                    User::where('id', $previousDirecteurId)->update(['id_etablissement' => null]);
                }

                if (!empty($data['id_directeur'])) {
                    User::where('id', $data['id_directeur'])->update(['id_etablissement' => $etablissement->id]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'data' => $etablissement->fresh()->load('directeur:id,nom,prenom,email,role,id_etablissement'),
        ]);
    }

    public function destroy($id)
    {
        $etablissement = Etablissement::find($id);

        if (!$etablissement) {
            return response()->json([
                'success' => false,
                'message' => 'Etablissement not found',
            ], 404);
        }

        $etablissement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Etablissement deleted successfully',
        ]);
    }
}
