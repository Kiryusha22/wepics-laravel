<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\AccessController;

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

Route
::controller(UserController::class)
->prefix('users')
->group(function () {
    Route::middleware('token.auth')
        ->get  ('logout', 'logout');
    Route::post('login' , 'login' );
    Route::post('reg'   , 'reg'   );

    Route::middleware('token.auth:admin')->group(function () {
        Route::post  (''    , 'create' );
        Route::get   (''    , 'showAll');
        Route::get   ('{id}', 'show'   )->where('id', '[0-9]+');;
        Route::delete('{id}', 'destroy')->where('id', '[0-9]+');
    });
});

Route::middleware('token.auth:guest')->group(function () {
    Route
    ::controller(AlbumController::class)
    ->prefix('albums')
    ->group(function () {
        Route::get   ('',                      'root');
        Route::get   ('images/{page?}',        'rootImages');
        Route::get   ('{hash}',                'get');
        Route::get   ('{hash}/images/{page?}', 'getImages');
        Route::post  ('{hash}',                'create');
        Route::post  ('{hash}/images/{page?}', 'uploadImages');
        Route::delete('{hash}',                'destroy');

        Route
        ::controller(AccessController::class)
        ->prefix('access')
        ->group(function () {
            Route::get   ('', 'showAll');
            Route::post  ('', 'create');
            Route::delete('', 'destroy');
        });
    });

    Route
    ::controller(ImageController::class)
    ->prefix('images')
    ->group(function () {
        Route::get('{hash}',      'show' );
        Route::get('{hash}/orig', 'orig' );
        Route::get('{hash}/thumb/{orient}/{px}', 'thumb')
            ->where('orient', '[whWH]')
            ->where('px'    , '[0-9]+');
        Route::delete('{hash}', 'destroy');
    });
});
