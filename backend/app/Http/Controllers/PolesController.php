<?php

namespace App\Http\Controllers;

use App\Models\Formateur;
use App\Models\Pole;
use App\Models\User;
use App\Services\ExcelBulkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PolesController extends Controller
{
    public function downloadTemplate(ExcelBulkService $excelBulkService)
    {
        return $excelBulkService->downloadTemplate('poles');
    }

    public function importExcel(Request $request, ExcelBulkService $excelBulkService)
    {
        return $excelBulkService->import($request, 'poles');
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

    private function ensureChefCanAccessPole(Request $request, int $poleId)
    {
        $user = $request->user();
        if (!$user || $user->role !== User::ROLE_CHEF_POLE) {
            return null;
        }

        $chefPoleIds = $this->getChefPoleIds($user);
        if (!$chefPoleIds || !$chefPoleIds->contains($poleId)) {
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
        $limit = $request->query('limit');
        $offset = $request->query('offset');

        $query = Pole::query()
            ->with('chef')
            ->select('poles.*')
            ->selectSub(function ($subQuery) {
                $subQuery->from('formateur_pole')
                    ->selectRaw('COUNT(DISTINCT formateur_pole.id_formateur)')
                    ->whereColumn('formateur_pole.id_pole', 'poles.id');
            }, 'formateurs_count');

        if ($user && $user->role !== User::ROLE_DOSI && $user->id_etablissement) {
            $query->where('id_etablissement', $user->id_etablissement);
        }

        if ($user && $user->role === User::ROLE_CHEF_POLE) {
            $query->where('id_chef', $user->id);
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
        $user = $request->user();
        $data = $request->validate([
            'nom_pole' => 'required|string|max:100',
            'id_chef' => 'nullable|integer|exists:users,id',
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

        Pole::create($data);

        return response()->json([
            'success' => true,
            'data' => [
                'id_etablissement' => $data['id_etablissement'],
                'nom_pole' => $data['nom_pole'],
                'id_chef' => $data['id_chef'] ?? null,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $pole = Pole::find($id);

        if (!$pole) {
            return response()->json([
                'success' => false,
                'message' => 'Pole not found',
            ], 404);
        }

        if ($user && $user->role !== User::ROLE_DOSI && $user->id_etablissement && (int) $pole->id_etablissement !== (int) $user->id_etablissement) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: pole belongs to another establishment',
            ], 403);
        }

        $data = $request->validate([
            'nom_pole' => 'sometimes|required|string|max:100',
            'id_chef' => 'nullable|integer|exists:users,id',
            'id_etablissement' => 'sometimes|integer|exists:etablissements,id',
        ]);

        if ($user && $user->role !== User::ROLE_DOSI) {
            unset($data['id_etablissement']);
        }

        $pole->update([
            'nom_pole' => $data['nom_pole'] ?? $pole->nom_pole,
            'id_chef' => $data['id_chef'] ?? $pole->id_chef,
            'id_etablissement' => $data['id_etablissement'] ?? $pole->id_etablissement,
        ]);

        return response()->json([
            'success' => true,
            'data' => $pole,
        ]);
    }

    public function destroy($id)
    {
        $pole = Pole::find($id);

        if (!$pole) {
            return response()->json([
                'success' => false,
                'message' => 'Pole not found',
            ], 404);
        }

        $pole->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pole deleted successfully',
        ]);
    }

    public function getFormateurs(Request $request, $id)
    {
        try {
            $accessDenied = $this->ensureChefCanAccessPole($request, (int) $id);
            if ($accessDenied) {
                return $accessDenied;
            }

            $pole = Pole::find($id);

            if (!$pole) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pole not found',
                ], 404);
            }

            $limit = $request->query('limit');
            $offset = $request->query('offset');

            $query = $pole->formateurs();

            if ($offset !== null) {
                $query->skip(max(0, (int) $offset));
            }

            if ($limit !== null) {
                $query->take(max(0, (int) $limit));
            }

            $formateurs = $query->with('user')->get();

            $data = $formateurs->map(function ($formateur) {
                return [
                    'id_user' => $formateur->id_user,
                    'id' => $formateur->id,
                    'type_formateur' => $formateur->type_formateur,
                    'MHS' => $formateur->MHS,
                    'NBR_semaine' => $formateur->NBR_semaine,
                    'user' => $formateur->user ? [
                        'id' => $formateur->user->id,
                        'nom' => $formateur->user->nom,
                        'prenom' => $formateur->user->prenom,
                        'email' => $formateur->user->email,
                        'matricule' => $formateur->user->matricule,
                        'role' => $formateur->user->role,
                    ] : null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getFormateurs: ' . $e->getMessage() . ' ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving formateurs',
            ], 500);
        }
    }

    public function getMyPoleFormateurs(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            $pole = Pole::where('id_chef', $user->id)->first();

            if (!$pole) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not a chef of any pole',
                ], 403);
            }

            $limit = $request->query('limit');
            $offset = $request->query('offset');

            $query = $pole->formateurs();

            if ($offset !== null) {
                $query->skip(max(0, (int) $offset));
            }

            if ($limit !== null) {
                $query->take(max(0, (int) $limit));
            }

            $formateurs = $query->with('user')->get();

            $data = $formateurs->map(function ($formateur) {
                return [
                    'id_user' => $formateur->id_user,
                    'id' => $formateur->id,
                    'type_formateur' => $formateur->type_formateur,
                    'MHS' => $formateur->MHS,
                    'NBR_semaine' => $formateur->NBR_semaine,
                    'user' => $formateur->user ? [
                        'id' => $formateur->user->id,
                        'nom' => $formateur->user->nom,
                        'prenom' => $formateur->user->prenom,
                        'email' => $formateur->user->email,
                        'matricule' => $formateur->user->matricule,
                        'role' => $formateur->user->role,
                    ] : null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'pole_id' => $pole->id,
                'pole_name' => $pole->nom_pole,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getMyPoleFormateurs: ' . $e->getMessage() . ' ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving formateurs',
            ], 500);
        }
    }

    public function addFormateur(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        // Always resolve the chef's pole from auth to avoid stale/mismatched route IDs.
        $poleQuery = Pole::where('id_chef', $user->id);
        if ($user->id_etablissement) {
            $poleQuery->where('id_etablissement', $user->id_etablissement);
        }
        $pole = $poleQuery->first();
        if (!$pole) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You are not the chef of any pole',
            ], 403);
        }

        $data = $request->validate([
            'id_user' => 'required|integer|exists:users,id',
        ]);

        try {
            $targetUser = User::find($data['id_user']);
            if (!$targetUser || $targetUser->role !== User::ROLE_FORMATEUR) {
                return response()->json([
                    'success' => false,
                    'message' => 'This user is not a formateur',
                ], 422);
            }

            if ((int) $targetUser->id_etablissement !== (int) $pole->id_etablissement) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected formateur belongs to another establishment',
                ], 422);
            }

            // Backfill missing formateur row for legacy/inconsistent data.
            $formateur = Formateur::firstOrCreate(
                ['id_user' => $data['id_user']],
                [
                    'id_etablissement' => $pole->id_etablissement,
                    'type_formateur' => null,
                    'MHS' => null,
                    'NBR_semaine' => null,
                ]
            );

            $existing = $pole->formateurs()->wherePivot('id_formateur', $formateur->id)->exists();
            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'This formateur is already assigned to this pole',
                ], 422);
            }

            $pole->formateurs()->attach($formateur->id);

            return response()->json([
                'success' => true,
                'message' => 'Formateur added to pole successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding formateur: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function removeFormateur(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $poleQuery = Pole::where('id_chef', $user->id);
        if ($user->id_etablissement) {
            $poleQuery->where('id_etablissement', $user->id_etablissement);
        }
        $pole = $poleQuery->first();
        if (!$pole) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You are not the chef of any pole',
            ], 403);
        }

        $id_user = $request->query('id_user') ?: $request->input('id_user');

        if (!$id_user) {
            return response()->json([
                'success' => false,
                'message' => 'id_user parameter is required',
            ], 422);
        }

        try {
            $formateur = Formateur::where('id_user', $id_user)->first();
            if (!$formateur) {
                return response()->json([
                    'success' => false,
                    'message' => 'Formateur not found',
                ], 404);
            }

            $pole->formateurs()->detach($formateur->id);

            return response()->json([
                'success' => true,
                'message' => 'Formateur removed from pole successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error removing formateur: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getSalles($id)
    {
        $accessDenied = $this->ensureChefCanAccessPole(request(), (int) $id);
        if ($accessDenied) {
            return $accessDenied;
        }

        $pole = Pole::find($id);

        if (!$pole) {
            return response()->json([
                'success' => false,
                'message' => 'Pole not found',
            ], 404);
        }

        $salles = $pole->salles;

        return response()->json([
            'success' => true,
            'data' => $salles,
        ]);
    }

    public function getFilieres($id)
    {
        $accessDenied = $this->ensureChefCanAccessPole(request(), (int) $id);
        if ($accessDenied) {
            return $accessDenied;
        }

        $pole = Pole::find($id);

        if (!$pole) {
            return response()->json([
                'success' => false,
                'message' => 'Pole not found',
            ], 404);
        }

        $filieres = $pole->filieres;

        return response()->json([
            'success' => true,
            'data' => $filieres,
        ]);
    }
}
