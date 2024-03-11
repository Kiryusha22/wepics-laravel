<?php

namespace App\Http\Controllers;

use App\Enums\SortTypesEnum;
use App\Exceptions\ApiException;
use App\Http\Requests\AlbumImagesRequest;
use App\Http\Resources\ImageResource;
use App\Models\Album;
use App\Models\Image;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AlbumController extends Controller
{
    static public function getAlbumFromDB($hash) {
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
        $parentAlbum = $this::getAlbumFromDB($hash);

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
    public function getImages(AlbumImagesRequest $request, $hash = null) {
        $parentAlbum = $this::getAlbumFromDB($hash);

        $path = "images$parentAlbum->path";
        $files = Storage::files($path);

        $images = array_filter($files, function ($file) {
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webm'];
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            return in_array($extension, $allowedExtensions);
        });
        // FIXME: каждый раз при пролистывании страниц проверять картинки? Много производительности может кушать
        foreach ($images as $image) {
            $imageModel = Image
                ::where('name', basename($image))
                ->where('album_id', $parentAlbum->id)
                ->first();
            if(!$imageModel) {
                $sizes = getimagesize(Storage::path($image));

                $imageModel = Image::create([
                    'name'     => basename($image),
                    'hash'     => md5(Storage::get($image)),
                    'date'     => Carbon::createFromTimestamp(Storage::lastModified($image)),
                    'size'     => Storage::size($image),
                    'width'    => $sizes[0],
                    'height'   => $sizes[1],
                    'album_id' => $parentAlbum->id,
                ]);
            }
            // FIXME: надо удалять не найденные картинки из БД
        }
        $searchedTags = null;
        $tagsString = $request->input('tags');
        if ($tagsString)
            $searchedTags = explode(',', $tagsString);

        $allowedSorts = array_column(SortTypesEnum::cases(), 'value');
        $sortType = ($request->input('sort'));
        if (!$sortType)
            $sortType = $allowedSorts[0];

        $isReverse = $request->has('reverse');

        $perPage = intval($request->input('per_page'));
        if (!$perPage)
            $perPage = 30;

        if (!$searchedTags) {
            $imagesFromDB = Image
                ::where('album_id', $parentAlbum->id)
                ->orderBy($sortType, $isReverse ? 'desc' : 'asc')
                ->paginate($perPage);
        }
        else {
            $imagesFromDB = Image
                ::where('album_id', $parentAlbum->id)
                ->orderBy($sortType, $isReverse ? 'desc' : 'asc')
                ->withAllTags($searchedTags)
                ->paginate($perPage);
        }
        return response([
            'page'     => $imagesFromDB->currentPage(),
            'per_page' => $imagesFromDB->perPage(),
            'total'    => $imagesFromDB->total(),
            'pictures' => ImageResource::collection($imagesFromDB->items()),
        ]);
    }
}
