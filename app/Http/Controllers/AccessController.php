<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\Requests\AccessRightRequest;
use App\Models\AccessRight;
use App\Models\Album;
use Illuminate\Support\Facades\Cache;

class AccessController extends Controller
{
    public function showAll($hash)
    {
        $album = Album::getByHash($hash);

        $rights = $album->accessRights;
        if (count($rights) < 1)
            return response(['message' => 'Nobody has rights to this album']);

        $allowed   = [];
        $disallowed = [];
        $isGuestAllowed = null;
        foreach ($rights as $right) {
            if ($right->user_id === null) {
                $isGuestAllowed = (bool)$right->allowed;
                continue;
            }

            if ($right->allowed) $allowed[] = [
                'user_id' => $right->user_id,
                'nickname' => $right->user->nickname
            ];
            else $disallowed[] = [
                'user_id' => $right->user_id,
                'nickname' => $right->user->nickname
            ];
        }
        $response = [];
        if ($isGuestAllowed !== null)
                         $response['isGuestAllowed'] = $isGuestAllowed;
        if ($allowed)    $response['listAllowed'   ] = $allowed;
        if ($disallowed) $response['listDisallowed'] = $disallowed;

        return response($response);
    }
    public function create(AccessRightRequest $request, $hash)
    {
        $album = Album::getByHash($hash);

        $right = AccessRight
            ::where('album_id', $album->id)
            ->where('user_id' , $request->user_id)
            ->first();
        if ($right)
            throw new ApiException(404, 'Right already exist');

        Cache::flush(); // FIXME: костыль
        AccessRight::create([
            'album_id' => $album->id,
            'user_id'  => $request->user_id,
            'allowed'  => $request->allow,
        ]);

        return response(null, 204);
    }
    public function delete(AccessRightRequest $request, $hash)
    {
        $album = Album::getByHash($hash);

        $right = AccessRight
            ::where('album_id', $album->id)
            ->where('user_id', $request->user_id)
            ->first();

        if (!$right)
            throw new ApiException(404, 'Access right not found');

        Cache::flush(); // FIXME: костыль
        $right->delete();

        return response(null, '204');
    }
}
