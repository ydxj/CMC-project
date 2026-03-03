<?php

namespace Database\Seeders;

use App\Models\Formateur;
use App\Models\Pole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'nom' => 'Admin',
                'prenom' => 'Directeur',
                'email' => 'directeur@cmc.ma',
                'matricule' => '20001',
                'password' => Hash::make('Directeur@123'),
                'role' => 'DIRECTEUR',
            ],
            [
                'nom' => 'Zerhouni Omar',
                'prenom' => 'Digital',
                'email' => 'chef.digital@cmc.ma',
                'matricule' => '20811',
                'password' => Hash::make('ChefPole@123'),
                'role' => 'CHEF_POLE',
            ],
            [
                'nom' => 'Mounir lahyani',
                'prenom' => 'Formateur',
                'email' => 'formateur@cmc.ma',
                'matricule' => '21249',
                'password' => Hash::make('mounir@123'),
                'role' => 'FORMATEUR',
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
            Pole::updateOrCreate(
                ['nom_pole' => 'Digital et Intelligence Artificielle'],
                ['id_chef' => $chefPole->id]
            );
        }

        if ($formateurUser) {
            $formateur = Formateur::updateOrCreate(
                ['id_user' => $formateurUser->id],
                [
                    'type_formateur' => 'FP',
                    'MHS' => 18,
                    'NBR_semaine' => 26,
                ]
            );

            $digitalPole = Pole::where('nom_pole', 'Digital et Intelligence Artificielle')->first();

            if ($digitalPole) {
                $formateur->poles()->syncWithoutDetaching([$digitalPole->id]);
            }
        }
    }
}
