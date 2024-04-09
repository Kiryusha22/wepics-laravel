<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\AccessController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\ReactionController;

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
->group(function ($users) {
    $users->post ('login' , 'login');
    $users->post ('reg'   , 'reg'  );
    $users->middleware('token.auth')->group(function ($authorized) {
        $authorized->get  ('logout', 'logout'  );
        $authorized->patch('',       'editSelf');
    });
    $users->middleware('token.auth:admin')->group(function ($usersManage) {
        $usersManage->post('', 'create' );
        $usersManage->get ('', 'showAll');
        $usersManage->prefix('{id}')->group(function ($userManage) {
            $userManage->get   ('', 'show'  )->where('id', '[0-9]+');
            $userManage->patch ('', 'edit'  )->where('id', '[0-9]+');
            $userManage->delete('', 'delete')->where('id', '[0-9]+');
        });
    });
});
Route
::middleware('token.auth:guest')
->controller(AlbumController::class)
->prefix('albums/{album_hash}')
->group(function ($album) {
    $album->get('', 'get');
    $album->middleware('token.auth:admin')->group(function ($albumManage) {
        $albumManage->post  ('', 'create');
        $albumManage->patch ('', 'rename');
        $albumManage->delete('', 'delete');
    });
    $album
    ->controller(AccessController::class)
    ->middleware('token.auth:admin')
    ->prefix('access')
    ->group(function ($albumRights) {
        $albumRights->get   ('', 'showAll');
        $albumRights->post  ('', 'create' );
        $albumRights->delete('', 'delete');
    });
    $album
    ->controller(ImageController::class)
    ->prefix('images')
    ->group(function ($albumImages) {
        $albumImages->get('', 'showAll');
        $albumImages->middleware('token.auth:admin')->post('', 'upload');
        $albumImages->prefix('{image_hash}')->group(function ($image) {
            $image->middleware('token.auth:admin')->delete('', 'delete');
            $image->middleware('token.auth:admin')->patch ('', 'rename');
            $image->get('',     'show');
            $image->get('orig', 'orig');
            $image->get('thumb/{orient}{px}', 'thumb')
                ->where('orient', '[whWH]')
                ->where('px'    , '[0-9]+')
                ->withoutMiddleware("throttle:api")
                ->name('get.image.thumb');
            $image
            ->controller(TagController::class)
            ->middleware('token.auth:admin')
            ->prefix('tags')
            ->group(function ($imageTags) {
                $imageTags->post  ('', 'set');
                $imageTags->delete('', 'unset');
            });
            $image
            ->controller(ReactionController::class)
            ->middleware('token.auth:user')
            ->prefix('reactions')
            ->group(function ($imageReactions) {
                $imageReactions->post  ('', 'set');
                $imageReactions->delete('', 'unset');
            });
        });
    });
});
Route
::middleware('token.auth:guest')
->controller(TagController::class)
->prefix('tags')
->group(function ($tags) {
    $tags->get('', 'showAllOrSearch');
    $tags->middleware('token.auth:admin')->group(function ($tagsManage) {
        $tagsManage->post  ('', 'create');
        $tagsManage->patch ('', 'rename');
        $tagsManage->delete('', 'delete');
    });
});
Route
::middleware('token.auth:guest')
->controller(ReactionController::class)
->prefix('reactions')
->group(function ($reactions) {
    $reactions->get('', 'showAll');
    $reactions->middleware('token.auth:admin')->group(function ($reactionsManage) {
        $reactionsManage->post  ('', 'add');
        $reactionsManage->delete('', 'remove');
    });
});
