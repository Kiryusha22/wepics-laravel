<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Album extends Model
{
    protected $fillable = [
        'name',
        'path',
        'hash',
        'parent_album_id',
    ];

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
