<?php

namespace App\Http\Controllers;

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
        $files  = Storage::files      ('public/images');
        $folders = Storage::directories('public/images');

        $pictures = array_filter($files, function ($file) {
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            return in_array($extension, $allowedExtensions);
        });
        $parentAlbum = Album::where('path','/')->first();
        if (!$parentAlbum)
            $parentAlbum =  Album::create([
                'name'=>"",
                'path'=>"/",
                'hash'=>Hash::make(Str::random(255)),
            ]);
        $picturesResponse = [];
        foreach ($pictures as $picture){
            $pictureModel = Picture::where('name', basename($picture))->first();
            if(!$pictureModel)
                $pictureModel = Picture::create([
                    'name'=>basename($picture),
                    'weight'=>Storage::size($picture),
                    'date'=>Carbon::createFromTimestamp(Storage::lastModified($picture)),
                    'hash'=>md5(Storage::get($picture)),
                    'album_id'=>$parentAlbum->id
                ]);
            $picturesResponse[] = $pictureModel;
        }
        $albumResponse = [];
        foreach ($folders as $folder){
            $path = $parentAlbum->path.basename($folder);
            $albumModel = Album::where('path', $path)->first();
            if(!$albumModel)
                $albumModel = Album::create([
                    'name'=>basename($folder),
                    'path'=>$path,
                    'hash'=>Hash::make(Str::random(255)),
                    'parent_album_id'=>$parentAlbum->id
                ]);
            $albumResponse[] = $albumModel;
        }
        return response()->json([
            'content' => [
                'albums'   => $albumResponse,
                'pictures' => $picturesResponse,
            ]
        ]);

    }
    public function get(string $hash) {
        $files  = Storage::files      ('public/images');
        $folders = Storage::directories('public/images');

        $pictures = array_filter($files, function ($file) {
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            return in_array($extension, $allowedExtensions);
        });
        $parentAlbum = Album::where('hash',$hash)->first();
        if (!$parentAlbum)
            $parentAlbum =  Album::create([
                'name'=>"",
                'path'=>"/",
                'hash'=>Hash::make(Str::random(255)),
            ]);
        $picturesResponse = [];
        foreach ($pictures as $picture){
            $pictureModel = Picture::where('name', basename($picture))->first();
            if(!$pictureModel)
                $pictureModel = Picture::create([
                    'name'=>basename($picture),
                    'weight'=>Storage::size($picture),
                    'date'=>Carbon::createFromTimestamp(Storage::lastModified($picture)),
                    'hash'=>Hash::make(Storage::get($picture)),
                    'album_id'=>$parentAlbum->id
                ]);
            $picturesResponse[] = $pictureModel;
        }
        $albumResponse = [];
        foreach ($folders as $folder){
            $path = $parentAlbum->path.basename($folder);
            $albumModel = Album::where('path', $path)->first();
            if(!$albumModel)
                $albumModel = Album::create([
                    'name'=>basename($folder),
                    'path'=>$path,
                    'hash'=>Hash::make(Str::random(255)),
                    'parent_album_id'=>$parentAlbum->id
                ]);
            $albumResponse[] = $albumModel;
        }
        return response()->json([
            'content' => [
                'albums'   => $albumResponse,
                'pictures' => $picturesResponse,
            ]
        ]);

    }
}
