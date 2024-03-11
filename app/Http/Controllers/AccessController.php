<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\Requests\AccessRightRequest;
use App\Models\AccessRight;

class AccessController extends Controller
{
    public function showAll($hash = null) {
        $album = AlbumController::getAlbumFromDB($hash);

        $rights = $album->accessRights;
        if(count($rights) < 1)
            throw new ApiException(200, 'Nobody has rights to this album');

        return response($rights);
    }
    public function create(AccessRightRequest $request, $hash = null) {
        $album = AlbumController::getAlbumFromDB($hash);

        $right = AccessRight::create([
            'album_id' => $album->id,
            'user_id'  => $request->user_id,
            'allowed'  => $request->allow,
        ]);

        return response(null, 204);
    }
    public function destroy(AccessRightRequest $request, $hash = null) {
        $album = AlbumController::getAlbumFromDB($hash);

        $right = AccessRight
            ::where('album_id', $album->id)
            ->where('user_id', $request->user_id)
            ->first();

        if (!$right)
            throw new ApiException(404, 'Access right not found');

        $right->delete();

        return response(null, '204');
    }
}
