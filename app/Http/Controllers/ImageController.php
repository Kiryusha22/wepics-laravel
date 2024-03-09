<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Models\Album;
use App\Models\Picture;
use Illuminate\Http\Request;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function orig($hash){
        $image = Picture::where('hash', $hash)->first();
        if(!$image)
            throw new ApiException(404, 'Image not found');

        $album = Album::find($image->album_id);
        $path = Storage::disk("local")->path("images$album->path/$image->name");

        return response()->download($path, basename($path));
    }
    public function thumb($hash, $size) {
        $imageDB = Picture::where('hash', $hash)->first();
        if(!$imageDB)
            throw new ApiException(404, 'Image not found');

        $album = Album::find($imageDB->album_id);

        $imagePath = "images$album->path/$imageDB->name";
        $thumbPath = "thumbs$album->path/$imageDB->name";

        
        $thumb = $manager->make(Storage::get($imagePath));

        $thumb->resize(300, null, function ($constraint) {
            $constraint->aspectRatio();
        });

        $thumb->encode('webp', 80)->save(Storage::path($thumbPath));

        /*
        $imageDB = Picture::where('hash', $hash)->first();
        if(!$imageDB)
            throw new ApiException(404, 'Image not found');
        $album = Album::find($imageDB->album_id);
        $sourcePath = Storage::disk("local")->path("images$album->path/$imageDB->name");
        $thumbPath = "thumbs$album->path/$imageDB->name";

        list($width, $height) = getimagesize($sourcePath);
        $ratio = $width / $height;
        $desiredHeight = (int)($size / $ratio);

        $image = imagecreatefromstring(file_get_contents($sourcePath));
        $thumb = imagecreatetruecolor($size, $desiredHeight);

        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $size, $desiredHeight, $width, $height);


        //TODO Происходит какая то фигатень
        imagejpeg($thumb, $thumbPath);

        imagedestroy($image);
        imagedestroy($thumb);
        */
    }
    public function show(){

    }
}
