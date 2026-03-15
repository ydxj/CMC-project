<?php

namespace App\Http\Controllers;

use App\Models\EmploiDuTemps;
use App\Models\Formateur;
use App\Models\Groupe;
use App\Models\Module;
use App\Models\ModuleGroupeFormateur;
use App\Models\Pole;
use Illuminate\Http\Request;

class ModuleFormateurController extends Controller
{
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

        return $query
            ->whereHas('module.filiere', function ($q) use ($chefPoleIds) {
                $q->whereIn('id_pole', $chefPoleIds);
            })
            ->whereHas('groupe.filiere', function ($q) use ($chefPoleIds) {
                $q->whereIn('id_pole', $chefPoleIds);
            })
            ->whereHas('formateur.poles', function ($q) use ($chefPoleIds) {
                $q->whereIn('poles.id', $chefPoleIds);
            });
    }

    private function validateChefModuleGroupeFormateur(Request $request, int $moduleId, int $groupeId, int $formateurId)
    {
        $chefPoleIds = $this->getChefPoleIds($request->user());
        if ($chefPoleIds === null) {
            return null;
        }

        $moduleAllowed = Module::where('id', $moduleId)
            ->whereHas('filiere', function ($q) use ($chefPoleIds) {
                $q->whereIn('id_pole', $chefPoleIds);
            })
            ->exists();

        $groupeAllowed = Groupe::where('id', $groupeId)
            ->whereHas('filiere', function ($q) use ($chefPoleIds) {
                $q->whereIn('id_pole', $chefPoleIds);
            })
            ->exists();

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
            ->with(['module', 'groupe', 'formateur.user']);

        $query = $this->applyChefScopeToAssignments($query, $request->user());

        if ($request->has('id_module')) {
            $query->where('id_module', $request->query('id_module'));
        }

        if ($request->has('id_formateur')) {
            $query->where('id_formateur', $request->query('id_formateur'));
        }

        if ($request->has('id_groupe')) {
            $query->where('id_groupe', $request->query('id_groupe'));
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
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $assignment->id,
                'id_module' => $data['id_module'],
                'id_groupe' => $data['id_groupe'],
                'id_formateur' => $data['id_formateur'],
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $baseQuery = ModuleGroupeFormateur::query()->with(['module.filiere', 'groupe.filiere', 'formateur.poles']);
        $baseQuery = $this->applyChefScopeToAssignments($baseQuery, $request->user());

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

        $assignment->update([
            'id_module' => $targetModuleId,
            'id_groupe' => $targetGroupeId,
            'id_formateur' => $targetFormateurId,
        ]);

        return response()->json([
            'success' => true,
            'data' => $assignment->load(['module', 'groupe', 'formateur.user']),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $baseQuery = ModuleGroupeFormateur::query();
        $baseQuery = $this->applyChefScopeToAssignments($baseQuery, $request->user());

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

        $assignments = $this->applyChefScopeToAssignments($assignments, $request->user());

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

        $assignments = $this->applyChefScopeToAssignments($assignments, $request->user());

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

        $assignments = $this->applyChefScopeToAssignments($assignments, $request->user());

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
}
