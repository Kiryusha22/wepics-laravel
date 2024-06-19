<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\Requests\FilenameCheckRequest;
use App\Models\Album;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AlbumController extends Controller
{
    public function get($hash)
    {
        $user = request()->user();

        $targetAlbum = Album::getByHash($hash);
        if(!$targetAlbum->hasAccessCached($user))
            throw new ApiException(403, 'Forbidden for you');

        $localPath = "images$targetAlbum->path";
        $folders = File::directories(Storage::path($localPath));

        $children = [];
        foreach ($folders as $folder) {
            $path = $targetAlbum->path . basename($folder) .'/';

            $albumModel = Album::where('path', $path)->first();
            if (!$albumModel)
                $albumModel = Album::create([
                    'name' => basename($path),
                    'path' => $path,
                    'hash' => Str::random(25),
                    'parent_album_id' => $targetAlbum->id
                ]);

            if ($albumModel->hasAccessCached($user))
                $children[] = $albumModel;
        }

        $parentsChain = [];
        $parentId = $targetAlbum->parent_album_id;
        while ($parentId) {
            $parent = Album::find($parentId);
            if (!$parent->hasAccessCached($user)) break;

            $parentId = $parent->parent_album_id;
            $parentsChain[] = $parent;
        }

        $response = ['name' => $targetAlbum->name];
        if ($children) {
            foreach ($children as $album)
                $childrenRefined[$album->name] = ['hash' => $album->hash];

            $response['children'] = $childrenRefined;
        }
        if ($parentsChain) {
            foreach (array_reverse($parentsChain) as $album) {
                if ($album->path === '/') $parentsChainRefined['/'] = ['hash' => $album->hash];
                else             $parentsChainRefined[$album->name] = ['hash' => $album->hash];
            }
            $response['parentsChain'] = $parentsChainRefined;
        }
        return response($response);
    }

    public function create(FilenameCheckRequest $request, $hash)
    {
        $parentAlbum = Album::getByHash($hash);
        $newFolderName = $request->name;

        $path = "images$parentAlbum->path$newFolderName";
        if (Storage::exists($path))
            throw new ApiException(409, 'Album with this name already exist');

        Storage::createDirectory($path);
        $newAlbum = Album::create([
            'name' => basename($path),
            'path' => "$parentAlbum->path$newFolderName/",
            'hash' => Str::random(25),
            'parent_album_id' => $parentAlbum->id
        ]);
        return response($newAlbum);
    }

    public function rename(FilenameCheckRequest $request, $hash)
    {
        $album = Album::getByHash($hash);
        $newFolderName = $request->name;

        $oldLocalPath = "images$album->path";
        $newPath = dirname($album->path) .'/'. $newFolderName .'/';
        $newLocalPath = "images$newPath";
        if (Storage::exists($newPath))
            throw new ApiException(409, 'Album with this name already exist');

        Storage::move($oldLocalPath, $newLocalPath);
        $album->update([
            'name' => basename($newPath),
            'path' => "$newPath",
        ]);
        return response(null, 204);
    }

    public function delete($hash)
    {
        $album = Album::getByHash($hash);
        $path = Storage::path("images$album->path");

        if ($album->path == '/')
            File::cleanDirectory($path);
        else
            File::deleteDirectory($path);

        $album->delete();
        return response(null, 204);
    }
}
