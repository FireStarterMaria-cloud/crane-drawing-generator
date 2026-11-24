<?php

use App\Http\Controllers\Api\DesignController;
use App\Http\Controllers\Api\MetaController;
use Illuminate\Support\Facades\Route;

Route::get('meta/limits', MetaController::class);

// preview must come before the resource routes
Route::post('designs/preview', [DesignController::class, 'preview']);
Route::get('designs/{design}/drawing.svg', [DesignController::class, 'drawing']);
Route::apiResource('designs', DesignController::class);