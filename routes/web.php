<?php

use App\Http\Controllers\ScrabbleController;
use Illuminate\Support\Facades\Route;

Route::post('/scrabble/start', [ScrabbleController::class, 'startGame']);
Route::post('/scrabble/end-turn', [ScrabbleController::class, 'endTurn']);
Route::get('/scrabble/status', [ScrabbleController::class, 'getStatus']);
