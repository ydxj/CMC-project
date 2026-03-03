<?php

use App\Http\Controllers\AuthController;
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

    Route::resource('users', UsersController::class);
    Route::resource('poles', PolesController::class);
    Route::resource('salles', SallesController::class);
    Route::resource('filiers', FiliersController::class);
    Route::resource('groupes', GroupesController::class);
    Route::resource('modules', ModulesController::class);
    Route::get('emploi-du-temps/weekly', [EmploiDuTempsController::class, 'getWeeklySchedule']);
    Route::resource('emploi-du-temps', EmploiDuTempsController::class);

    Route::resource('module-formateur', ModuleFormateurController::class);
    Route::get('formateur/{id_formateur}/modules', [ModuleFormateurController::class, 'getFormateurModules']);
    Route::get('module/{id_module}/formateurs', [ModuleFormateurController::class, 'getModuleFormateurs']);
    Route::get('groupe/{id_groupe}/modules', [ModuleFormateurController::class, 'getGroupeModules']);
});
