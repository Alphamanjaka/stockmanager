<?php

use App\Http\Controllers\API\CategoryController;
use Illuminate\Support\Facades\Route;

// Registers standard RESTful endpoints for products
Route::apiResource('/categories', CategoryController::class);
