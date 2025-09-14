<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PokemonController;

Route::get('/pokemon', [PokemonController::class, 'index']); //lista
Route::get('/pokemon/{name}', [PokemonController::class, 'show']); //dettaglio
