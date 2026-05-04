<?php

use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\ColorController;
use Illuminate\Support\Facades\Route;

// Registers standard RESTful endpoints for products
Route::apiResource('/categories', CategoryController::class);
Route::apiResource('/colors', ColorController::class);