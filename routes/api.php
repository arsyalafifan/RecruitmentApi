<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\mtEducationController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\VacancyController;
use App\Http\Controllers\API\CandidateEventController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/profile', [AuthController::class, 'profile']);
Route::post('/logout', [AuthController::class, 'logout']);

Route::get('/profile', [ProfileController::class, 'profile']);
Route::put('/profile', [ProfileController::class, 'update']);
Route::post('/profile/upload-photo', [ProfileController::class, 'uploadPhoto']);

Route::get('/vacancy', [VacancyController::class, 'index']);
Route::post('/vacancy/apply', [VacancyController::class, 'apply']);
Route::get('/vacancy/check-applied', [VacancyController::class, 'checkApplied']);

Route::get('/education', [mtEducationController::class, 'list']);

Route::get('/candidate/events', [CandidateEventController::class, 'getEvents']);
Route::post('/candidate/confirm-attendance', [CandidateEventController::class, 'confirmAttendance']);
Route::post('/candidate/generate-qr', [CandidateEventController::class, 'generateQR']);

