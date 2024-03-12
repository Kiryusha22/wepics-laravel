<?php

namespace App\Http\Controllers;

use App\Enums\ImageExtensionsEnum;
use App\Enums\SortTypesEnum;
use App\Exceptions\ApiException;
use App\Http\Requests\AlbumImagesRequest;
use App\Http\Requests\FilenameCheckRequest;
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
    public function indexingImages($album)
    {
        $path = "images$album->path";
        $files = Storage::files($path);
        $allowedExtensions = array_column(ImageExtensionsEnum::cases(), 'value');
        foreach ($files as $file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if (!in_array($extension, $allowedExtensions))
                continue;

            $imageModel = Image
                ::where('name', basename($file))
                ->where('album_id', $album->id)
                ->first();
            if (!$imageModel) {
                $sizes = getimagesize(Storage::path($file));

                $imageModel = Image::create([
                    'name' => basename($file),
                    'hash' => md5(Storage::get($file)),
                    'date' => Carbon::createFromTimestamp(Storage::lastModified($file)),
                    'size' => Storage::size($file),
                    'width'  => $sizes[0],
                    'height' => $sizes[1],
                    'album_id' => $album->id,
                ]);
            }
            // FIXME: надо удалять не найденные картинки из БД
        }
    }

    public function showAll(AlbumImagesRequest $request, $albumHash)
    {
        $album = Album::getByHash($albumHash);

        // FIXME: каждый раз при пролистывании страниц проверять картинки? Много производительности может кушать
        $this->indexingImages($album);

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
                ::where('album_id', $album->id)
                ->orderBy($sortType, $isReverse ? 'desc' : 'asc')
                ->paginate($perPage);
        } else {
            $imagesFromDB = Image
                ::where('album_id', $album->id)
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

    public function show($albumHash, $imageHash)
    {
        $image = Image::getByHash($albumHash, $imageHash);
        return response(ImageResource::make($image));
    }

    public function orig($albumHash, $imageHash)
    {
        $image = Image::getByHash($albumHash, $imageHash);
        $path = Storage::path('images'. $image->album->path . $image->name);
        return response()->download($path, $image->name);
    }

    public function thumb($albumHash, $imageHash, $orientation, $size)
    {
        $image = Image::getByHash($albumHash, $imageHash);

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

            if (!Storage::exists('thumbs'))
                Storage::makeDirectory('thumbs');

            $thumb->toWebp(80)->save(Storage::path($thumbPath));
        }
        return response()->download(Storage::path($thumbPath), basename($thumbPath));
    }

    public function rename(FilenameCheckRequest $request, $albumHash, $imageHash)
    {
        $image = Image::getByHash($albumHash, $imageHash);
        $imageExt = pathinfo($image->name, PATHINFO_EXTENSION);
        $newName = $request->name;

        $oldLocalPath = 'images'. $image->album->path . $image->name;
        $newPath = $image->album->path ."$newName.$imageExt";
        $newLocalPath = "images$newPath";
        if (Storage::exists($newPath))
            throw new ApiException(409, 'Album with this name already exist');

        Storage::move($oldLocalPath, $newLocalPath);
        $image->update([
            'name' => basename($newPath),
            'path' => "$newPath",
        ]);
        return response(null, 204);
    }

    public function delete($albumHash, $imageHash)
    {
        $image = Image::getByHash($albumHash, $imageHash);

        $imagePath = 'images'. $image->album->path . $image->name;
        Storage::delete($imagePath);

        $thumbPath = "thumbs/$image->hash-*";
        File::delete(File::glob(Storage::path($thumbPath)));

        $image->delete();

        return response(null, 204);
    }
}
