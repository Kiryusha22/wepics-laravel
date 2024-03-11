<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Models\Album;
use App\Models\Image;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    static public function getImageFromDB($hash) {
        $image = Image::where('hash', $hash)->first();
        if(!$image)
            throw new ApiException(404, "Image with hash \"$hash\" not found");
        return $image;
    }
    public function show($hash) {
        $image = $this::getImageFromDB($hash);
        return response($image);
    }
    public function orig($hash) {
        $image = $this::getImageFromDB($hash);

        $album = Album::find($image->album_id);
        $path = Storage::disk("local")->path("images$album->path$image->name");

        return response()->download($path, basename($path));
    }
    public function thumb($hash, $orientation, $size) {
        $image = $this::getImageFromDB($hash);

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
    public function destroy($hash) {
        $image = $this::getImageFromDB($hash);
        $album = Album::find($image->album_id);

        //$imagePath = "images$album->path$image->name";
        //Storage::delete($imagePath);

        $thumbPath = "thumbs/$image->hash-*";
        Storage::delete(File::glob(Storage::path($thumbPath)));  // FIXME: чёт не удаляется

        //$image->delete();

        return response(null, 204);
    }
}
