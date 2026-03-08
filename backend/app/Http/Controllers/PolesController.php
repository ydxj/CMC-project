<?php

namespace App\Http\Controllers;

use App\Models\Formateur;
use App\Models\Pole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PolesController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->query('limit');
        $offset = $request->query('offset');

        if ($limit === null && $offset === null) {
            return response()->json(Pole::with('chef')->get());
        }

        $query = Pole::with('chef');

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
            'nom_pole' => 'required|string|max:100',
            'id_chef' => 'nullable|integer|exists:users,id',
        ]);

        Pole::create($data);

        return response()->json([
            'success' => true,
            'data' => [
                'nom_pole' => $data['nom_pole'],
                'id_chef' => $data['id_chef'],
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $pole = Pole::find($id);

        if (!$pole) {
            return response()->json([
                'success' => false,
                'message' => 'Pole not found',
            ], 404);
        }

        $data = $request->validate([
            'nom_pole' => 'sometimes|required|string|max:100',
            'id_chef' => 'nullable|integer|exists:users,id',
        ]);

        $pole->update([
            'nom_pole' => $data['nom_pole'] ?? $pole->nom_pole,
            'id_chef' => $data['id_chef'] ?? $pole->id_chef,
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
        $pole = Pole::find($id);

        if (!$pole) {
            return response()->json([
                'success' => false,
                'message' => 'Pole not found',
            ], 404);
        }

        $user = $request->user();
        if (!$user || $user->id !== $pole->id_chef) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You are not the chef of this pole',
            ], 403);
        }

        $data = $request->validate([
            'id_user' => 'required|integer|exists:users,id',
        ]);

        try {
            $formateur = Formateur::where('id_user', $data['id_user'])->first();
            if (!$formateur) {
                return response()->json([
                    'success' => false,
                    'message' => 'This user is not a formateur',
                ], 422);
            }

            $existing = $pole->formateurs()->wherePivot('id_user', $formateur->id)->exists();
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
        $pole = Pole::find($id);

        if (!$pole) {
            return response()->json([
                'success' => false,
                'message' => 'Pole not found',
            ], 404);
        }

        $user = $request->user();
        if (!$user || $user->id !== $pole->id_chef) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You are not the chef of this pole',
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
