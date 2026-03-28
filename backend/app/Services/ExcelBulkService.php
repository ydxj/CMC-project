<?php

namespace App\Services;

use App\Exports\BaseExport;
use App\Imports\BaseImport;
use App\Models\Filiere;
use App\Models\Formateur;
use App\Models\Groupe;
use App\Models\Module;
use App\Models\Pole;
use App\Models\Salle;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ExcelBulkService
{
    public function downloadTemplate(string $entityKey): BinaryFileResponse
    {
        $config = $this->entityConfig($entityKey);
        $filename = sprintf('%s-import-template.xlsx', $entityKey);

        return Excel::download(
            new BaseExport($config['template_headings'], $config['template_sample_rows']),
            $filename
        );
    }

    public function import(Request $request, string $entityKey): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $config = $this->entityConfig($entityKey);
        $import = new BaseImport();

        try {
            Excel::import($import, $request->file('file'));
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to parse uploaded file',
                'error' => $e->getMessage(),
            ], 422);
        }

        $rows = $import->rows();
        $errors = [];
        $inserted = 0;
        $skipped = 0;
        $context = [
            'seen' => [],
            'chef_pole_ids' => null,
        ];

        foreach ($rows as $idx => $row) {
            $excelRowNumber = $idx + 2;
            $payload = $this->normalizeRowPayload($entityKey, $row);

            $validator = Validator::make(
                $payload,
                $config['rules']($payload, $request),
                $config['messages'] ?? []
            );

            if ($validator->fails()) {
                foreach ($validator->errors()->toArray() as $field => $messages) {
                    foreach ($messages as $message) {
                        $errors[] = [
                            'row' => $excelRowNumber,
                            'field' => $field,
                            'message' => $message,
                        ];
                    }
                }
                $skipped++;
                continue;
            }

            $duplicateErrors = $config['duplicate_check']($payload, $request, $context);
            if (!empty($duplicateErrors)) {
                foreach ($duplicateErrors as $field => $message) {
                    $errors[] = [
                        'row' => $excelRowNumber,
                        'field' => $field,
                        'message' => $message,
                    ];
                }
                $skipped++;
                continue;
            }

            try {
                DB::transaction(function () use ($config, $payload, $request, &$context) {
                    $config['create']($payload, $request, $context);
                });
                $inserted++;
            } catch (Throwable $e) {
                $errors[] = [
                    'row' => $excelRowNumber,
                    'field' => 'row',
                    'message' => $e->getMessage(),
                ];
                $skipped++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Import processed',
            'summary' => [
                'total_rows' => $rows->count(),
                'inserted_rows' => $inserted,
                'skipped_rows' => $skipped,
                'error_rows' => count($errors),
            ],
            'errors' => $errors,
        ]);
    }

    private function normalizeRowPayload(string $entityKey, array $row): array
    {
        $payload = [];
        foreach ($row as $key => $value) {
            $normalizedKey = strtolower(trim((string) $key));
            $normalizedKey = str_replace([' ', '-', '.'], '_', $normalizedKey);
            $payload[$normalizedKey] = $value;
        }

        if ($entityKey === 'users') {
            foreach (['nom', 'prenom', 'email', 'password', 'role', 'matricule', 'type_formateur'] as $field) {
                if (array_key_exists($field, $payload) && $payload[$field] !== null) {
                    $payload[$field] = trim((string) $payload[$field]);
                }
            }
        }

        if ($entityKey === 'groupes' && empty($payload['crenau']) && !empty($payload['creneau'])) {
            $payload['crenau'] = $payload['creneau'];
        }

        return $payload;
    }

    private function actorEtablissementId(Request $request): ?int
    {
        $actor = $request->user();
        if (!$actor || !$actor->id_etablissement) {
            return null;
        }

        return (int) $actor->id_etablissement;
    }

    private function chefPoleIds(Request $request): ?array
    {
        $actor = $request->user();
        if (!$actor || $actor->role !== User::ROLE_CHEF_POLE) {
            return null;
        }

        $query = Pole::query()->where('id_chef', $actor->id);
        if ($actor->id_etablissement) {
            $query->where('id_etablissement', $actor->id_etablissement);
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function filiereInScope(Request $request, int $filiereId): bool
    {
        $actor = $request->user();
        if (!$actor) {
            return true;
        }

        $query = Filiere::query()->where('id', $filiereId);

        if ($actor->role === User::ROLE_CHEF_POLE) {
            $poleIds = $this->chefPoleIds($request) ?? [];
            if (!empty($poleIds)) {
                $query->whereIn('id_pole', $poleIds);
            }
        }

        if ($actor->role !== User::ROLE_DOSI && $actor->id_etablissement) {
            $query->where('id_etablissement', $actor->id_etablissement);
        }

        return $query->exists();
    }

    private function entityConfig(string $entityKey): array
    {
        $configs = [
            'users' => [
                'template_headings' => ['nom', 'prenom', 'email', 'password', 'role', 'matricule', 'type_formateur', 'mhs', 'nbr_semaine'],
                'template_sample_rows' => [
                    ['Doe', 'John', 'john.doe@example.com', 'Secret123', 'FORMATEUR', 'MAT001', 'FP', 120, 6],
                ],
                'rules' => function (array $row, Request $request): array {
                    return [
                        'nom' => 'required|string|max:100',
                        'prenom' => 'required|string|max:100',
                        'email' => 'required|email',
                        'password' => 'required|string|min:6',
                        'role' => 'required|in:' . implode(',', User::ROLES),
                        'matricule' => 'required|max:50',
                        'type_formateur' => 'nullable|required_if:role,FORMATEUR|in:FP,FV',
                        'mhs' => 'nullable|required_if:role,FORMATEUR|integer|min:0',
                        'nbr_semaine' => 'nullable|required_if:role,FORMATEUR|integer|min:0',
                    ];
                },
                'messages' => [
                    'email.required' => 'Email obligatoire.',
                    'email.email' => 'Email invalide. Utilisez le format nom@domaine.com.',
                    'matricule.required' => 'Matricule obligatoire.',
                    'matricule.max' => 'Matricule trop long (max 50 caracteres).',
                    'password.required' => 'Mot de passe obligatoire.',
                    'password.min' => 'Mot de passe trop court (minimum 6 caracteres).',
                    'role.in' => 'Role invalide. Valeurs autorisees: DOSI, DIRECTEUR_CMC, DIRECTEUR_EFP, CHEF_POLE, FORMATEUR.',
                    'type_formateur.required_if' => 'Type formateur obligatoire si le role est FORMATEUR.',
                    'id_pole.exists' => 'Pole introuvable.',
                ],
                'duplicate_check' => function (array $row, Request $request, array &$context): array {
                    $errors = [];
                    $email = strtolower((string) ($row['email'] ?? ''));

                    if ($email === '') {
                        return $errors;
                    }

                    if (User::where('email', $email)->exists()) {
                        $errors['email'] = 'Email already exists';
                        return $errors;
                    }

                    if (in_array($email, $context['seen']['user_emails'] ?? [], true)) {
                        $errors['email'] = 'Duplicate email in uploaded file';
                        return $errors;
                    }

                    $context['seen']['user_emails'][] = $email;
                    return $errors;
                },
                'create' => function (array $row, Request $request, array &$context): void {
                    $actor = $request->user();
                    $etablissementId = $this->actorEtablissementId($request);

                    if ($actor && $actor->role !== User::ROLE_DOSI && !$etablissementId) {
                        throw new \RuntimeException('Authenticated user does not have an establishment scope');
                    }

                    $user = User::create([
                        'nom' => (string) $row['nom'],
                        'prenom' => (string) $row['prenom'],
                        'email' => strtolower((string) $row['email']),
                        'password' => Hash::make((string) $row['password']),
                        'role' => (string) $row['role'],
                        'matricule' => (string) $row['matricule'],
                        'id_etablissement' => $etablissementId,
                    ]);

                    if ((string) $row['role'] === User::ROLE_FORMATEUR) {
                        $formateur = Formateur::create([
                            'id_user' => $user->id,
                            'id_etablissement' => $etablissementId,
                            'type_formateur' => $row['type_formateur'] ?? null,
                            'MHS' => $row['mhs'] ?? null,
                            'NBR_semaine' => $row['nbr_semaine'] ?? null,
                        ]);

                        $targetPole = null;

                        if ($actor && $actor->role === User::ROLE_CHEF_POLE) {
                            $chefPoleIds = $this->chefPoleIds($request) ?? [];
                            if (count($chefPoleIds) === 0) {
                                throw new \RuntimeException('Aucun pole associe a votre compte chef de pole.');
                            }

                            $targetPole = (int) $chefPoleIds[0];
                        }

                        if ($actor && $actor->role === User::ROLE_DIRECTEUR_CMC && $etablissementId) {
                            $targetPole = Pole::where('id_etablissement', $etablissementId)
                                ->orderBy('id')
                                ->value('id');
                        }

                        if ($actor && $actor->role === User::ROLE_DIRECTEUR_EFP) {
                            $targetPole = null;
                        }

                        if (!empty($targetPole)) {
                            DB::table('formateur_pole')->insert([
                                'id_formateur' => $formateur->id,
                                'id_pole' => (int) $targetPole,
                            ]);
                        }
                    }
                },
            ],
            'salles' => [
                'template_headings' => ['nom_salle', 'type_salle'],
                'template_sample_rows' => [
                    ['SD01', 'SALLE'],
                ],
                'rules' => fn (array $row, Request $request): array => [
                    'nom_salle' => 'required|string|max:100',
                    'type_salle' => 'required|in:SALLE,ATELIER',
                ],
                'duplicate_check' => function (array $row, Request $request, array &$context): array {
                    $etabId = $this->actorEtablissementId($request);
                    if (!$etabId) {
                        return ['id_etablissement' => 'Authenticated user does not have an establishment scope'];
                    }

                    $exists = Salle::where('id_etablissement', $etabId)
                        ->whereRaw('LOWER(nom_salle) = ?', [strtolower((string) $row['nom_salle'])])
                        ->exists();

                    return $exists ? ['nom_salle' => 'Salle name already exists in this establishment'] : [];
                },
                'create' => function (array $row, Request $request, array &$context): void {
                    $actor = $request->user();
                    $etabId = $this->actorEtablissementId($request);
                    if (!$etabId) {
                        throw new \RuntimeException('Authenticated user does not have an establishment scope');
                    }

                    $poleId = null;

                    if ($actor && $actor->role === User::ROLE_CHEF_POLE) {
                        $chefPoleIds = $this->chefPoleIds($request) ?? [];
                        $poleId = count($chefPoleIds) > 0 ? (int) $chefPoleIds[0] : null;
                    }

                    if ($actor && $actor->role === User::ROLE_DIRECTEUR_CMC && $etabId) {
                        $poleId = Pole::where('id_etablissement', $etabId)
                            ->orderBy('id')
                            ->value('id');
                    }

                    if ($actor && $actor->role === User::ROLE_DIRECTEUR_EFP) {
                        $poleId = null;
                    }

                    if ($poleId) {
                        $poleExists = Pole::where('id', $poleId)
                            ->where('id_etablissement', $etabId)
                            ->exists();
                        if (!$poleExists) {
                            throw new \RuntimeException('Selected pole does not belong to your establishment');
                        }

                        $chefPoleIds = $this->chefPoleIds($request);
                        if ($chefPoleIds !== null && !in_array($poleId, $chefPoleIds, true)) {
                            throw new \RuntimeException('Unauthorized: this pole does not belong to you');
                        }
                    }

                    Salle::create([
                        'id_etablissement' => $etabId,
                        'nom_salle' => (string) $row['nom_salle'],
                        'type_salle' => (string) $row['type_salle'],
                        'id_pole' => $poleId,
                    ]);
                },
            ],
            'filiers' => [
                'template_headings' => ['nom_filiere'],
                'template_sample_rows' => [
                    ['Developpement Digital'],
                ],
                'rules' => fn (array $row, Request $request): array => [
                    'nom_filiere' => 'required|string|max:150',
                ],
                'duplicate_check' => function (array $row, Request $request, array &$context): array {
                    $etabId = $this->actorEtablissementId($request);
                    if (!$etabId) {
                        return ['id_etablissement' => 'Authenticated user does not have an establishment scope'];
                    }

                    $exists = Filiere::where('id_etablissement', $etabId)
                        ->whereRaw('LOWER(nom_filiere) = ?', [strtolower((string) $row['nom_filiere'])])
                        ->exists();

                    return $exists ? ['nom_filiere' => 'Filiere already exists in this establishment'] : [];
                },
                'create' => function (array $row, Request $request, array &$context): void {
                    $actor = $request->user();
                    $etabId = $this->actorEtablissementId($request);
                    if (!$etabId) {
                        throw new \RuntimeException('Authenticated user does not have an establishment scope');
                    }

                    $poleId = null;

                    if ($actor && $actor->role === User::ROLE_CHEF_POLE) {
                        $chefPoleIds = $this->chefPoleIds($request) ?? [];
                        $poleId = count($chefPoleIds) > 0 ? (int) $chefPoleIds[0] : null;
                    }

                    if ($actor && $actor->role === User::ROLE_DIRECTEUR_CMC && $etabId) {
                        $poleId = Pole::where('id_etablissement', $etabId)
                            ->orderBy('id')
                            ->value('id');
                    }

                    if ($actor && $actor->role === User::ROLE_DIRECTEUR_EFP) {
                        $poleId = null;
                    }

                    if ($poleId) {
                        $poleExists = Pole::where('id', $poleId)
                            ->where('id_etablissement', $etabId)
                            ->exists();
                        if (!$poleExists) {
                            throw new \RuntimeException('Selected pole does not belong to your establishment');
                        }

                        $chefPoleIds = $this->chefPoleIds($request);
                        if ($chefPoleIds !== null && !in_array($poleId, $chefPoleIds, true)) {
                            throw new \RuntimeException('Unauthorized: this pole does not belong to you');
                        }
                    }

                    Filiere::create([
                        'id_etablissement' => $etabId,
                        'nom_filiere' => (string) $row['nom_filiere'],
                        'id_pole' => $poleId,
                    ]);
                },
            ],
            'groupes' => [
                'template_headings' => ['nom_groupe', 'annee', 'niveau', 'crenau', 'id_filiere'],
                'template_sample_rows' => [
                    ['DD101', '2025/2026', 'TS', 'CDJ', 1],
                ],
                'rules' => fn (array $row, Request $request): array => [
                    'nom_groupe' => 'required|string|max:100',
                    'annee' => 'required|string|max:20',
                    'niveau' => 'required|in:TS,T,Q,S,FQ,BP,PC',
                    'crenau' => 'required|in:CDJ,CDS',
                    'id_filiere' => 'required|integer|exists:filieres,id',
                ],
                'duplicate_check' => function (array $row, Request $request, array &$context): array {
                    $exists = Groupe::where('id_filiere', (int) $row['id_filiere'])
                        ->where('annee', (string) $row['annee'])
                        ->whereRaw('LOWER(nom_groupe) = ?', [strtolower((string) $row['nom_groupe'])])
                        ->exists();

                    return $exists ? ['nom_groupe' => 'Groupe already exists for this filiere and year'] : [];
                },
                'create' => function (array $row, Request $request, array &$context): void {
                    $filiereId = (int) $row['id_filiere'];
                    if (!$this->filiereInScope($request, $filiereId)) {
                        throw new \RuntimeException('Unauthorized: this filiere is outside your establishment scope');
                    }

                    Groupe::create([
                        'nom_groupe' => (string) $row['nom_groupe'],
                        'annee' => (string) $row['annee'],
                        'niveau' => (string) $row['niveau'],
                        'creneau' => (string) $row['crenau'],
                        'id_filiere' => $filiereId,
                    ]);
                },
            ],
            'modules' => [
                'template_headings' => ['nom_module', 'volume_horaire', 'id_filiere'],
                'template_sample_rows' => [
                    ['M101 - Initiation', 60, 1],
                ],
                'rules' => fn (array $row, Request $request): array => [
                    'nom_module' => 'required|string|max:150',
                    'volume_horaire' => 'required|integer|min:1',
                    'id_filiere' => 'required|integer|exists:filieres,id',
                ],
                'duplicate_check' => function (array $row, Request $request, array &$context): array {
                    $exists = Module::where('id_filiere', (int) $row['id_filiere'])
                        ->whereRaw('LOWER(nom_module) = ?', [strtolower((string) $row['nom_module'])])
                        ->exists();

                    return $exists ? ['nom_module' => 'Module already exists for this filiere'] : [];
                },
                'create' => function (array $row, Request $request, array &$context): void {
                    $filiereId = (int) $row['id_filiere'];
                    if (!$this->filiereInScope($request, $filiereId)) {
                        throw new \RuntimeException('Unauthorized: this filiere is outside your establishment scope');
                    }

                    Module::create([
                        'nom_module' => (string) $row['nom_module'],
                        'volume_horaire' => (int) $row['volume_horaire'],
                        'id_filiere' => $filiereId,
                    ]);
                },
            ],
            'poles' => [
                'template_headings' => ['nom_pole'],
                'template_sample_rows' => [
                    ['Pole Informatique'],
                ],
                'rules' => fn (array $row, Request $request): array => [
                    'nom_pole' => 'required|string|max:100',
                ],
                'duplicate_check' => function (array $row, Request $request, array &$context): array {
                    $etabId = $this->actorEtablissementId($request);
                    if (!$etabId) {
                        return ['id_etablissement' => 'Authenticated user does not have an establishment scope'];
                    }

                    $exists = Pole::where('id_etablissement', $etabId)
                        ->whereRaw('LOWER(nom_pole) = ?', [strtolower((string) $row['nom_pole'])])
                        ->exists();

                    return $exists ? ['nom_pole' => 'Pole already exists in this establishment'] : [];
                },
                'create' => function (array $row, Request $request, array &$context): void {
                    $etabId = $this->actorEtablissementId($request);
                    if (!$etabId) {
                        throw new \RuntimeException('Authenticated user does not have an establishment scope');
                    }

                    Pole::create([
                        'id_etablissement' => $etabId,
                        'nom_pole' => (string) $row['nom_pole'],
                        'id_chef' => null,
                    ]);
                },
            ],
        ];

        if (!array_key_exists($entityKey, $configs)) {
            throw new \InvalidArgumentException('Unsupported bulk entity: ' . $entityKey);
        }

        return $configs[$entityKey];
    }
}
