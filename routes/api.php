<?php

use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::controller(ScheduleController::class)->group(function () {
    Route::get('/slots', 'listSlots')->name('slots.list');
    Route::post('/book', 'book')->name('appointments.book');
    Route::delete('/appointments/{appointment}', 'cancel')->name('appointments.cancel');
});
