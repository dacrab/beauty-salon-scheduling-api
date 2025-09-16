<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScheduleController;

Route::get('/slots', [ScheduleController::class, 'listSlots']);
Route::post('/book', [ScheduleController::class, 'book']);
Route::delete('/appointments/{appointment}', [ScheduleController::class, 'cancel']);


