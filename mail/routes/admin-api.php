<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\AdminStatsController;

Route::get('/stats', [AdminStatsController::class, 'index']);
