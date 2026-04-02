<?php

namespace App\Http\Controllers;

use App\Models\EmploiDuTemps;
use App\Models\Filiere;
use App\Models\Formateur;
use App\Models\Groupe;
use App\Models\Module;
use App\Models\ModuleGroupeFormateur;
use App\Models\Pole;
use App\Models\Secteur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ModuleFormateurController extends Controller
{
    private function isDirectorRole($user): bool
    {
        if (!$user) {
            return false;
        }

        return in_array($user->role, ['DIRECTEUR_CMC', 'DIRECTEUR_EFP'], true);
    }

    private function getScopedEtablissementId($user): ?int
    {
        if (!$this->isDirectorRole($user)) {
            return null;
        }

        return $user->id_etablissement ? (int) $user->id_etablissement : null;
    }

    private function timeToMinutes(string $time): int
    {
        $hhmm = substr($time, 0, 5);
        [$hours, $minutes] = array_pad(explode(':', $hhmm), 2, 0);
        return ((int) $hours * 60) + (int) $minutes;
    }

    private function parseVolumeHours($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return 0.0;
        }

        $normalized = str_replace(',', '.', trim($value));
        if (preg_match('/\d+(\.\d+)?/', $normalized, $matches)) {
            return (float) $matches[0];
        }

        return 0.0;
    }

    private function minutesToHours(int $minutes): float
    {
        return round($minutes / 60, 2);
    }

    private function getChefPoleIds($user)
    {
        if (!$user || $user->role !== 'CHEF_POLE') {
            return null;
        }

        return Pole::where('id_chef', $user->id)->pluck('id');
    }

    private function applyChefScopeToAssignments($query, $user)
    {
        $chefPoleIds = $this->getChefPoleIds($user);
        if ($chefPoleIds === null) {
            return $query;
        }

        $query = $query
            ->whereHas('module.filiere', function ($q) use ($chefPoleIds) {
                $q->where(function ($scopeQuery) use ($chefPoleIds) {
                    $scopeQuery
                        ->whereIn('id_pole', $chefPoleIds)
                        ->orWhereNull('id_pole');
                });
            })
            ->whereHas('groupe.filiere', function ($q) use ($chefPoleIds) {
                $q->where(function ($scopeQuery) use ($chefPoleIds) {
                    $scopeQuery
                        ->whereIn('id_pole', $chefPoleIds)
                        ->orWhereNull('id_pole');
                });
            })
            ->whereHas('formateur.poles', function ($q) use ($chefPoleIds) {
                $q->whereIn('poles.id', $chefPoleIds);
            });

        if (Schema::hasColumn('module_groupe_formateur', 'id_etablissement') && $user?->id_etablissement) {
            $query->where('id_etablissement', $user->id_etablissement);
        }

        return $query;
    }

    private function applyEtablissementScopeToAssignments($query, $user)
    {
        $etablissementId = $this->getScopedEtablissementId($user);
        if ($etablissementId === null) {
            return $query;
        }

        return $query->where('id_etablissement', $etablissementId);
    }

    private function applyAssignmentScope($query, $user)
    {
        $query = $this->applyChefScopeToAssignments($query, $user);
        return $this->applyEtablissementScopeToAssignments($query, $user);
    }

    private function validateChefModuleGroupeFormateur(Request $request, int $moduleId, int $groupeId, int $formateurId)
    {
        $user = $request->user();
        $chefPoleIds = $this->getChefPoleIds($request->user());
        if ($chefPoleIds === null) {
            return null;
        }

        $moduleAllowed = Module::where('id', $moduleId)
            ->whereHas('filiere', function ($q) use ($chefPoleIds) {
                $q->where(function ($scopeQuery) use ($chefPoleIds) {
                    $scopeQuery
                        ->whereIn('id_pole', $chefPoleIds)
                        ->orWhereNull('id_pole');
                });
            })
            ->exists();

        $groupeAllowed = Groupe::where('id', $groupeId)
            ->whereHas('filiere', function ($q) use ($chefPoleIds) {
                $q->where(function ($scopeQuery) use ($chefPoleIds) {
                    $scopeQuery
                        ->whereIn('id_pole', $chefPoleIds)
                        ->orWhereNull('id_pole');
                });
            })
            ->exists();

        if ($groupeAllowed && Schema::hasColumn('groupes', 'id_etablissement') && $user?->id_etablissement) {
            $groupeAllowed = Groupe::where('id', $groupeId)
                ->where(function ($q) use ($user) {
                    $q->whereNull('id_etablissement')
                        ->orWhere('id_etablissement', $user->id_etablissement);
                })
                ->exists();
        }

        $formateurAllowed = Formateur::where('id', $formateurId)
            ->whereHas('poles', function ($q) use ($chefPoleIds) {
                $q->whereIn('poles.id', $chefPoleIds);
            })
            ->exists();

        if (!$moduleAllowed || !$groupeAllowed || !$formateurAllowed) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: assignment data is outside your pole scope',
            ], 403);
        }

        return null;
    }

    public function index(Request $request)
    {
        $limit = $request->query('limit');
        $offset = $request->query('offset');

        $query = ModuleGroupeFormateur::query()
            ->with(['module', 'groupe', 'formateur.user', 'etablissement']);

        $query = $this->applyAssignmentScope($query, $request->user());

        if ($request->has('id_module')) {
            $query->where('id_module', $request->query('id_module'));
        }

        if ($request->has('id_formateur')) {
            $query->where('id_formateur', $request->query('id_formateur'));
        }

        if ($request->has('id_groupe')) {
            $query->where('id_groupe', $request->query('id_groupe'));
        }

        if ($request->has('id_etablissement')) {
            $query->where('id_etablissement', $request->query('id_etablissement'));
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
            'id_module' => 'required|integer|exists:modules,id',
            'id_formateur' => 'required|integer|exists:formateurs,id',
            'id_groupe' => 'required|integer|exists:groupes,id',
        ]);

        $formateur = Formateur::find($data['id_formateur']);
        if (!$formateur) {
            return response()->json([
                'success' => false,
                'message' => 'Formateur not found',
            ], 404);
        }

        $accessDenied = $this->validateChefModuleGroupeFormateur(
            $request,
            (int) $data['id_module'],
            (int) $data['id_groupe'],
            (int) $data['id_formateur']
        );
        if ($accessDenied) {
            return $accessDenied;
        }

        $assignment = ModuleGroupeFormateur::firstOrCreate([
            'id_module' => $data['id_module'],
            'id_groupe' => $data['id_groupe'],
            'id_formateur' => $data['id_formateur'],
            'id_etablissement' => $formateur->id_etablissement,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $assignment->id,
                'id_module' => $data['id_module'],
                'id_groupe' => $data['id_groupe'],
                'id_formateur' => $data['id_formateur'],
                'id_etablissement' => $formateur->id_etablissement,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $baseQuery = ModuleGroupeFormateur::query()->with(['module.filiere', 'groupe.filiere', 'formateur.poles', 'etablissement']);
        $baseQuery = $this->applyAssignmentScope($baseQuery, $request->user());

        $assignment = $baseQuery->where('id', $id)->first();

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Module-Groupe-Formateur assignment not found',
            ], 404);
        }

        $data = $request->validate([
            'id_module' => 'sometimes|required|integer|exists:modules,id',
            'id_formateur' => 'sometimes|required|integer|exists:formateurs,id',
            'id_groupe' => 'sometimes|required|integer|exists:groupes,id',
        ]);

        $targetModuleId = isset($data['id_module']) ? (int) $data['id_module'] : (int) $assignment->id_module;
        $targetGroupeId = isset($data['id_groupe']) ? (int) $data['id_groupe'] : (int) $assignment->id_groupe;
        $targetFormateurId = isset($data['id_formateur']) ? (int) $data['id_formateur'] : (int) $assignment->id_formateur;

        $accessDenied = $this->validateChefModuleGroupeFormateur($request, $targetModuleId, $targetGroupeId, $targetFormateurId);
        if ($accessDenied) {
            return $accessDenied;
        }

        // If formateur is being changed, get the new establishment
        $targetEtablissementId = $assignment->id_etablissement;
        if (isset($data['id_formateur']) && (int) $data['id_formateur'] !== (int) $assignment->id_formateur) {
            $newFormateur = Formateur::find($targetFormateurId);
            if ($newFormateur) {
                $targetEtablissementId = $newFormateur->id_etablissement;
            }
        }

        $assignment->update([
            'id_module' => $targetModuleId,
            'id_groupe' => $targetGroupeId,
            'id_formateur' => $targetFormateurId,
            'id_etablissement' => $targetEtablissementId,
        ]);

        return response()->json([
            'success' => true,
            'data' => $assignment->load(['module', 'groupe', 'formateur.user', 'etablissement']),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $baseQuery = ModuleGroupeFormateur::query();
        $baseQuery = $this->applyAssignmentScope($baseQuery, $request->user());

        $assignment = $baseQuery->where('id', $id)->first();

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Module-Groupe-Formateur assignment not found',
            ], 404);
        }

        $assignment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Module-Groupe-Formateur assignment deleted successfully',
        ]);
    }

    public function getFormateurModules(Request $request, $id_formateur)
    {
        $formateur = Formateur::find($id_formateur);

        if (!$formateur) {
            return response()->json([
                'success' => false,
                'message' => 'Formateur not found',
            ], 404);
        }

        $userEtablissementId = $this->getScopedEtablissementId($request->user());
        if ($userEtablissementId !== null && (int) $formateur->id_etablissement !== $userEtablissementId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: this formateur does not belong to your establishment',
            ], 403);
        }

        $chefPoleIds = $this->getChefPoleIds($request->user());
        if ($chefPoleIds !== null) {
            $allowed = $formateur->poles()->whereIn('poles.id', $chefPoleIds)->exists();
            if (!$allowed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: this formateur does not belong to your pole',
                ], 403);
            }
        }

        $assignments = ModuleGroupeFormateur::where('id_formateur', $id_formateur)
            ->with(['module', 'groupe']);

        $assignments = $this->applyAssignmentScope($assignments, $request->user());

        return response()->json([
            'success' => true,
            'formateur_id' => $id_formateur,
            'data' => $assignments->get(),
        ]);
    }

    public function getModuleFormateurs(Request $request, $id_module)
    {
        $module = Module::find($id_module);

        if (!$module) {
            return response()->json([
                'success' => false,
                'message' => 'Module not found',
            ], 404);
        }

        $chefPoleIds = $this->getChefPoleIds($request->user());
        if ($chefPoleIds !== null) {
            $allowed = Module::where('id', $id_module)
                ->whereHas('filiere', function ($q) use ($chefPoleIds) {
                    $q->whereIn('id_pole', $chefPoleIds);
                })->exists();

            if (!$allowed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: this module does not belong to your pole',
                ], 403);
            }
        }

        $assignments = ModuleGroupeFormateur::where('id_module', $id_module)
            ->with(['formateur.user', 'groupe']);

        $assignments = $this->applyAssignmentScope($assignments, $request->user());

        return response()->json([
            'success' => true,
            'module_id' => $id_module,
            'data' => $assignments->get(),
        ]);
    }

    public function getGroupeModules(Request $request, $id_groupe)
    {
        $groupe = Groupe::find($id_groupe);

        if (!$groupe) {
            return response()->json([
                'success' => false,
                'message' => 'Groupe not found',
            ], 404);
        }

        $userEtablissementId = $this->getScopedEtablissementId($request->user());
        if ($userEtablissementId !== null) {
            $groupeInUserEtablissement = Groupe::where('id', $id_groupe)
                ->whereHas('filiere.pole', function ($q) use ($userEtablissementId) {
                    $q->where('id_etablissement', $userEtablissementId);
                })->exists();

            if (!$groupeInUserEtablissement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: this groupe does not belong to your establishment',
                ], 403);
            }
        }

        $chefPoleIds = $this->getChefPoleIds($request->user());
        if ($chefPoleIds !== null) {
            $allowed = Groupe::where('id', $id_groupe)
                ->whereHas('filiere', function ($q) use ($chefPoleIds) {
                    $q->whereIn('id_pole', $chefPoleIds);
                })->exists();

            if (!$allowed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: this groupe does not belong to your pole',
                ], 403);
            }
        }

        $assignments = ModuleGroupeFormateur::where('id_groupe', $id_groupe)
            ->with(['module', 'formateur.user']);

        $assignments = $this->applyAssignmentScope($assignments, $request->user());

        return response()->json([
            'success' => true,
            'groupe_id' => $id_groupe,
            'data' => $assignments->get(),
        ]);
    }

    public function getMyGroupes(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $formateur = Formateur::where('id_user', $user->id)->first();
        if (!$formateur) {
            return response()->json([
                'success' => false,
                'message' => 'No formateur profile found for current user',
            ], 404);
        }

        $assignments = ModuleGroupeFormateur::where('id_formateur', $formateur->id)
            ->with(['groupe.filiere.pole'])
            ->get();

        $groupes = $assignments
            ->map(function ($assignment) {
                return $assignment->groupe;
            })
            ->filter()
            ->unique('id')
            ->values()
            ->map(function ($groupe) {
                return [
                    'id' => $groupe->id,
                    'nom_groupe' => $groupe->nom_groupe,
                    'annee' => $groupe->annee,
                    'niveau' => $groupe->niveau,
                    'creneau' => $groupe->creneau,
                    'filiere' => $groupe->filiere ? [
                        'id' => $groupe->filiere->id,
                        'nom_filiere' => $groupe->filiere->nom_filiere,
                        'pole' => $groupe->filiere->pole ? [
                            'id' => $groupe->filiere->pole->id,
                            'nom_pole' => $groupe->filiere->pole->nom_pole,
                        ] : null,
                    ] : null,
                ];
            });

        return response()->json([
            'success' => true,
            'formateur_id' => $formateur->id,
            'data' => $groupes,
        ]);
    }

    public function getMyModules(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $formateur = Formateur::where('id_user', $user->id)->first();
        if (!$formateur) {
            return response()->json([
                'success' => false,
                'message' => 'No formateur profile found for current user',
            ], 404);
        }

        $assignments = ModuleGroupeFormateur::where('id_formateur', $formateur->id)
            ->with(['module.filiere.pole', 'groupe'])
            ->get();

        $modules = $assignments
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->module->id . '_' . $assignment->groupe->id,
                    'module_id' => $assignment->module->id,
                    'groupe_id' => $assignment->groupe->id,
                    'nom_module' => $assignment->module->nom_module,
                    'volume_horaire' => $assignment->module->volume_horaire,
                    'nom_groupe' => $assignment->groupe->nom_groupe,
                    'filiere' => $assignment->module->filiere ? [
                        'id' => $assignment->module->filiere->id,
                        'nom_filiere' => $assignment->module->filiere->nom_filiere,
                        'pole' => $assignment->module->filiere->pole ? [
                            'id' => $assignment->module->filiere->pole->id,
                            'nom_pole' => $assignment->module->filiere->pole->nom_pole,
                        ] : null,
                    ] : null,
                ];
            })
            ->unique('id')
            ->values();

        return response()->json([
            'success' => true,
            'formateur_id' => $formateur->id,
            'data' => $modules,
        ]);
    }

    public function getMyModuleHours(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $formateur = Formateur::where('id_user', $user->id)->first();
        if (!$formateur) {
            return response()->json([
                'success' => false,
                'message' => 'No formateur profile found for current user',
            ], 404);
        }

        $assignments = ModuleGroupeFormateur::where('id_formateur', $formateur->id)
            ->with(['module', 'groupe'])
            ->get();

        if ($assignments->isEmpty()) {
            return response()->json([
                'success' => true,
                'formateur_id' => $formateur->id,
                'totals' => [
                    'module_count' => 0,
                    'planned_minutes' => 0,
                    'planned_hours' => 0,
                ],
                'data' => [],
            ]);
        }

        $assignmentIds = $assignments->pluck('id');
        $scheduleEntries = EmploiDuTemps::whereIn('id_module_groupe_formateur', $assignmentIds)->get();

        $plannedMinutesByAssignment = [];
        foreach ($scheduleEntries as $entry) {
            $duration = $this->timeToMinutes($entry->heure_fin) - $this->timeToMinutes($entry->heure_debut);
            $duration = max(0, $duration);

            $assignmentId = (int) $entry->id_module_groupe_formateur;
            if (!array_key_exists($assignmentId, $plannedMinutesByAssignment)) {
                $plannedMinutesByAssignment[$assignmentId] = 0;
            }
            $plannedMinutesByAssignment[$assignmentId] += $duration;
        }

        $rows = $assignments->map(function ($assignment) use ($plannedMinutesByAssignment) {
            $assignmentId = (int) $assignment->id;
            $plannedMinutes = (int) ($plannedMinutesByAssignment[$assignmentId] ?? 0);
            $volumeHours = $this->parseVolumeHours($assignment->module->volume_horaire ?? 0);
            $volumeMinutes = (int) round($volumeHours * 60);
            $remainingMinutes = max(0, $volumeMinutes - $plannedMinutes);
            $exceededMinutes = max(0, $plannedMinutes - $volumeMinutes);

            return [
                'assignment_id' => $assignmentId,
                'module_id' => $assignment->module->id,
                'nom_module' => $assignment->module->nom_module,
                'groupe_id' => $assignment->groupe->id,
                'nom_groupe' => $assignment->groupe->nom_groupe,
                'volume_horaire' => $volumeHours,
                'volume_minutes' => $volumeMinutes,
                'planned_minutes' => $plannedMinutes,
                'planned_hours' => $this->minutesToHours($plannedMinutes),
                'remaining_minutes' => $remainingMinutes,
                'remaining_hours' => $this->minutesToHours($remainingMinutes),
                'exceeded_minutes' => $exceededMinutes,
                'exceeded_hours' => $this->minutesToHours($exceededMinutes),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'formateur_id' => $formateur->id,
            'totals' => [
                'module_count' => $rows->count(),
                'planned_minutes' => (int) $rows->sum('planned_minutes'),
                'planned_hours' => $this->minutesToHours((int) $rows->sum('planned_minutes')),
            ],
            'data' => $rows,
        ]);
    }

    public function getFormateurModuleHours(Request $request, $id_formateur)
    {
        $formateur = Formateur::find($id_formateur);

        if (!$formateur) {
            return response()->json([
                'success' => false,
                'message' => 'Formateur not found',
            ], 404);
        }

        $chefPoleIds = $this->getChefPoleIds($request->user());
        if ($chefPoleIds !== null) {
            $allowed = $formateur->poles()->whereIn('poles.id', $chefPoleIds)->exists();
            if (!$allowed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: this formateur does not belong to your pole',
                ], 403);
            }
        }

        $assignments = ModuleGroupeFormateur::where('id_formateur', $formateur->id)
            ->with(['module', 'groupe'])
            ->get();

        if ($assignments->isEmpty()) {
            return response()->json([
                'success' => true,
                'formateur_id' => $formateur->id,
                'totals' => [
                    'module_count' => 0,
                    'planned_minutes' => 0,
                    'planned_hours' => 0,
                ],
                'data' => [],
            ]);
        }

        $assignmentIds = $assignments->pluck('id');
        $scheduleEntries = EmploiDuTemps::whereIn('id_module_groupe_formateur', $assignmentIds)->get();

        $plannedMinutesByAssignment = [];
        foreach ($scheduleEntries as $entry) {
            $duration = $this->timeToMinutes($entry->heure_fin) - $this->timeToMinutes($entry->heure_debut);
            $duration = max(0, $duration);

            $assignmentId = (int) $entry->id_module_groupe_formateur;
            if (!array_key_exists($assignmentId, $plannedMinutesByAssignment)) {
                $plannedMinutesByAssignment[$assignmentId] = 0;
            }
            $plannedMinutesByAssignment[$assignmentId] += $duration;
        }

        $rows = $assignments->map(function ($assignment) use ($plannedMinutesByAssignment) {
            $assignmentId = (int) $assignment->id;
            $plannedMinutes = (int) ($plannedMinutesByAssignment[$assignmentId] ?? 0);
            $volumeHours = $this->parseVolumeHours($assignment->module->volume_horaire ?? 0);
            $volumeMinutes = (int) round($volumeHours * 60);
            $remainingMinutes = max(0, $volumeMinutes - $plannedMinutes);
            $exceededMinutes = max(0, $plannedMinutes - $volumeMinutes);

            return [
                'assignment_id' => $assignmentId,
                'module_id' => $assignment->module->id,
                'nom_module' => $assignment->module->nom_module,
                'groupe_id' => $assignment->groupe->id,
                'nom_groupe' => $assignment->groupe->nom_groupe,
                'volume_horaire' => $volumeHours,
                'volume_minutes' => $volumeMinutes,
                'planned_minutes' => $plannedMinutes,
                'planned_hours' => $this->minutesToHours($plannedMinutes),
                'remaining_minutes' => $remainingMinutes,
                'remaining_hours' => $this->minutesToHours($remainingMinutes),
                'exceeded_minutes' => $exceededMinutes,
                'exceeded_hours' => $this->minutesToHours($exceededMinutes),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'formateur_id' => $formateur->id,
            'totals' => [
                'module_count' => $rows->count(),
                'planned_minutes' => (int) $rows->sum('planned_minutes'),
                'planned_hours' => $this->minutesToHours((int) $rows->sum('planned_minutes')),
            ],
            'data' => $rows,
        ]);
    }

    public function getAffectationFormData(Request $request)
    {
        $user = $request->user();
        $userEstablissementId = $user?->id_etablissement;

        // Get all secteurs
        $secteurs = Secteur::all()->map(function ($s) {
            return [
                'id' => $s->id,
                'nom_secteur' => $s->nom_secteur,
            ];
        });

        // Get formateurs for this user's establishment (always load)
        $formateurQuery = Formateur::query();
        if ($userEstablissementId) {
            $formateurQuery->where('id_etablissement', $userEstablissementId);
        }
        $formateurs = $formateurQuery->with('user')->get()->map(function ($f) {
            return [
                'id' => $f->id,
                'nom' => $f->user?->nom,
                'prenom' => $f->user?->prenom,
                'type_formateur' => $f->type_formateur,
            ];
        });

        return response()->json([
            'success' => true,
            'secteurs' => $secteurs,
            'formateurs' => $formateurs,
        ]);
    }

    public function getFilieresBySecteur(Request $request, $id_secteur)
    {
        $user = $request->user();
        $chefPoleIds = $this->getChefPoleIds($user);
        $userEstablissementId = $user?->id_etablissement;

        $filieresQuery = Filiere::query()
            ->where('id_secteur', $id_secteur)
            ->with('secteur');

        if ($chefPoleIds !== null) {
            $filieresQuery->where(function ($q) use ($chefPoleIds) {
                $q->whereIn('id_pole', $chefPoleIds)->orWhereNull('id_pole');
            });
        } elseif ($this->isDirectorRole($user) && $userEstablissementId) {
            $filieresQuery->where(function ($q) use ($userEstablissementId) {
                $q->whereNull('id_pole')
                    ->orWhereHas('pole', function ($poleQ) use ($userEstablissementId) {
                        $poleQ->where('id_etablissement', $userEstablissementId);
                    });
            });
        }

        $filieres = $filieresQuery->get()->map(function ($f) {
            return [
                'id' => $f->id,
                'nom_filiere' => $f->nom_filiere,
                'id_secteur' => $f->id_secteur,
            ];
        });

        return response()->json([
            'success' => true,
            'filieres' => $filieres,
        ]);
    }

    public function getModulesByFiliere(Request $request, $id_filiere)
    {
        $user = $request->user();
        $chefPoleIds = $this->getChefPoleIds($user);
        $userEstablissementId = $user?->id_etablissement;

        $modulesQuery = Module::query()
            ->where('id_filiere', $id_filiere)
            ->with('filiere');

        if ($chefPoleIds !== null) {
            $modulesQuery->whereHas('filiere', function ($q) use ($chefPoleIds) {
                $q->where(function ($scopeQuery) use ($chefPoleIds) {
                    $scopeQuery
                        ->whereIn('id_pole', $chefPoleIds)
                        ->orWhereNull('id_pole');
                });
            });
        } elseif ($this->isDirectorRole($user) && $userEstablissementId) {
            $modulesQuery->whereHas('filiere', function ($q) use ($userEstablissementId) {
                $q->whereNull('id_pole')
                    ->orWhereHas('pole', function ($poleQ) use ($userEstablissementId) {
                        $poleQ->where('id_etablissement', $userEstablissementId);
                    });
            });
        }

        $modules = $modulesQuery->get()->map(function ($m) {
            return [
                'id' => $m->id,
                'nom_module' => $m->nom_module,
                'id_filiere' => $m->id_filiere,
                'volume_horaire' => $m->volume_horaire,
            ];
        });

        return response()->json([
            'success' => true,
            'modules' => $modules,
        ]);
    }

    public function getGroupesByFiliere(Request $request, $id_filiere)
    {
        $user = $request->user();
        $chefPoleIds = $this->getChefPoleIds($user);
        $userEstablissementId = $user?->id_etablissement;

        $groupesQuery = Groupe::query()
            ->where('id_filiere', $id_filiere)
            ->with('filiere');

        if ($chefPoleIds) {
            $groupesQuery->whereHas('filiere', function ($q) use ($chefPoleIds) {
                $q->where(function ($scopeQuery) use ($chefPoleIds) {
                    $scopeQuery
                        ->whereIn('id_pole', $chefPoleIds)
                        ->orWhereNull('id_pole');
                });
            });
        } elseif ($this->isDirectorRole($user) && $userEstablissementId) {
            $groupesQuery->whereHas('filiere', function ($q) use ($userEstablissementId) {
                $q->whereNull('id_pole')
                    ->orWhereHas('pole', function ($poleQ) use ($userEstablissementId) {
                        $poleQ->where('id_etablissement', $userEstablissementId);
                    });
            });
        }

        $groupes = $groupesQuery->get()->map(function ($g) {
            return [
                'id' => $g->id,
                'nom_groupe' => $g->nom_groupe,
                'annee' => $g->annee,
                'niveau' => $g->niveau,
                'creneau' => $g->creneau,
                'id_filiere' => $g->id_filiere,
                'filiere' => $g->filiere ? [
                    'id' => $g->filiere->id,
                    'nom' => $g->filiere->nom_filiere,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'groupes' => $groupes,
        ]);
    }
}
