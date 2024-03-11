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
    Route::post ('login' , 'login'   );
    Route::post ('reg'   , 'reg'     );

    Route::middleware('token.auth')->group(function () {
        Route::get  ('logout', 'logout'  );
        Route::patch('',       'editSelf');
    });

    Route::middleware('token.auth:admin')->group(function () {
        Route::post  (''    , 'create' );
        Route::get   (''    , 'showAll');
        Route::get   ('{id}', 'show'   )->where('id', '[0-9]+');
        Route::patch ('{id}', 'edit'   )->where('id', '[0-9]+');
        Route::delete('{id}', 'destroy')->where('id', '[0-9]+');
    });
});

Route::middleware('token.auth:guest')->group(function () {
    Route
    ::controller(AlbumController::class)
    ->prefix('albums')
    ->group(function () {

        Route
        ::controller(AccessController::class)
        ->prefix('access')
        ->middleware('token.auth:admin')
        ->group(function () {
            Route::get   ('', 'showAll');
            Route::post  ('', 'create' );
            Route::delete('', 'destroy');
        });

        Route::get   ('images',        'getImages');
        Route::get   ('{hash}/images', 'getImages');
        Route::get   ('{hash?}',       'get'   )->defaults('hash', null);
        Route::post  ('{hash?}',       'create')->defaults('hash', null);
        Route::post  ('images',        'uploadImages');
        Route::post  ('{hash}/images', 'uploadImages');
        Route::delete('{hash}',        'destroy');

        Route
        ::controller(AccessController::class)
        ->prefix('{hash}/access')
        ->middleware('token.auth:admin')
        ->group(function () {
            Route::get   ('', 'showAll');
            Route::post  ('', 'create' );
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

