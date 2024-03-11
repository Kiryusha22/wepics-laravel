<?php

namespace App\Models;

use App\Exceptions\ApiException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Album extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'path',
        'hash',
        'parent_album_id',
    ];

    static public function getByHash($hash) {
        if ($hash != 'root') {
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
                    'hash' => 'root',
                ]);
        }
        return $album;
    }

    // Связь с моделью Image
    public function images() {
        return $this->hasMany(Image::class);
    }

    // Связь с моделью AccessRight
    public function accessRights() {
        return $this->hasMany(AccessRight::class);
    }

    // Связь с моделью Album для рекурсивного определения вложенных альбомов
    public function parentAlbum() {
        return $this->belongsTo(Album::class, 'parent_album_id');
    }

    public function childAlbums() {
        return $this->hasMany(Album::class, 'parent_album_id');
    }
}
