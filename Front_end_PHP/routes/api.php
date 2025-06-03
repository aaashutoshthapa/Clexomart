<?php

use App\Http\Controllers\RfidController;
use Illuminate\Support\Facades\Route;

Route::post('/rfid', [RfidController::class, 'store']);
Route::get('/rfid/recent', [RfidController::class, 'getRecent']);
Route::get('/rfid/{uid}/product', [RfidController::class, 'getProductByRfid']);
