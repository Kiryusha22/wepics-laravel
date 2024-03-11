<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\AccessController;
use App\Http\Controllers\TagContoller;
use App\Http\Controllers\ReactionContoller;

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
->group(function ($route) {
    $route->post ('login' , 'login'   );
    $route->post ('reg'   , 'reg'     );
    $route
        ->middleware('token.auth')
        ->group(function ($route) {
        $route->get  ('logout', 'logout'  );
        $route->patch('',       'editSelf');
    });
    $route
        ->middleware('token.auth:admin')
        ->group(function ($route) {
        $route->post  (''    , 'create' );
        $route->get   (''    , 'showAll');
        $route
            ->prefix('{id}')
            ->group(function ($route) {
            $route->get   ('', 'show'   )->where('id', '[0-9]+');
            $route->patch ('', 'edit'   )->where('id', '[0-9]+');
            $route->delete('', 'destroy')->where('id', '[0-9]+');
        });
    });
});

Route
    ::middleware('token.auth:guest')
    ->controller(AlbumController::class)
    ->prefix('albums/{album_hash}')
    ->group(function ($route) {
    $route->get   ('', 'get'   );
    $route->post  ('', 'create');
    $route->patch ('', 'rename');
    $route->delete('', 'destroy');
    $route
        ->controller(ImageController::class)
        ->prefix('images')
        ->group(function ($route) {
        $route->get ('', 'showAll');
        $route->post('', 'upload');
        $route
            ->prefix('{image_hash}')
            ->group(function ($route) {
            $route->delete('',     'destroy');
            $route->patch ('',     'rename');
            $route->get   ('',     'show');
            $route->get   ('orig', 'orig');
            $route->get   ('thumb/{orient}{px}', 'thumb')
                ->where('orient', '[whWH]')
                ->where('px'    , '[0-9]+');
            $route
                ->controller(TagContoller::class)
                ->middleware('token.auth:admin')
                ->prefix('tags')
                ->group(function ($route) {
                $route->post  ('', 'set');
                $route->delete('', 'unset');
            });
            $route
                ->controller(ReactionContoller::class)
                ->middleware('token.auth:user')
                ->prefix('reactions')
                ->group(function ($route) {
                $route->post  ('', 'set');
                $route->delete('', 'unset');
            });
        });
    });
    $route
        ->controller(AccessController::class)
        ->middleware('token.auth:admin')
        ->prefix('access')
        ->group(function ($route) {
        $route->get   ('', 'showAll');
        $route->post  ('', 'create' );
        $route->delete('', 'destroy');
    });
});
