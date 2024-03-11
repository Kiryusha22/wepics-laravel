<?php

namespace App\Http\Controllers;

use App\Enums\SortTypesEnum;
use App\Exceptions\ApiException;
use App\Http\Requests\AlbumImagesRequest;
use App\Http\Resources\ImageResource;
use App\Models\Album;
use App\Models\Image;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function showAll(AlbumImagesRequest $request, $albumHash) {
        $parentAlbum = Album::getByHash($albumHash);

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

    public function show($albumHash, $imageHash) {
        $image = $this::getImageByHash($albumHash, $imageHash);
        return response(ImageResource::make($image));
    }

    public function orig($albumHash, $imageHash) {
        $image = $this::getImageByHash($albumHash, $imageHash);

        $album = Album::find($image->album_id);
        $path = Storage::disk("local")->path("images$image->album->path$image->name");

        return response()->download($path, basename($path));
    }

    public function thumb($albumHash, $imageHash, $orientation, $size) {
        $image = $this::getImageByHash($albumHash, $imageHash);

        $allowedSizes = [200, 300, 400, 600, 900];
        $allow = false;
        foreach ($allowedSizes as $allowedSize) {
            if ($size <= $allowedSize) {
                $size = $allowedSize;
                $allow = true;
                break;
            }
        }
        if (!$allow) $size = max($allowedSizes);

        $album = Album::find($image->album_id);

        $imagePath = "images$album->path$image->name";
        $thumbPath = "thumbs/$image->hash-$orientation$size.webp";

        if (!Storage::exists($thumbPath)) {
            $manager = new ImageManager(new Driver());
            $thumb = $manager->read(Storage::get($imagePath));

            if ($orientation == 'w')
                $thumb->scale(width: $size);
            else
                $thumb->scale(height: $size);

            if(!Storage::exists('thumbs'))
                Storage::makeDirectory('thumbs');

            $thumb->toWebp(80)->save(Storage::path($thumbPath));
        }

        $path = Storage::disk("local")->path($thumbPath);
        return response()->download($path, basename($path));
    }

    public function destroy($albumHash, $imageHash) {
        $image = $this::getImageByHash($albumHash, $imageHash);
        $album = Album::find($image->album_id);

        $imagePath = "images$album->path$image->name";
        Storage::delete($imagePath);

        $thumbPath = "thumbs/$image->hash-*";
        File::delete(File::glob(Storage::path($thumbPath)));

        $image->delete();

        return response(null, 204);
    }
}
