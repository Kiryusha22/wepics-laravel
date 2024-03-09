<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Models\Album;
use App\Models\Picture;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AlbumController extends Controller
{
    public function root() {
        return $this->get();
    }
    public function rootImages(int $page = 1) {
        return $this->getImages(page: $page);
    }
    public function getAlbumFromDB($hash) {
        if ($hash) {
            $album = Album::where('hash', $hash)->first();
            if (!$album)
                throw new ApiException(404, "Album with hash \"$hash\" not found");
        }
        else {
            $album = Album::where('path', '/')->first();
            if (!$album)
                $album =  Album::create([
                    'name' => '',
                    'path' => '/',
                    'hash' => Str::random(25),
                ]);
        }
        return $album;
    }
    public function get($hash = null) {
        $parentAlbum = $this->getAlbumFromDB($hash);

        $path = "images$parentAlbum->path";
        $folders = Storage::directories($path);

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
    public function getImages($hash = null, int $page = 1) {
        $parentAlbum = $this->getAlbumFromDB($hash);

        $path = "images$parentAlbum->path";
        $files = Storage::files($path);

        $searchedTags = null;
        $tagsString = request()->input('tags');
        if ($tagsString)
            $searchedTags = explode(',', $tagsString);

        $allowedSorts = ['name', 'date', 'size'];
        $sortType = (request()->input('sort'));
        if (!in_array($sortType, $allowedSorts))
            $perPage = $allowedSorts[0];

        $perPage = intval(request()->input('per_page'));
        if (!$perPage)
            $perPage = 30;

        // TODO: Сделать фильтрацию по тегам, сортировку и страницизацию через данные в базе

        $pictures = array_filter($files, function ($file) {
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webm'];
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            return in_array($extension, $allowedExtensions);
        });

        $picturesResponse = [];
        foreach ($pictures as $picture){
            $pictureModel = Picture::where('name', basename($picture))->first();
            if(!$pictureModel)
                $pictureModel = Picture::create([
                    'name' => basename($picture),
                    'size' => Storage::size($picture),
                    'date' => Carbon::createFromTimestamp(Storage::lastModified($picture)),
                    'hash' => md5(Storage::get($picture)),
                    'album_id' => $parentAlbum->id
                ]);
            $picturesResponse[] = $pictureModel;
        }
        return response([
            'pictures' => $picturesResponse,
        ]);
    }
}
