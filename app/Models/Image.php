<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Tags\HasTags;

class Image extends Model
{
    use HasFactory;
    use HasTags;

    public static function getTagClassName(): string
    {
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

    public function album() {
        return $this->belongsTo(Album::class);
        // FIXME: одна и та же картинка может находится в разных альбомах
    }
    public function reactions() {
        return $this->belongsToMany(Reaction::class, 'reaction_image');
    }
    public function tags() {
        return $this->belongsToMany(Tag::class, 'tag_image');
    }
}
