<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EtablissementsController;
use App\Http\Controllers\EmploiDuTempsController;
use App\Http\Controllers\FiliersController;
use App\Http\Controllers\GroupesController;
use App\Http\Controllers\LocationsController;
use App\Http\Controllers\ModuleFormateurController;
use App\Http\Controllers\ModulesController;
use App\Http\Controllers\PolesController;
use App\Http\Controllers\SecteursController;
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

    // User listing/creation is needed for DOSI, directors and chef de pole flows.
    Route::middleware('role:DOSI,DIRECTEUR_CMC,DIRECTEUR_EFP,CHEF_POLE')->group(function () {
        Route::get('users', [UsersController::class, 'index']);
        Route::post('users', [UsersController::class, 'store']);
        Route::get('users/template', [UsersController::class, 'downloadTemplate']);
        Route::post('users/import', [UsersController::class, 'importExcel']);
    });

    // Update/delete user accounts are reserved to DOSI and directors.
    Route::middleware('role:DOSI,DIRECTEUR_CMC,DIRECTEUR_EFP')->group(function () {
        Route::put('users/{id}', [UsersController::class, 'update']);
        Route::patch('users/{id}', [UsersController::class, 'update']);
        Route::delete('users/{id}', [UsersController::class, 'destroy']);
    });

    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);

    Route::middleware('role:DOSI')->group(function () {
        Route::resource('etablissements', EtablissementsController::class);
        Route::get('locations/regions', [LocationsController::class, 'regions']);
        Route::get('locations/regions/{regionId}/cities', [LocationsController::class, 'citiesByRegion']);
    });

    Route::middleware('role:DOSI,DIRECTEUR_CMC,DIRECTEUR_EFP,CHEF_POLE')->group(function () {
        Route::get('poles/template', [PolesController::class, 'downloadTemplate']);
        Route::post('poles/import', [PolesController::class, 'importExcel']);
        Route::get('salles/template', [SallesController::class, 'downloadTemplate']);
        Route::post('salles/import', [SallesController::class, 'importExcel']);

        Route::resource('poles', PolesController::class);
        Route::resource('salles', SallesController::class);
        Route::get('groupes', [GroupesController::class, 'index']);
        Route::get('filiers', [FiliersController::class, 'index']);
        Route::get('filiers/{id}', [FiliersController::class, 'show'])->whereNumber('id');
        Route::get('modules', [ModulesController::class, 'index']);
        Route::get('modules/{id}', [ModulesController::class, 'show'])->whereNumber('id');

        Route::get('module-formateur', [ModuleFormateurController::class, 'index']);
        Route::get('formateur/{id_formateur}/module-hours', [ModuleFormateurController::class, 'getFormateurModuleHours']);
        Route::get('formateur/{id_formateur}/modules', [ModuleFormateurController::class, 'getFormateurModules']);
        Route::get('module/{id_module}/formateurs', [ModuleFormateurController::class, 'getModuleFormateurs']);
        Route::get('groupe/{id_groupe}/modules', [ModuleFormateurController::class, 'getGroupeModules']);

        Route::get('emploi-du-temps/weekly', [EmploiDuTempsController::class, 'getWeeklySchedule']);
        Route::get('emploi-du-temps/formateur/{id}/pdf', [EmploiDuTempsController::class, 'downloadFormateurWeeklyPdf']);
        Route::get('emploi-du-temps/groupe/{id}/pdf', [EmploiDuTempsController::class, 'downloadGroupeWeeklyPdf']);
        Route::get('emploi-du-temps/formateur/{id}', [EmploiDuTempsController::class, 'getByFormateur']);
        Route::get('emploi-du-temps/groupe/{id}', [EmploiDuTempsController::class, 'getByGroupe']);
        Route::get('emploi-du-temps/salle/{id}', [EmploiDuTempsController::class, 'getBySalle']);
        Route::resource('emploi-du-temps', EmploiDuTempsController::class);
    });

    Route::middleware('role:DOSI')->group(function () {
        Route::get('secteurs/template', [SecteursController::class, 'downloadTemplate']);
        Route::post('secteurs/import', [SecteursController::class, 'importExcel']);
        Route::get('secteurs', [SecteursController::class, 'index']);
        Route::get('secteurs/{id}', [SecteursController::class, 'show'])->whereNumber('id');
        Route::post('secteurs', [SecteursController::class, 'store']);
        Route::put('secteurs/{id}', [SecteursController::class, 'update']);
        Route::patch('secteurs/{id}', [SecteursController::class, 'update']);
        Route::delete('secteurs/{id}', [SecteursController::class, 'destroy']);

        Route::get('filiers/template', [FiliersController::class, 'downloadTemplate']);
        Route::post('filiers/import', [FiliersController::class, 'importExcel']);
        Route::post('filiers', [FiliersController::class, 'store']);
        Route::put('filiers/{id}', [FiliersController::class, 'update']);
        Route::patch('filiers/{id}', [FiliersController::class, 'update']);
        Route::delete('filiers/{id}', [FiliersController::class, 'destroy']);

        Route::get('modules/template', [ModulesController::class, 'downloadTemplate']);
        Route::post('modules/import', [ModulesController::class, 'importExcel']);
        Route::post('modules', [ModulesController::class, 'store']);
        Route::put('modules/{id}', [ModulesController::class, 'update']);
        Route::patch('modules/{id}', [ModulesController::class, 'update']);
        Route::delete('modules/{id}', [ModulesController::class, 'destroy']);

    });

    Route::middleware('role:DOSI,DIRECTEUR_CMC,DIRECTEUR_EFP,CHEF_POLE')->group(function () {
        Route::get('groupes/template', [GroupesController::class, 'downloadTemplate']);
        Route::post('groupes/import', [GroupesController::class, 'importExcel']);
        Route::post('groupes', [GroupesController::class, 'store']);
        Route::put('groupes/{groupe}', [GroupesController::class, 'update']);
        Route::patch('groupes/{groupe}', [GroupesController::class, 'update']);
        Route::delete('groupes/{groupe}', [GroupesController::class, 'destroy']);
    });

    // Module-Formateur affectations (CHEF_POLE and directors can manage)
    Route::middleware('role:CHEF_POLE,DIRECTEUR_EFP,DIRECTEUR_CMC')->group(function () {
        Route::get('module-formateur/affectation-form-data', [ModuleFormateurController::class, 'getAffectationFormData']);
        Route::get('module-formateur/filieres/{id_secteur}', [ModuleFormateurController::class, 'getFilieresBySecteur']);
        Route::get('module-formateur/modules/{id_filiere}', [ModuleFormateurController::class, 'getModulesByFiliere']);
        Route::get('module-formateur/groupes/{id_filiere}', [ModuleFormateurController::class, 'getGroupesByFiliere']);
        Route::post('module-formateur', [ModuleFormateurController::class, 'store']);
        Route::put('module-formateur/{module_formateur}', [ModuleFormateurController::class, 'update']);
        Route::patch('module-formateur/{module_formateur}', [ModuleFormateurController::class, 'update']);
        Route::delete('module-formateur/{module_formateur}', [ModuleFormateurController::class, 'destroy']);
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
        Route::get('me/emploi-du-temps/pdf', [EmploiDuTempsController::class, 'downloadMyWeeklyPdf']);
        Route::get('me/groupes', [ModuleFormateurController::class, 'getMyGroupes']);
        Route::get('me/modules', [ModuleFormateurController::class, 'getMyModules']);
        Route::get('me/module-hours', [ModuleFormateurController::class, 'getMyModuleHours']);
    });
});
