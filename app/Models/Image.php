<?php

namespace App\Models;

use App\Exceptions\ApiException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Tags\HasTags;

class Image extends Model
{
    use HasFactory;
    use HasTags;

    public static function getTagClassName(): string {
        return Tag::class;
    }

    protected $fillable = [
        'name',
        'hash',
        'date',
        'size',
        'width',
        'height',
        'album_id',
    ];

    static public function getByHash($albumHash, $imageHash)
    {
        $album = Album::getByHash($albumHash);
        $image = Image
            ::where('album_id', $album->id)
            ->where('hash', $imageHash)
            ->first();
        if(!$image)
            throw new ApiException(404, "Image not found");
        return $image;
    }

    public function album() {
        return $this->belongsTo(Album::class);
    }
    public function reactions() {
        return $this->belongsToMany(Reaction::class, 'reaction_images');
    }
    public function tags() {
        return $this->belongsToMany(Tag::class, 'tag_image');
        // TODO: Понять что это
//        return $this
//            ->morphToMany(self::getTagClassName(), 'tag_id', 'tag_image', null, 'tag_id')
//            ->orderBy('order_column');
    }
}
