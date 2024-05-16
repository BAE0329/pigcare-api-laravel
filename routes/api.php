<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register',['uses'=>'\App\Http\Controllers\AuthController@register']);
Route::post('/login',['uses'=>'\App\Http\Controllers\AuthController@login']);
Route::post('/forgotPassword', ['uses'=> '\App\Http\Controllers\AuthController@forgotPassword']);
Route::post('/resetPassword/{id}/{isSuccess}', ['uses'=> '\App\Http\Controllers\AuthController@resetPassword']);
Route::get('/viewFeedsInfo', ['uses'=> '\App\Http\Controllers\AuthController@viewFeedsInfo']);
Route::get('/viewPigsInfo', ['uses'=> '\App\Http\Controllers\AuthController@viewPigsInfo']);

Route::middleware(['auth:sanctum'])->group(function (){
    Route::post('/logout',['uses'=>'\App\Http\Controllers\AuthController@logout']);
    Route::post('/FarmSetup',['uses'=>'\App\Http\Controllers\AuthController@FarmSetup']);
    Route::post('/create_pigs',['uses'=>'\App\Http\Controllers\AuthController@create_pigs']);
    Route::get('/viewPigs',['uses'=>'\App\Http\Controllers\AuthController@viewPigs']);
    Route::post('/changepassword',['uses'=>'\App\Http\Controllers\AuthController@changepassword']);
    Route::get('/getPigDetails/{pigName}',['uses'=>'\App\Http\Controllers\AuthController@getPigDetails']);
    Route::put('/updatePigDetails/{pigName}',['uses'=>'\App\Http\Controllers\AuthController@updatePigDetails']);  
    Route::get('/ViewProfile',['uses'=>'\App\Http\Controllers\AuthController@ViewProfile']);
    Route::get('/HeatPigs',['uses'=>'\App\Http\Controllers\AuthController@HeatPigs']);
    Route::put('/updatePigStatus/{pigName}',['uses'=>'\App\Http\Controllers\AuthController@updatePigStatus']);
    Route::get('/GestatingPigs',['uses'=>'\App\Http\Controllers\AuthController@GestatingPigs']);
    Route::put('/updatePigStatus1/{pigName}',['uses'=>'\App\Http\Controllers\AuthController@updatePigStatus1']);
    Route::put('/StoreFeeds',['uses'=>'\App\Http\Controllers\AuthController@StoreFeeds']);
    Route::get('/ViewFeeds',['uses'=>'\App\Http\Controllers\AuthController@ViewFeeds']);
    Route::put('/DoneFeeding',['uses'=>'\App\Http\Controllers\AuthController@DoneFeeding']);
    Route::post('/Transaction',['uses'=>'\App\Http\Controllers\AuthController@Transaction']);
    Route::post('/Events',['uses'=>'\App\Http\Controllers\AuthController@Events']);
    Route::get('/Reports',['uses'=>'\App\Http\Controllers\AuthController@Reports']);
    Route::get('/ViewSetup',['uses'=>'\App\Http\Controllers\AuthController@ViewSetup']);
    Route::delete('/deletePig/{pigName}',['uses'=>'\App\Http\Controllers\AuthController@deletePig']);
    Route::get('/Revenue',['uses'=>'\App\Http\Controllers\AuthController@Revenue']);
    Route::post('/countPigsByBreed',['uses'=>'\App\Http\Controllers\AuthController@countPigsByBreed']);
    Route::post('/PigsReport',['uses'=>'\App\Http\Controllers\AuthController@PigsReport']);
    Route::get('/viewPigsReport',['uses'=>'\App\Http\Controllers\AuthController@viewPigsReport']);
    Route::put ('/updateProfile',['uses'=>'\App\Http\Controllers\AuthController@updateProfile']);
    Route::get ('/feedReport',['uses'=>'\App\Http\Controllers\AuthController@feedReport']);
});



