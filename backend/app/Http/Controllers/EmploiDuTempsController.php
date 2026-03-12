<?php

namespace App\Http\Controllers;

use App\Models\EmploiDuTemps;
use App\Models\Formateur;
use App\Models\ModuleGroupeFormateur;
use App\Models\Pole;
use App\Models\Salle;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class EmploiDuTempsController extends Controller
{
    private function buildConflictQuery(string $date, string $start, string $end, ?int $ignoreScheduleId = null)
    {
        $query = EmploiDuTemps::query()
            ->where('date_seance', $date)
            ->where('heure_debut', '<', $end)
            ->where('heure_fin', '>', $start);

        if ($ignoreScheduleId !== null) {
            $query->where('id', '!=', $ignoreScheduleId);
        }

        return $query;
    }

    private function detectScheduleConflict(
        string $date,
        string $start,
        string $end,
        int $groupeId,
        int $formateurId,
        int $salleId,
        ?int $ignoreScheduleId = null
    ): ?string {
        $groupeConflict = (clone $this->buildConflictQuery($date, $start, $end, $ignoreScheduleId))
            ->whereHas('moduleGroupeFormateur', function ($q) use ($groupeId) {
                $q->where('id_groupe', $groupeId);
            })
            ->exists();

        if ($groupeConflict) {
            return 'Conflit: ce groupe a deja une seance sur ce creneau.';
        }

        $formateurConflict = (clone $this->buildConflictQuery($date, $start, $end, $ignoreScheduleId))
            ->whereHas('moduleGroupeFormateur', function ($q) use ($formateurId) {
                $q->where('id_formateur', $formateurId);
            })
            ->exists();

        if ($formateurConflict) {
            return 'Conflit: ce formateur a deja une seance sur ce creneau.';
        }

        $salleConflict = (clone $this->buildConflictQuery($date, $start, $end, $ignoreScheduleId))
            ->where('id_salle', $salleId)
            ->exists();

        if ($salleConflict) {
            return 'Conflit: cette salle est deja reservee sur ce creneau.';
        }

        return null;
    }

    private function getChefPoleIds($user)
    {
        if (!$user || $user->role !== 'CHEF_POLE') {
            return null;
        }

        return Pole::where('id_chef', $user->id)->pluck('id');
    }

    private function applyChefScopeToSchedules($query, $user)
    {
        $chefPoleIds = $this->getChefPoleIds($user);
        if ($chefPoleIds === null) {
            return $query;
        }

        return $query
            ->whereHas('moduleGroupeFormateur.module.filiere', function ($q) use ($chefPoleIds) {
                $q->whereIn('id_pole', $chefPoleIds);
            })
            ->whereHas('moduleGroupeFormateur.groupe.filiere', function ($q) use ($chefPoleIds) {
                $q->whereIn('id_pole', $chefPoleIds);
            })
            ->whereHas('salle', function ($q) use ($chefPoleIds) {
                $q->whereIn('id_pole', $chefPoleIds);
            });
    }

    private function validateChefScheduleData(Request $request, int $moduleId, int $groupeId, int $formateurId, int $salleId)
    {
        $chefPoleIds = $this->getChefPoleIds($request->user());
        if ($chefPoleIds === null) {
            return null;
        }

        $assignmentAllowed = ModuleGroupeFormateur::where('id_module', $moduleId)
            ->where('id_groupe', $groupeId)
            ->where('id_formateur', $formateurId)
            ->whereHas('module.filiere', function ($q) use ($chefPoleIds) {
                $q->whereIn('id_pole', $chefPoleIds);
            })
            ->whereHas('groupe.filiere', function ($q) use ($chefPoleIds) {
                $q->whereIn('id_pole', $chefPoleIds);
            })
            ->whereHas('formateur', function ($q) use ($chefPoleIds) {
                $q->whereHas('poles', function ($p) use ($chefPoleIds) {
                    $p->whereIn('poles.id', $chefPoleIds);
                });
            })
            ->exists();

        $salleAllowed = Salle::where('id', $salleId)
            ->whereIn('id_pole', $chefPoleIds)
            ->exists();

        if (!$assignmentAllowed || !$salleAllowed) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: schedule data is outside your pole scope',
            ], 403);
        }

        return null;
    }

    public function index(Request $request)
    {
        $limit = $request->query('limit');
        $offset = $request->query('offset');

        if ($limit === null && $offset === null) {
            return response()->json(EmploiDuTemps::all());
        }

        $query = EmploiDuTemps::query();

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
            'date_seance' => 'required|date',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut',
            'id_groupe' => 'required|integer|exists:groupes,id',
            'id_module' => 'required|integer|exists:modules,id',
            'id_formateur' => 'required|integer|exists:formateurs,id',
            'id_salle' => 'required|integer|exists:salles,id',
        ]);

        $accessDenied = $this->validateChefScheduleData(
            $request,
            (int) $data['id_module'],
            (int) $data['id_groupe'],
            (int) $data['id_formateur'],
            (int) $data['id_salle']
        );
        if ($accessDenied) {
            return $accessDenied;
        }

        $conflictMessage = $this->detectScheduleConflict(
            $data['date_seance'],
            $data['heure_debut'],
            $data['heure_fin'],
            (int) $data['id_groupe'],
            (int) $data['id_formateur'],
            (int) $data['id_salle']
        );

        if ($conflictMessage !== null) {
            return response()->json([
                'success' => false,
                'message' => $conflictMessage,
            ], 409);
        }

        $assignment = ModuleGroupeFormateur::firstOrCreate([
            'id_module' => $data['id_module'],
            'id_groupe' => $data['id_groupe'],
            'id_formateur' => $data['id_formateur'],
        ]);

        try {
            EmploiDuTemps::create([
                'date_seance' => $data['date_seance'],
                'heure_debut' => $data['heure_debut'],
                'heure_fin' => $data['heure_fin'],
                'id_module_groupe_formateur' => $assignment->id,
                'id_salle' => $data['id_salle'],
            ]);
        } catch (QueryException $e) {
            if ((int) $e->getCode() === 23000) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conflit: cette salle est deja reservee sur ce creneau.',
                ], 409);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la creation de la seance.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'date_seance' => $data['date_seance'],
                'heure_debut' => $data['heure_debut'],
                'heure_fin' => $data['heure_fin'],
                'id_groupe' => $data['id_groupe'],
                'id_module' => $data['id_module'],
                'id_formateur' => $data['id_formateur'],
                'id_salle' => $data['id_salle'],
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $emploi = EmploiDuTemps::find($id);

        if (!$emploi) {
            return response()->json([
                'success' => false,
                'message' => 'Emploi du temps not found',
            ], 404);
        }

        $data = $request->validate([
            'date_seance' => 'sometimes|required|date',
            'heure_debut' => 'sometimes|required|date_format:H:i',
            'heure_fin' => 'sometimes|required|date_format:H:i|after:heure_debut',
            'id_groupe' => 'sometimes|required|integer|exists:groupes,id',
            'id_module' => 'sometimes|required|integer|exists:modules,id',
            'id_formateur' => 'sometimes|required|integer|exists:formateurs,id',
            'id_salle' => 'sometimes|required|integer|exists:salles,id',
        ]);

        $targetModuleId = (int) ($data['id_module'] ?? $emploi->moduleGroupeFormateur->id_module);
        $targetGroupeId = (int) ($data['id_groupe'] ?? $emploi->moduleGroupeFormateur->id_groupe);
        $targetFormateurId = (int) ($data['id_formateur'] ?? $emploi->moduleGroupeFormateur->id_formateur);
        $targetSalleId = (int) ($data['id_salle'] ?? $emploi->id_salle);
        $targetDate = $data['date_seance'] ?? $emploi->date_seance;
        $targetStart = $data['heure_debut'] ?? $emploi->heure_debut;
        $targetEnd = $data['heure_fin'] ?? $emploi->heure_fin;

        $accessDenied = $this->validateChefScheduleData(
            $request,
            $targetModuleId,
            $targetGroupeId,
            $targetFormateurId,
            $targetSalleId
        );
        if ($accessDenied) {
            return $accessDenied;
        }

        $conflictMessage = $this->detectScheduleConflict(
            $targetDate,
            $targetStart,
            $targetEnd,
            $targetGroupeId,
            $targetFormateurId,
            $targetSalleId,
            (int) $emploi->id
        );

        if ($conflictMessage !== null) {
            return response()->json([
                'success' => false,
                'message' => $conflictMessage,
            ], 409);
        }

        // Handle module-groupe-formateur update
        if (isset($data['id_module']) || isset($data['id_groupe']) || isset($data['id_formateur'])) {
            $assignment = ModuleGroupeFormateur::firstOrCreate([
                'id_module' => $targetModuleId,
                'id_groupe' => $targetGroupeId,
                'id_formateur' => $targetFormateurId,
            ]);

            $emploi->id_module_groupe_formateur = $assignment->id;
        }

        try {
            $emploi->update([
                'date_seance' => $data['date_seance'] ?? $emploi->date_seance,
                'heure_debut' => $data['heure_debut'] ?? $emploi->heure_debut,
                'heure_fin' => $data['heure_fin'] ?? $emploi->heure_fin,
                'id_salle' => $data['id_salle'] ?? $emploi->id_salle,
                'id_module_groupe_formateur' => $emploi->id_module_groupe_formateur,
            ]);
        } catch (QueryException $e) {
            if ((int) $e->getCode() === 23000) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conflit: impossible de deplacer la seance, salle ou creneau deja utilise.',
                ], 409);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise a jour de la seance.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $emploi,
        ]);
    }

    public function destroy($id)
    {
        $emploi = EmploiDuTemps::find($id);

        if (!$emploi) {
            return response()->json([
                'success' => false,
                'message' => 'Emploi du temps not found',
            ], 404);
        }

        $emploi->delete();

        return response()->json([
            'success' => true,
            'message' => 'Emploi du temps deleted successfully',
        ]);
    }

    public function getWeeklySchedule(Request $request)
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'id_groupe' => 'nullable|integer|exists:groupes,id',
            'id_formateur' => 'nullable|integer|exists:formateurs,id',
            'id_pole' => 'nullable|integer|exists:poles,id',
            'id_salle' => 'nullable|integer|exists:salles,id',
            'id_module' => 'nullable|integer|exists:modules,id',
        ]);

        $date = \Carbon\Carbon::parse($data['start_date']);
        // Get Monday of the week
        $monday = $date->copy()->startOfWeek();
        $sunday = $date->copy()->endOfWeek();

        $query = EmploiDuTemps::whereBetween('date_seance', [$monday, $sunday])
            ->with(['moduleGroupeFormateur.groupe', 'moduleGroupeFormateur.module', 'moduleGroupeFormateur.formateur', 'salle']);

        $query = $this->applyChefScopeToSchedules($query, $request->user());

        if (isset($data['id_groupe'])) {
            $query->whereHas('moduleGroupeFormateur', function ($q) use ($data) {
                $q->where('id_groupe', $data['id_groupe']);
            });
        }

        if (isset($data['id_formateur'])) {
            $query->whereHas('moduleGroupeFormateur', function ($q) use ($data) {
                $q->where('id_formateur', $data['id_formateur']);
            });
        }

        if (isset($data['id_pole'])) {
            $query->where(function ($q) use ($data) {
                $q->whereHas('moduleGroupeFormateur.module.filiere', function ($mf) use ($data) {
                    $mf->where('id_pole', $data['id_pole']);
                })->orWhereHas('moduleGroupeFormateur.groupe.filiere', function ($gf) use ($data) {
                    $gf->where('id_pole', $data['id_pole']);
                });
            });
        }

        if (isset($data['id_salle'])) {
            $query->where('id_salle', $data['id_salle']);
        }

        if (isset($data['id_module'])) {
            $query->whereHas('moduleGroupeFormateur', function ($q) use ($data) {
                $q->where('id_module', $data['id_module']);
            });
        }

        $schedules = $query->orderBy('date_seance')->orderBy('heure_debut')->get();

        return response()->json([
            'success' => true,
            'week' => [
                'start' => $monday->format('Y-m-d'),
                'end' => $sunday->format('Y-m-d'),
            ],
            'data' => $schedules,
        ]);
    }

    public function getByFormateur($id)
    {
        $request = request();

        $schedulesQuery = EmploiDuTemps::whereHas('moduleGroupeFormateur', function ($q) use ($id) {
            $q->where('id_formateur', $id);
        })
            ->with(['moduleGroupeFormateur.groupe', 'moduleGroupeFormateur.module', 'moduleGroupeFormateur.formateur', 'salle'])
            ->orderBy('date_seance')
            ->orderBy('heure_debut');

        $schedulesQuery = $this->applyChefScopeToSchedules($schedulesQuery, $request->user());
        $schedules = $schedulesQuery->get();

        return response()->json([
            'success' => true,
            'data' => $schedules,
        ]);
    }

    public function getByGroupe($id)
    {
        $request = request();

        $schedulesQuery = EmploiDuTemps::whereHas('moduleGroupeFormateur', function ($q) use ($id) {
            $q->where('id_groupe', $id);
        })
            ->with(['moduleGroupeFormateur.groupe', 'moduleGroupeFormateur.module', 'moduleGroupeFormateur.formateur', 'salle'])
            ->orderBy('date_seance')
            ->orderBy('heure_debut');

        $schedulesQuery = $this->applyChefScopeToSchedules($schedulesQuery, $request->user());
        $schedules = $schedulesQuery->get();

        return response()->json([
            'success' => true,
            'data' => $schedules,
        ]);
    }

    public function getBySalle($id)
    {
        $request = request();

        $schedulesQuery = EmploiDuTemps::where('id_salle', $id)
            ->with(['moduleGroupeFormateur.groupe', 'moduleGroupeFormateur.module', 'moduleGroupeFormateur.formateur', 'salle'])
            ->orderBy('date_seance')
            ->orderBy('heure_debut');

        $schedulesQuery = $this->applyChefScopeToSchedules($schedulesQuery, $request->user());
        $schedules = $schedulesQuery->get();

        return response()->json([
            'success' => true,
            'data' => $schedules,
        ]);
    }

    public function getMyWeeklySchedule(Request $request)
    {
        $user = $request->user();

        $formateur = Formateur::where('id_user', $user->id)->first();
        if (!$formateur) {
            return response()->json([
                'success' => false,
                'message' => 'No formateur profile found for current user',
            ], 404);
        }

        $data = $request->validate([
            'start_date' => 'required|date',
        ]);

        $date = \Carbon\Carbon::parse($data['start_date']);
        $monday = $date->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
        $saturday = $monday->copy()->addDays(5);

        $schedules = EmploiDuTemps::whereBetween('date_seance', [$monday->format('Y-m-d'), $saturday->format('Y-m-d')])
            ->whereHas('moduleGroupeFormateur', function ($q) use ($formateur) {
                $q->where('id_formateur', $formateur->id);
            })
            ->with(['moduleGroupeFormateur.groupe', 'moduleGroupeFormateur.module', 'salle'])
            ->orderBy('date_seance')
            ->orderBy('heure_debut')
            ->get();

        return response()->json([
            'success' => true,
            'week' => [
                'start' => $monday->format('Y-m-d'),
                'end' => $saturday->format('Y-m-d'),
            ],
            'data' => $schedules,
        ]);
    }
}
