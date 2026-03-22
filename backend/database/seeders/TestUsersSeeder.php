<?php

namespace Database\Seeders;

use App\Models\Etablissement;
use App\Models\Formateur;
use App\Models\Pole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $etablissement = Etablissement::updateOrCreate(
            ['nom_etablissement' => 'CMC Principal'],
            [
                'type_etablissement' => 'CMC',
                'region' => 'Casablanca-Settat',
                'id_directeur' => null,
            ]
        );

        $users = [
            [
                'nom' => 'Admin',
                'prenom' => 'Directeur',
                'email' => 'directeur@cmc.ma',
                'matricule' => '20001',
                'password' => Hash::make('Directeur@123'),
                'role' => User::ROLE_DIRECTEUR_CMC,
                'id_etablissement' => $etablissement->id,
            ],
            [
                'nom' => 'Zerhouni Omar',
                'prenom' => 'Digital',
                'email' => 'chef.digital@cmc.ma',
                'matricule' => '20811',
                'password' => Hash::make('ChefPole@123'),
                'role' => User::ROLE_CHEF_POLE,
                'id_etablissement' => $etablissement->id,
            ],
            [
                'nom' => 'Mounir lahyani',
                'prenom' => 'Formateur',
                'email' => 'formateur@cmc.ma',
                'matricule' => '21249',
                'password' => Hash::make('mounir@123'),
                'role' => User::ROLE_FORMATEUR,
                'id_etablissement' => $etablissement->id,
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        $chefPole = User::where('email', 'chef.digital@cmc.ma')->first();
        $formateurUser = User::where('email', 'formateur@cmc.ma')->first();

        if ($chefPole) {
            $pole = Pole::updateOrCreate(
                ['nom_pole' => 'Digital et Intelligence Artificielle'],
                [
                    'id_chef' => $chefPole->id,
                    'id_etablissement' => $etablissement->id,
                ]
            );

            $etablissement->update(['id_directeur' => User::where('email', 'directeur@cmc.ma')->value('id')]);
        }

        if ($formateurUser) {
            $formateur = Formateur::updateOrCreate(
                ['id_user' => $formateurUser->id],
                [
                    'id_etablissement' => $etablissement->id,
                    'type_formateur' => 'FP',
                    'MHS' => 18,
                    'NBR_semaine' => 26,
                ]
            );

            $digitalPole = Pole::where('nom_pole', 'Digital et Intelligence Artificielle')->first();

            if ($digitalPole) {
                    DB::table('formateur_pole')->updateOrInsert(
                        [
                            'id_formateur' => $formateur->id,
                            'id_pole' => $digitalPole->id,
                        ],
                        []
                    );
            }
        }
    }
}
