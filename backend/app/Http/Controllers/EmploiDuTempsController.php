<?php

namespace App\Http\Controllers;

use App\Models\EmploiDuTemps;
use App\Models\FiliereModulePhase;
use App\Models\Formateur;
use App\Models\Groupe;
use App\Models\ModuleGroupeFormateur;
use App\Models\Pole;
use App\Models\Salle;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EmploiDuTempsController extends Controller
{
    private const PDF_SLOT_BOUNDARIES = [
        '08:30',
        '09:30',
        '10:30',
        '11:30',
        '12:30',
        '13:30',
        '14:30',
        '15:30',
        '16:30',
        '17:30',
        '18:30',
    ];

    private const PDF_DAY_NAMES = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

    private const SMART_SLOT_WINDOWS = [
        ['08:30', '11:00'],
        ['11:00', '13:30'],
        ['13:30', '16:00'],
        ['16:00', '18:30'],
    ];

    private const SMART_MAX_FORMATEUR_DAILY_MINUTES = 300;

    private const SMART_MAX_GROUPE_DAILY_MINUTES = 300;

    private function resolveWeekRange(string $startDate): array
    {
        $date = Carbon::parse($startDate);
        $monday = $date->copy()->startOfWeek(Carbon::MONDAY);
        $saturday = $monday->copy()->addDays(5);

        return [$monday, $saturday];
    }

    private function getPdfSlotLabels(): array
    {
        $labels = [];
        for ($i = 0; $i < count(self::PDF_SLOT_BOUNDARIES) - 1; $i += 1) {
            $labels[] = sprintf('%s | %s', self::PDF_SLOT_BOUNDARIES[$i], self::PDF_SLOT_BOUNDARIES[$i + 1]);
        }

        return $labels;
    }

    private function overlapsPdfSlot(EmploiDuTemps $session, int $slotIdx): bool
    {
        $slotStart = $this->toMinutes(self::PDF_SLOT_BOUNDARIES[$slotIdx] ?? '08:30');
        $slotEnd = $this->toMinutes(self::PDF_SLOT_BOUNDARIES[$slotIdx + 1] ?? '18:30');
        $sessionStart = $this->toMinutes($session->heure_debut);
        $sessionEnd = $this->toMinutes($session->heure_fin);

        return $sessionStart < $slotEnd && $sessionEnd > $slotStart;
    }

    private function toMinutes(string $hhmm): int
    {
        [$h, $m] = array_map('intval', explode(':', substr($hhmm, 0, 5)));
        return ($h * 60) + $m;
    }

    private function formatHours(float $hours): string
    {
        $rounded = round($hours, 1);
        if (abs($rounded - floor($rounded)) < 0.01) {
            return sprintf('%.0f', $rounded);
        }

        return number_format($rounded, 1, '.', '');
    }

    private function computeTotalHours(Collection $schedules): float
    {
        $minutes = 0;
        foreach ($schedules as $session) {
            $minutes += max(0, $this->toMinutes($session->heure_fin) - $this->toMinutes($session->heure_debut));
        }

        return $minutes / 60;
    }

    private function resolveLogoVariant(Request $request, ?string $etablissementName = null): string
    {
        $user = $request->user();

        if (($user?->role ?? null) === 'DIRECTEUR_CMC' || ($user?->role ?? null) === 'CHEF_POLE') {
            return 'cmc';
        }

        if (($user?->role ?? null) === 'DIRECTEUR_EFP') {
            return 'efp';
        }

        if (is_string($etablissementName) && stripos($etablissementName, 'cmc') !== false) {
            return 'cmc';
        }

        return 'efp';
    }

    private function getLogoDataUri(?string $variant = null): ?string
    {
        $variant = $variant === 'cmc' ? 'cmc' : 'efp';

        $paths = $variant === 'cmc'
            ? [
                base_path('../frontend/public/cmc.png'),
                public_path('cmc.png'),
                base_path('../frontend/public/logo.png'),
                public_path('logo.png'),
            ]
            : [
                base_path('../frontend/public/logo.png'),
                public_path('logo.png'),
                base_path('../frontend/public/cmc.png'),
                public_path('cmc.png'),
            ];

        foreach ($paths as $path) {
            if (!$path || !is_file($path)) {
                continue;
            }

            $data = @file_get_contents($path);
            if ($data === false) {
                continue;
            }

            return 'data:image/png;base64,' . base64_encode($data);
        }

        return null;
    }

    private function buildPdfRows(Collection $schedules, Carbon $monday, string $mode): array
    {
        $rows = [];

        for ($dayIdx = 0; $dayIdx < count(self::PDF_DAY_NAMES); $dayIdx += 1) {
            $dayDate = $monday->copy()->addDays($dayIdx)->format('Y-m-d');

            $daySessions = $schedules
                ->filter(fn ($s) => $s->date_seance === $dayDate)
                ->sortBy(fn ($s) => $this->toMinutes($s->heure_debut))
                ->values();

            $cells = [];
            $slotIdx = 0;

            while ($slotIdx < count(self::PDF_SLOT_BOUNDARIES) - 1) {
                $session = $daySessions->first(fn ($s) => $this->overlapsPdfSlot($s, $slotIdx));

                if (!$session) {
                    $cells[] = [
                        'type' => 'empty',
                        'colspan' => 1,
                    ];
                    $slotIdx += 1;
                    continue;
                }

                $span = 0;
                while ($slotIdx + $span < count(self::PDF_SLOT_BOUNDARIES) - 1 && $this->overlapsPdfSlot($session, $slotIdx + $span)) {
                    $span += 1;
                }

                $formateurName = trim(($session->moduleGroupeFormateur->formateur->user->nom ?? '') . ' ' . ($session->moduleGroupeFormateur->formateur->user->prenom ?? ''));
                $groupeName = $session->moduleGroupeFormateur->groupe->nom_groupe ?? '-';
                $moduleName = $session->moduleGroupeFormateur->module->nom_module ?? '-';
                $salleName = $session->salle?->nom_salle;

                $cells[] = [
                    'type' => 'session',
                    'colspan' => max(1, $span),
                    'line1' => $mode === 'groupe' ? Str::upper($formateurName ?: '-') : Str::upper($moduleName),
                    'line2' => $mode === 'groupe'
                        ? trim($moduleName . ($salleName ? ' - ' . $salleName : ''))
                        : trim($groupeName . ($salleName ? ' - ' . $salleName : '')),
                ];

                $slotIdx += max(1, $span);
            }

            $rows[] = [
                'day' => self::PDF_DAY_NAMES[$dayIdx],
                'cells' => $cells,
            ];
        }

        return $rows;
    }

    private function schedulePdfDownload(string $fileName, array $viewData)
    {
        return Pdf::loadView('pdfs.emploi-du-temps-weekly', $viewData)
            ->setPaper('a4', 'landscape')
            ->download($fileName);
    }

    private function isPastScheduleDate(string $date): bool
    {
        return Carbon::parse($date)->startOfDay()->lt(Carbon::today());
    }

    private function pastScheduleLockedResponse()
    {
        return response()->json([
            'success' => false,
            'message' => 'Les seances passees sont verrouillees et ne peuvent pas etre modifiees.',
        ], 403);
    }

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

        $query = $query
            ->whereHas('moduleGroupeFormateur.module.filiere', function ($q) use ($chefPoleIds) {
                $q->where(function ($scopeQuery) use ($chefPoleIds) {
                    $scopeQuery
                        ->whereIn('id_pole', $chefPoleIds)
                        ->orWhereNull('id_pole');
                });
            })
            ->whereHas('moduleGroupeFormateur.groupe.filiere', function ($q) use ($chefPoleIds) {
                $q->where(function ($scopeQuery) use ($chefPoleIds) {
                    $scopeQuery
                        ->whereIn('id_pole', $chefPoleIds)
                        ->orWhereNull('id_pole');
                });
            })
            ->whereHas('salle', function ($q) use ($chefPoleIds) {
                $q->whereIn('id_pole', $chefPoleIds);
            });

        if (Schema::hasColumn('module_groupe_formateur', 'id_etablissement') && $user?->id_etablissement) {
            $query->whereHas('moduleGroupeFormateur', function ($q) use ($user) {
                $q->where('id_etablissement', $user->id_etablissement);
            });
        }

        return $query;
    }

    private function validateChefScheduleData(Request $request, int $moduleId, int $groupeId, int $formateurId, int $salleId)
    {
        $user = $request->user();
        $chefPoleIds = $this->getChefPoleIds($request->user());
        if ($chefPoleIds === null) {
            return null;
        }

        $assignmentQuery = ModuleGroupeFormateur::where('id_module', $moduleId)
            ->where('id_groupe', $groupeId)
            ->where('id_formateur', $formateurId)
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
            ->whereHas('formateur', function ($q) use ($chefPoleIds) {
                $q->whereHas('poles', function ($p) use ($chefPoleIds) {
                    $p->whereIn('poles.id', $chefPoleIds);
                });
            });

        if (Schema::hasColumn('module_groupe_formateur', 'id_etablissement') && $user?->id_etablissement) {
            $assignmentQuery->where('id_etablissement', $user->id_etablissement);
        }

        $assignmentAllowed = $assignmentQuery->exists();

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

    private function buildPhaseRankByModule(Collection $assignments): array
    {
        $filiereIds = $assignments
            ->map(fn ($a) => (int) ($a->module?->id_filiere ?? 0))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($filiereIds->isEmpty()) {
            return [];
        }

        $rowsByFiliere = FiliereModulePhase::query()
            ->whereIn('id_filiere', $filiereIds->all())
            ->get(['id_filiere', 'id_module', 'id_previous_module'])
            ->groupBy('id_filiere');

        $phaseByModule = [];

        foreach ($rowsByFiliere as $filiereRows) {
            $previousByModule = [];
            foreach ($filiereRows as $row) {
                $previousByModule[(int) $row->id_module] = $row->id_previous_module ? (int) $row->id_previous_module : null;
            }

            $resolver = function (int $moduleId, array &$cache, array $path) use (&$resolver, $previousByModule): int {
                if (isset($cache[$moduleId])) {
                    return $cache[$moduleId];
                }

                if (in_array($moduleId, $path, true)) {
                    $cache[$moduleId] = 1;
                    return 1;
                }

                $previous = $previousByModule[$moduleId] ?? null;
                if (!$previous) {
                    $cache[$moduleId] = 1;
                    return 1;
                }

                $path[] = $moduleId;
                $cache[$moduleId] = $resolver((int) $previous, $cache, $path) + 1;
                return $cache[$moduleId];
            };

            $cache = [];
            foreach ($previousByModule as $moduleId => $_) {
                $phaseByModule[(int) $moduleId] = $resolver((int) $moduleId, $cache, []);
            }
        }

        return $phaseByModule;
    }

    private function intervalOverlap(int $startA, int $endA, int $startB, int $endB): bool
    {
        return $startA < $endB && $endA > $startB;
    }

    private function appendOccupancy(array &$occupancy, string $date, int $startMin, int $endMin, int $groupeId, int $formateurId, int $salleId): void
    {
        if (!isset($occupancy[$date])) {
            $occupancy[$date] = [
                'groupes' => [],
                'formateurs' => [],
                'salles' => [],
            ];
        }

        $occupancy[$date]['groupes'][$groupeId][] = [$startMin, $endMin];
        $occupancy[$date]['formateurs'][$formateurId][] = [$startMin, $endMin];
        $occupancy[$date]['salles'][$salleId][] = [$startMin, $endMin];
    }

    private function hasEntityConflict(array $occupancy, string $date, string $entity, int $entityId, int $startMin, int $endMin): bool
    {
        $intervals = $occupancy[$date][$entity][$entityId] ?? [];
        foreach ($intervals as $interval) {
            $busyStart = (int) ($interval[0] ?? 0);
            $busyEnd = (int) ($interval[1] ?? 0);
            if ($this->intervalOverlap($startMin, $endMin, $busyStart, $busyEnd)) {
                return true;
            }
        }

        return false;
    }

    public function generateWeeklySmart(Request $request)
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'target_type' => 'required|in:formateur,groupe',
            'target_id' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $targetType = (string) $data['target_type'];
        $targetId = (int) $data['target_id'];
        [$monday, $saturday] = $this->resolveWeekRange($data['start_date']);

        $assignmentsQuery = ModuleGroupeFormateur::query()
            ->with(['module', 'groupe', 'formateur'])
            ->where($targetType === 'formateur' ? 'id_formateur' : 'id_groupe', $targetId);

        if (Schema::hasColumn('module_groupe_formateur', 'id_etablissement') && $user?->id_etablissement) {
            $assignmentsQuery->where('id_etablissement', $user->id_etablissement);
        }

        if (($user?->role ?? null) === 'CHEF_POLE') {
            $chefPoleIds = $this->getChefPoleIds($user);
            if ($chefPoleIds === null || $chefPoleIds->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun pole associe a votre compte chef de pole.',
                ], 403);
            }

            $assignmentsQuery
                ->whereHas('module.filiere', function ($q) use ($chefPoleIds) {
                    $q->where(function ($scopeQuery) use ($chefPoleIds) {
                        $scopeQuery->whereIn('id_pole', $chefPoleIds)->orWhereNull('id_pole');
                    });
                })
                ->whereHas('groupe.filiere', function ($q) use ($chefPoleIds) {
                    $q->where(function ($scopeQuery) use ($chefPoleIds) {
                        $scopeQuery->whereIn('id_pole', $chefPoleIds)->orWhereNull('id_pole');
                    });
                });
        }

        $assignments = $assignmentsQuery->get();

        if ($assignments->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune affectation trouvee pour la cible selectionnee.',
            ], 404);
        }

        $assignmentIds = $assignments->pluck('id')->map(fn ($id) => (int) $id)->all();
        $existingByAssignment = EmploiDuTemps::query()
            ->whereIn('id_module_groupe_formateur', $assignmentIds)
            ->get(['id_module_groupe_formateur', 'heure_debut', 'heure_fin']);

        $plannedMinutesByAssignment = [];
        foreach ($existingByAssignment as $entry) {
            $duration = max(0, $this->toMinutes((string) $entry->heure_fin) - $this->toMinutes((string) $entry->heure_debut));
            $assignmentId = (int) $entry->id_module_groupe_formateur;
            $plannedMinutesByAssignment[$assignmentId] = (int) ($plannedMinutesByAssignment[$assignmentId] ?? 0) + $duration;
        }

        $phaseByModule = $this->buildPhaseRankByModule($assignments);

        $candidatePool = $assignments
            ->map(function ($assignment) use ($plannedMinutesByAssignment, $phaseByModule) {
                $assignmentId = (int) $assignment->id;
                $moduleMinutes = (int) round(((float) ($assignment->module?->volume_horaire ?? 0)) * 60);
                $alreadyPlanned = (int) ($plannedMinutesByAssignment[$assignmentId] ?? 0);
                $remainingMinutes = max(0, $moduleMinutes - $alreadyPlanned);

                return [
                    'assignment' => $assignment,
                    'assignment_id' => $assignmentId,
                    'phase' => (int) ($phaseByModule[(int) $assignment->id_module] ?? 1),
                    'remaining_minutes' => $remainingMinutes,
                    'scheduled_minutes_this_run' => 0,
                ];
            })
            ->filter(fn ($row) => (int) $row['remaining_minutes'] > 0)
            ->values();

        if ($candidatePool->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Tous les modules de la cible sont deja planifies.',
                'generated_count' => 0,
                'data' => [],
            ]);
        }

        $sallesQuery = Salle::query();

        if (($user?->role ?? null) === 'CHEF_POLE') {
            $chefPoleIds = $this->getChefPoleIds($user);
            $sallesQuery->whereIn('id_pole', $chefPoleIds ?? []);
        } elseif (($user?->role ?? null) === 'DIRECTEUR_EFP' && $user?->id_etablissement) {
            $sallesQuery->where('id_etablissement', $user->id_etablissement);
        }

        $salles = $sallesQuery->get(['id', 'nom_salle']);

        if ($salles->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune salle disponible dans votre perimetre.',
            ], 422);
        }

        $existingWeekSessions = EmploiDuTemps::query()
            ->whereBetween('date_seance', [$monday->format('Y-m-d'), $saturday->format('Y-m-d')])
            ->with(['moduleGroupeFormateur'])
            ->get();

        $occupancy = [];
        $targetBusySlots = [];
        $formateurDailyUsedMinutes = [];
        $groupeDailyUsedMinutes = [];

        foreach ($existingWeekSessions as $session) {
            if (!$session->moduleGroupeFormateur) {
                continue;
            }

            $sessionDate = (string) $session->date_seance;
            $startMin = $this->toMinutes((string) $session->heure_debut);
            $endMin = $this->toMinutes((string) $session->heure_fin);
            $groupeId = (int) $session->moduleGroupeFormateur->id_groupe;
            $formateurId = (int) $session->moduleGroupeFormateur->id_formateur;
            $salleId = (int) $session->id_salle;

            $this->appendOccupancy($occupancy, $sessionDate, $startMin, $endMin, $groupeId, $formateurId, $salleId);

            if (!isset($formateurDailyUsedMinutes[$sessionDate])) {
                $formateurDailyUsedMinutes[$sessionDate] = [];
            }
            if (!isset($groupeDailyUsedMinutes[$sessionDate])) {
                $groupeDailyUsedMinutes[$sessionDate] = [];
            }

            $durationMinutes = max(0, $endMin - $startMin);
            $formateurDailyUsedMinutes[$sessionDate][$formateurId] = (int) ($formateurDailyUsedMinutes[$sessionDate][$formateurId] ?? 0) + $durationMinutes;
            $groupeDailyUsedMinutes[$sessionDate][$groupeId] = (int) ($groupeDailyUsedMinutes[$sessionDate][$groupeId] ?? 0) + $durationMinutes;

            if ($targetType === 'formateur' && $formateurId === $targetId) {
                $targetBusySlots[$sessionDate][] = [$startMin, $endMin];
            }
            if ($targetType === 'groupe' && $groupeId === $targetId) {
                $targetBusySlots[$sessionDate][] = [$startMin, $endMin];
            }
        }

        $assignmentById = [];
        foreach ($candidatePool as $row) {
            $assignmentById[(int) $row['assignment_id']] = $row;
        }

        $formateurWeekRemaining = [];
        $formateurIds = $assignments->pluck('id_formateur')->map(fn ($id) => (int) $id)->unique()->values();
        $formateurs = Formateur::query()->whereIn('id', $formateurIds->all())->get(['id', 'MHS']);

        foreach ($formateurs as $formateur) {
            $mhs = is_numeric($formateur->MHS) ? (float) $formateur->MHS : 0.0;
            if ($mhs <= 0) {
                $formateurWeekRemaining[(int) $formateur->id] = PHP_INT_MAX;
                continue;
            }

            $alreadyPlannedMinutes = EmploiDuTemps::query()
                ->whereBetween('date_seance', [$monday->format('Y-m-d'), $saturday->format('Y-m-d')])
                ->whereHas('moduleGroupeFormateur', function ($q) use ($formateur) {
                    $q->where('id_formateur', (int) $formateur->id);
                })
                ->get(['heure_debut', 'heure_fin'])
                ->reduce(function ($carry, $session) {
                    return $carry + max(0, $this->toMinutes((string) $session->heure_fin) - $this->toMinutes((string) $session->heure_debut));
                }, 0);

            $formateurWeekRemaining[(int) $formateur->id] = max(0, (int) round($mhs * 60) - (int) $alreadyPlannedMinutes);
        }

        $today = Carbon::today();
        $generated = [];
        $lastGeneratedByAssignmentDate = [];
        $skippedPastDays = 0;

        for ($dayCursor = $monday->copy(); $dayCursor->lte($saturday); $dayCursor->addDay()) {
            if ($dayCursor->isSunday()) {
                continue;
            }

            if ($dayCursor->lt($today)) {
                $skippedPastDays += 1;
                continue;
            }

            $dateStr = $dayCursor->format('Y-m-d');
            $targetIntervals = $targetBusySlots[$dateStr] ?? [];

            foreach (self::SMART_SLOT_WINDOWS as $slotWindow) {
                $slotStartMin = $this->toMinutes($slotWindow[0]);
                $slotEndMin = $this->toMinutes($slotWindow[1]);

                $targetSlotTaken = false;
                foreach ($targetIntervals as $targetInterval) {
                    $busyStart = (int) ($targetInterval[0] ?? 0);
                    $busyEnd = (int) ($targetInterval[1] ?? 0);
                    if ($this->intervalOverlap($slotStartMin, $slotEndMin, $busyStart, $busyEnd)) {
                        $targetSlotTaken = true;
                        break;
                    }
                }

                if ($targetSlotTaken) {
                    continue;
                }

                uasort($assignmentById, function ($a, $b) {
                    $phaseCmp = ((int) $a['phase']) <=> ((int) $b['phase']);
                    if ($phaseCmp !== 0) {
                        return $phaseCmp;
                    }

                    $remainingCmp = ((int) $b['remaining_minutes']) <=> ((int) $a['remaining_minutes']);
                    if ($remainingCmp !== 0) {
                        return $remainingCmp;
                    }

                    return ((int) $a['scheduled_minutes_this_run']) <=> ((int) $b['scheduled_minutes_this_run']);
                });

                $chosen = null;
                $chosenSalleId = null;
                $chosenStart = $slotStartMin;
                $chosenEnd = null;
                $chosenCandidateId = null;

                $preferredCandidateId = null;
                $preferredKey = null;
                foreach ($assignmentById as $candidateId => $candidateRow) {
                    $candidateMergeKey = $dateStr . '|' . (int) $candidateId;
                    $lastGenerated = $lastGeneratedByAssignmentDate[$candidateMergeKey] ?? null;
                    if ($lastGenerated && (int) ($lastGenerated['end'] ?? -1) === $slotStartMin) {
                        $preferredCandidateId = (int) $candidateId;
                        $preferredKey = $candidateMergeKey;
                        break;
                    }
                }

                if ($preferredCandidateId !== null && isset($assignmentById[$preferredCandidateId])) {
                    $candidate = $assignmentById[$preferredCandidateId];
                    $assignment = $candidate['assignment'];
                    $groupeId = (int) $assignment->id_groupe;
                    $formateurId = (int) $assignment->id_formateur;
                    $remainingMinutes = (int) $candidate['remaining_minutes'];
                    $formateurRemaining = (int) ($formateurWeekRemaining[$formateurId] ?? PHP_INT_MAX);
                    $formateurDailyUsed = (int) ($formateurDailyUsedMinutes[$dateStr][$formateurId] ?? 0);
                    $groupeDailyUsed = (int) ($groupeDailyUsedMinutes[$dateStr][$groupeId] ?? 0);
                    $formateurDailyRemaining = max(0, self::SMART_MAX_FORMATEUR_DAILY_MINUTES - $formateurDailyUsed);
                    $groupeDailyRemaining = max(0, self::SMART_MAX_GROUPE_DAILY_MINUTES - $groupeDailyUsed);
                    $slotCapacity = $slotEndMin - $slotStartMin;
                    $duration = min($slotCapacity, $remainingMinutes, $formateurRemaining, $formateurDailyRemaining, $groupeDailyRemaining);

                    if ($duration >= 60) {
                        $candidateEnd = $slotStartMin + $duration;
                        if (
                            !$this->hasEntityConflict($occupancy, $dateStr, 'groupes', $groupeId, $slotStartMin, $candidateEnd)
                            && !$this->hasEntityConflict($occupancy, $dateStr, 'formateurs', $formateurId, $slotStartMin, $candidateEnd)
                        ) {
                            $availableSalleId = null;
                            $lastGenerated = $lastGeneratedByAssignmentDate[$preferredKey] ?? null;
                            $preferredSalleId = (int) ($lastGenerated['salle_id'] ?? 0);

                            if ($preferredSalleId && !$this->hasEntityConflict($occupancy, $dateStr, 'salles', $preferredSalleId, $slotStartMin, $candidateEnd)) {
                                $availableSalleId = $preferredSalleId;
                            }

                            if ($availableSalleId === null) {
                                foreach ($salles as $salle) {
                                    $salleId = (int) $salle->id;
                                    if ($this->hasEntityConflict($occupancy, $dateStr, 'salles', $salleId, $slotStartMin, $candidateEnd)) {
                                        continue;
                                    }

                                    $availableSalleId = $salleId;
                                    break;
                                }
                            }

                            if ($availableSalleId !== null) {
                                $chosen = $candidate;
                                $chosenSalleId = $availableSalleId;
                                $chosenEnd = $candidateEnd;
                                $chosenCandidateId = $preferredCandidateId;
                            }
                        }
                    }
                }

                foreach ($assignmentById as $candidateId => $candidate) {
                    if ($chosenCandidateId !== null && $chosenCandidateId === (int) $candidateId) {
                        continue;
                    }

                    $assignment = $candidate['assignment'];
                    $groupeId = (int) $assignment->id_groupe;
                    $formateurId = (int) $assignment->id_formateur;

                    $remainingMinutes = (int) $candidate['remaining_minutes'];
                    if ($remainingMinutes <= 0) {
                        continue;
                    }

                    $formateurRemaining = (int) ($formateurWeekRemaining[$formateurId] ?? PHP_INT_MAX);
                    if ($formateurRemaining <= 0) {
                        continue;
                    }

                    $formateurDailyUsed = (int) ($formateurDailyUsedMinutes[$dateStr][$formateurId] ?? 0);
                    $groupeDailyUsed = (int) ($groupeDailyUsedMinutes[$dateStr][$groupeId] ?? 0);
                    $formateurDailyRemaining = max(0, self::SMART_MAX_FORMATEUR_DAILY_MINUTES - $formateurDailyUsed);
                    $groupeDailyRemaining = max(0, self::SMART_MAX_GROUPE_DAILY_MINUTES - $groupeDailyUsed);

                    if ($formateurDailyRemaining <= 0 || $groupeDailyRemaining <= 0) {
                        continue;
                    }

                    $slotCapacity = $slotEndMin - $slotStartMin;
                    $duration = min($slotCapacity, $remainingMinutes, $formateurRemaining, $formateurDailyRemaining, $groupeDailyRemaining);
                    if ($duration < 60) {
                        continue;
                    }

                    $candidateEnd = $slotStartMin + $duration;

                    if ($this->hasEntityConflict($occupancy, $dateStr, 'groupes', $groupeId, $slotStartMin, $candidateEnd)) {
                        continue;
                    }

                    if ($this->hasEntityConflict($occupancy, $dateStr, 'formateurs', $formateurId, $slotStartMin, $candidateEnd)) {
                        continue;
                    }

                    $availableSalleId = null;
                    $preferredSalleId = null;
                    $mergeKey = $dateStr . '|' . (int) $candidate['assignment_id'];
                    $lastGenerated = $lastGeneratedByAssignmentDate[$mergeKey] ?? null;
                    if ($lastGenerated && (int) ($lastGenerated['end'] ?? -1) === $slotStartMin) {
                        $preferredSalleId = (int) ($lastGenerated['salle_id'] ?? 0);
                    }

                    if ($preferredSalleId) {
                        if (!$this->hasEntityConflict($occupancy, $dateStr, 'salles', $preferredSalleId, $slotStartMin, $candidateEnd)) {
                            $availableSalleId = $preferredSalleId;
                        }
                    }

                    if ($availableSalleId === null) {
                        foreach ($salles as $salle) {
                            $salleId = (int) $salle->id;
                            if ($this->hasEntityConflict($occupancy, $dateStr, 'salles', $salleId, $slotStartMin, $candidateEnd)) {
                                continue;
                            }

                            $availableSalleId = $salleId;
                            break;
                        }
                    }

                    if ($availableSalleId === null) {
                        continue;
                    }

                    $chosen = $candidate;
                    $chosenSalleId = $availableSalleId;
                    $chosenEnd = $candidateEnd;
                    $chosenCandidateId = (int) $candidate['assignment_id'];
                    break;
                }

                if (!$chosen || !$chosenSalleId || $chosenEnd === null || $chosenCandidateId === null) {
                    continue;
                }

                /** @var ModuleGroupeFormateur $chosenAssignment */
                $chosenAssignment = $chosen['assignment'];
                $duration = $chosenEnd - $chosenStart;
                $candidateId = $chosenCandidateId;
                $assignmentById[$candidateId]['remaining_minutes'] = max(0, (int) $assignmentById[$candidateId]['remaining_minutes'] - $duration);
                $assignmentById[$candidateId]['scheduled_minutes_this_run'] = (int) $assignmentById[$candidateId]['scheduled_minutes_this_run'] + $duration;

                $formateurId = (int) $chosenAssignment->id_formateur;
                $groupeId = (int) $chosenAssignment->id_groupe;
                if (($formateurWeekRemaining[$formateurId] ?? PHP_INT_MAX) !== PHP_INT_MAX) {
                    $formateurWeekRemaining[$formateurId] = max(0, (int) $formateurWeekRemaining[$formateurId] - $duration);
                }

                if (!isset($formateurDailyUsedMinutes[$dateStr])) {
                    $formateurDailyUsedMinutes[$dateStr] = [];
                }
                if (!isset($groupeDailyUsedMinutes[$dateStr])) {
                    $groupeDailyUsedMinutes[$dateStr] = [];
                }
                $formateurDailyUsedMinutes[$dateStr][$formateurId] = (int) ($formateurDailyUsedMinutes[$dateStr][$formateurId] ?? 0) + $duration;
                $groupeDailyUsedMinutes[$dateStr][$groupeId] = (int) ($groupeDailyUsedMinutes[$dateStr][$groupeId] ?? 0) + $duration;

                $this->appendOccupancy(
                    $occupancy,
                    $dateStr,
                    $chosenStart,
                    $chosenEnd,
                    $groupeId,
                    $formateurId,
                    (int) $chosenSalleId
                );

                $targetBusySlots[$dateStr][] = [$chosenStart, $chosenEnd];
                $targetIntervals[] = [$chosenStart, $chosenEnd];

                $mergeKey = $dateStr . '|' . $candidateId;
                $lastGenerated = $lastGeneratedByAssignmentDate[$mergeKey] ?? null;

                if (
                    $lastGenerated
                    && (int) ($lastGenerated['end'] ?? -1) === $chosenStart
                    && (int) ($lastGenerated['salle_id'] ?? 0) === (int) $chosenSalleId
                ) {
                    /** @var EmploiDuTemps $lastModel */
                    $lastModel = $lastGenerated['model'];
                    $lastModel->heure_fin = $this->minutesToHHMM($chosenEnd);
                    $lastModel->save();

                    $generatedIndex = (int) ($lastGenerated['generated_index'] ?? -1);
                    if ($generatedIndex >= 0 && isset($generated[$generatedIndex])) {
                        $generated[$generatedIndex]['heure_fin'] = $this->minutesToHHMM($chosenEnd);
                    }

                    $lastGeneratedByAssignmentDate[$mergeKey]['end'] = $chosenEnd;
                    $lastGeneratedByAssignmentDate[$mergeKey]['model'] = $lastModel;
                } else {
                    $created = EmploiDuTemps::query()->create([
                        'date_seance' => $dateStr,
                        'heure_debut' => $this->minutesToHHMM($chosenStart),
                        'heure_fin' => $this->minutesToHHMM($chosenEnd),
                        'id_module_groupe_formateur' => (int) $chosenAssignment->id,
                        'id_module' => (int) $chosenAssignment->id_module,
                        'id_salle' => (int) $chosenSalleId,
                    ]);

                    $generated[] = [
                        'id' => (int) $created->id,
                        'date_seance' => $dateStr,
                        'heure_debut' => $this->minutesToHHMM($chosenStart),
                        'heure_fin' => $this->minutesToHHMM($chosenEnd),
                        'id_module_groupe_formateur' => (int) $chosenAssignment->id,
                        'id_module' => (int) $chosenAssignment->id_module,
                        'id_groupe' => (int) $chosenAssignment->id_groupe,
                        'id_formateur' => (int) $chosenAssignment->id_formateur,
                        'id_salle' => (int) $chosenSalleId,
                    ];

                    $lastGeneratedByAssignmentDate[$mergeKey] = [
                        'end' => $chosenEnd,
                        'salle_id' => (int) $chosenSalleId,
                        'model' => $created,
                        'generated_index' => count($generated) - 1,
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => count($generated) > 0
                ? 'Generation intelligente terminee.'
                : 'Aucun nouveau creneau viable trouve pour cette semaine.',
            'generated_count' => count($generated),
            'skipped_past_days' => $skippedPastDays,
            'week' => [
                'start' => $monday->format('Y-m-d'),
                'end' => $saturday->format('Y-m-d'),
            ],
            'data' => $generated,
        ]);
    }

    private function minutesToHHMM(int $totalMinutes): string
    {
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
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

        if ($this->isPastScheduleDate($data['date_seance'])) {
            return $this->pastScheduleLockedResponse();
        }

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
                'id_module' => $data['id_module'],
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

        if ($this->isPastScheduleDate($emploi->date_seance)) {
            return $this->pastScheduleLockedResponse();
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

        if ($this->isPastScheduleDate($targetDate)) {
            return $this->pastScheduleLockedResponse();
        }

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
                'id_module' => $targetModuleId,
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

        if ($this->isPastScheduleDate($emploi->date_seance)) {
            return $this->pastScheduleLockedResponse();
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

    public function downloadFormateurWeeklyPdf(Request $request, int $id)
    {
        $data = $request->validate([
            'start_date' => 'required|date',
        ]);

        [$monday, $saturday] = $this->resolveWeekRange($data['start_date']);

        $formateur = Formateur::with(['user', 'etablissement', 'poles'])
            ->find($id);

        if (!$formateur) {
            return response()->json([
                'success' => false,
                'message' => 'Formateur not found',
            ], 404);
        }

        $query = EmploiDuTemps::whereBetween('date_seance', [$monday->format('Y-m-d'), $saturday->format('Y-m-d')])
            ->whereHas('moduleGroupeFormateur', function ($q) use ($id) {
                $q->where('id_formateur', $id);
            })
            ->with([
                'moduleGroupeFormateur.groupe.filiere.pole.etablissement',
                'moduleGroupeFormateur.module.filiere.pole',
                'moduleGroupeFormateur.formateur.user',
                'salle',
            ])
            ->orderBy('date_seance')
            ->orderBy('heure_debut');

        $query = $this->applyChefScopeToSchedules($query, $request->user());
        $schedules = $query->get();

        $firstSchedule = $schedules->first();
        $poleName = $firstSchedule?->moduleGroupeFormateur?->module?->filiere?->pole?->nom_pole
            ?? $formateur->poles->first()?->nom_pole
            ?? '-';
        $etablissementName = $firstSchedule?->moduleGroupeFormateur?->module?->filiere?->pole?->etablissement?->nom_etablissement
            ?? $formateur->etablissement?->nom_etablissement
            ?? '-';

        $formateurName = trim(($formateur->user->nom ?? '') . ' ' . ($formateur->user->prenom ?? ''));
        $matricule = $formateur->user->matricule ?? '-';

        $fileName = sprintf('emploi-du-temps-formateur-%s-%s.pdf', Str::slug($formateurName ?: 'formateur'), $monday->format('Y-m-d'));

        return $this->schedulePdfDownload($fileName, [
            'title' => 'Emploi du temps par Formateur',
            'slotLabels' => $this->getPdfSlotLabels(),
            'rows' => $this->buildPdfRows($schedules, $monday, 'formateur'),
            'logoDataUri' => $this->getLogoDataUri($this->resolveLogoVariant($request, $etablissementName)),
            'metaLeft' => [
                $etablissementName,
                'Pole: ' . $poleName,
            ],
            'metaRight' => [
                sprintf('Formateur : %s | MLE : %s', Str::upper($formateurName ?: '-'), $matricule),
                sprintf('Semaine : %s au %s', $monday->format('d/m/Y'), $saturday->format('d/m/Y')),
            ],
        ]);
    }

    public function downloadGroupeWeeklyPdf(Request $request, int $id)
    {
        $data = $request->validate([
            'start_date' => 'required|date',
        ]);

        [$monday, $saturday] = $this->resolveWeekRange($data['start_date']);

        $groupe = Groupe::with(['filiere.pole.etablissement'])->find($id);

        if (!$groupe) {
            return response()->json([
                'success' => false,
                'message' => 'Groupe not found',
            ], 404);
        }

        $query = EmploiDuTemps::whereBetween('date_seance', [$monday->format('Y-m-d'), $saturday->format('Y-m-d')])
            ->whereHas('moduleGroupeFormateur', function ($q) use ($id) {
                $q->where('id_groupe', $id);
            })
            ->with([
                'moduleGroupeFormateur.groupe.filiere.pole.etablissement',
                'moduleGroupeFormateur.module',
                'moduleGroupeFormateur.formateur.user',
                'salle',
            ])
            ->orderBy('date_seance')
            ->orderBy('heure_debut');

        $query = $this->applyChefScopeToSchedules($query, $request->user());
        $schedules = $query->get();

        $fileName = sprintf('emploi-du-temps-groupe-%s-%s.pdf', Str::slug($groupe->nom_groupe ?: 'groupe'), $monday->format('Y-m-d'));
        $totalHours = $this->formatHours($this->computeTotalHours($schedules));

        return $this->schedulePdfDownload($fileName, [
            'title' => 'Emploi du temps par groupe',
            'slotLabels' => $this->getPdfSlotLabels(),
            'rows' => $this->buildPdfRows($schedules, $monday, 'groupe'),
            'logoDataUri' => $this->getLogoDataUri($this->resolveLogoVariant($request, $groupe->filiere?->pole?->etablissement?->nom_etablissement)),
            'metaLeft' => [
                $groupe->filiere?->pole?->etablissement?->nom_etablissement ?? '-',
                'Pole: ' . ($groupe->filiere?->pole?->nom_pole ?? '-'),
                'Filiere : ' . ($groupe->filiere?->nom_filiere ?? '-'),
            ],
            'metaRight' => [
                sprintf('Groupe : %s | Total : %sH', $groupe->nom_groupe ?? '-', $totalHours),
                sprintf('Semaine : %s au %s', $monday->format('d/m/Y'), $saturday->format('d/m/Y')),
            ],
        ]);
    }

    public function downloadMyWeeklyPdf(Request $request)
    {
        $user = $request->user();
        $formateur = Formateur::where('id_user', $user->id)->first();

        if (!$formateur) {
            return response()->json([
                'success' => false,
                'message' => 'No formateur profile found for current user',
            ], 404);
        }

        return $this->downloadFormateurWeeklyPdf($request, (int) $formateur->id);
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
