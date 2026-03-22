<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmploiDuTempsController;
use App\Http\Controllers\FiliersController;
use App\Http\Controllers\GroupesController;
use App\Http\Controllers\ModuleFormateurController;
use App\Http\Controllers\ModulesController;
use App\Http\Controllers\PolesController;
use App\Http\Controllers\SallesController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);

// i will add here the public rout for the student to see their schedule

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);

    Route::middleware('role:DOSI,DIRECTEUR_CMC,DIRECTEUR_EFP')->group(function () {
        Route::resource('users', UsersController::class);
    });

    Route::middleware('role:DOSI,DIRECTEUR_CMC,DIRECTEUR_EFP,CHEF_POLE')->group(function () {
        Route::resource('poles', PolesController::class);
        Route::resource('salles', SallesController::class);
        Route::resource('filiers', FiliersController::class);
        Route::resource('groupes', GroupesController::class);
        Route::resource('modules', ModulesController::class);

        Route::resource('module-formateur', ModuleFormateurController::class);
        Route::get('formateur/{id_formateur}/module-hours', [ModuleFormateurController::class, 'getFormateurModuleHours']);
        Route::get('formateur/{id_formateur}/modules', [ModuleFormateurController::class, 'getFormateurModules']);
        Route::get('module/{id_module}/formateurs', [ModuleFormateurController::class, 'getModuleFormateurs']);
        Route::get('groupe/{id_groupe}/modules', [ModuleFormateurController::class, 'getGroupeModules']);

        Route::get('emploi-du-temps/weekly', [EmploiDuTempsController::class, 'getWeeklySchedule']);
        Route::get('emploi-du-temps/formateur/{id}', [EmploiDuTempsController::class, 'getByFormateur']);
        Route::get('emploi-du-temps/groupe/{id}', [EmploiDuTempsController::class, 'getByGroupe']);
        Route::get('emploi-du-temps/salle/{id}', [EmploiDuTempsController::class, 'getBySalle']);
        Route::resource('emploi-du-temps', EmploiDuTempsController::class);
    });

    Route::middleware('role:CHEF_POLE')->group(function () {
        Route::get('my-poles-formateurs', [PolesController::class, 'getMyPoleFormateurs']);

        // Pole-scoped queries
        Route::get('poles/{id}/formateurs', [PolesController::class, 'getFormateurs']);
        Route::post('poles/{id}/formateurs', [PolesController::class, 'addFormateur']);
        Route::delete('poles/{id}/formateurs', [PolesController::class, 'removeFormateur']);
        Route::get('poles/{id}/salles', [PolesController::class, 'getSalles']);
        Route::get('poles/{id}/filieres', [PolesController::class, 'getFilieres']);
    });

    Route::middleware('role:FORMATEUR')->group(function () {
        Route::get('me/emploi-du-temps', [EmploiDuTempsController::class, 'getMyWeeklySchedule']);
        Route::get('me/groupes', [ModuleFormateurController::class, 'getMyGroupes']);
        Route::get('me/modules', [ModuleFormateurController::class, 'getMyModules']);
        Route::get('me/module-hours', [ModuleFormateurController::class, 'getMyModuleHours']);
    });
});
