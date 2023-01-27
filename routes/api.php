<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::prefix("auth")->group(function () {
    Route::post("login", [UserController::class, "login"]);
    Route::post("register", [UserController::class, "register"]);
    Route::post("logout", [UserController::class, "logout"]);
    Route::get("me", [UserController::class, "me"]);
    Route::post("refresh", [UserController::class, "refresh"]);
    Route::delete("destroy", [UserController::class, "destroy"]);
    Route::put("reset-password", [UserController::class, "resetPassword"]);
    Route::put("restore", [UserController::class, "restore"]);
});

Route::apiResource("users", UserController::class)->only("show");
