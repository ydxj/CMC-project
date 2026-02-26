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

Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/users', [UsersController::class, 'index']);
Route::post('/users', [UsersController::class, 'store']);

Route::get('/poles', [PolesController::class, 'index']);
Route::post('/poles', [PolesController::class, 'store']);

Route::get('/salles', [SallesController::class, 'index']);
Route::post('/salles', [SallesController::class, 'store']);

Route::get('/filiers', [FiliersController::class, 'index']);
Route::post('/filiers', [FiliersController::class, 'store']);

Route::get('/groupes', [GroupesController::class, 'index']);
Route::post('/groupes', [GroupesController::class, 'store']);

Route::get('/modules', [ModulesController::class, 'index']);
Route::post('/modules', [ModulesController::class, 'store']);

Route::post('/module-formateur', [ModuleFormateurController::class, 'store']);

Route::get('/emploi-du-temps', [EmploiDuTempsController::class, 'index']);
Route::post('/emploi-du-temps', [EmploiDuTempsController::class, 'store']);
