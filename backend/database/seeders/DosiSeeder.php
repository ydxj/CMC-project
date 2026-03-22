<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DosiSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'dosi@cmc.ma'],
            [
                'nom' => 'System',
                'prenom' => 'DOSI',
                'matricule' => '00001',
                'password' => Hash::make('Dosi@123'),
                'role' => User::ROLE_DOSI,
                'id_etablissement' => null,
            ]
        );
    }
}