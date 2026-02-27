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

Route::resource('users', UsersController::class);
Route::resource('poles', PolesController::class);
Route::resource('salles', SallesController::class);
Route::resource('filiers', FiliersController::class);
Route::resource('groupes', GroupesController::class);
Route::resource('modules', ModulesController::class);
Route::resource('emploi-du-temps', EmploiDuTempsController::class);

Route::post('/module-formateur', [ModuleFormateurController::class, 'store']);
