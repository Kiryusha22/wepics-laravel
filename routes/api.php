<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\ImageController;

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

Route::group([
    'controller' => UserController::class,
    'prefix' => 'auth'
],function (){
    Route::middleware('token.auth')
        ->get  ('logout', 'logout');
    Route::post('login',  'login' );
    Route::post('reg'  ,  'reg'   );
});

Route::group([
    'controller' => ImageController::class,
    'prefix' => 'images'
],function (){
    Route::get('{hash}/orig',         'orig' );
    Route::get('{hash}/thumb/{size}', 'thumb');
    Route::get('{hash}',              'show' );
});

Route::middleware('token.auth')->group(function () {
    Route::group([
        'controller' => AlbumController::class,
        'prefix' => 'albums'
    ],function (){
        Route::get('',       'root');
        Route::get('{hash}', 'get' );
    });
});

