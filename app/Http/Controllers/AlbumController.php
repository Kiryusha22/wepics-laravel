<?php

namespace App\Http\Controllers;

use App\Enums\SortTypesEnum;
use App\Exceptions\ApiException;
use App\Http\Requests\AlbumImagesRequest;
use App\Http\Resources\ImageResource;
use App\Models\Album;
use App\Models\Image;
use Carbon\Carbon;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AlbumController extends Controller
{
    public function get($hash) {
        $parentAlbum = Album::getByHash($hash);

        $localPath = "images$parentAlbum->path";
        $folders = Storage::directories($localPath);

        $albumResponse = [];
        foreach ($folders as $folder) {
            $path = $parentAlbum->path.basename($folder)."/";
            $albumModel = Album::where('path', $path)->first();
            if(!$albumModel)
                $albumModel = Album::create([
                    'name' => basename($folder),
                    'path' => $path,
                    'hash' => Str::random(25),
                    'parent_album_id' => $parentAlbum->id
                ]);
            $albumResponse[] = $albumModel;
        }
        return response([
            'albums' => $albumResponse,
        ]);
    }

    public function create($hash) {
        $parentAlbum = $this::getAlbumFromDB($hash);

        $newFolderName = request()->album_name;
        if (strpbrk($newFolderName, "\\/?%*:|\"<>"))
            throw new ApiException(422, 'Not valid album name');

        $path = "images$parentAlbum->path$newFolderName";
        if (Storage::exists($path))
            throw new ApiException(409, 'Album already exist');

        Storage::createDirectory($path);
        $newAlbum = Album::create([
            'name' => basename($path),
            'path' => "$parentAlbum->path$newFolderName/",
            'hash' => Str::random(25),
            'parent_album_id' => $parentAlbum->id
        ]);
        return response($newAlbum);
    }
    public function destroy($hash) {
        $album = $this::getAlbumFromDB($hash);

        if ($album->path == '/')
            throw new ApiException(400, 'Root album cannot be deleted');

        Storage::deleteDirectory("images$album->path");
        $album->delete();

        return response(null, 204);
    }
}
