<?php

namespace App\Models;

use App\Exceptions\ApiException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Album extends Model
{
    use HasFactory;

    // Поля для заполнения
    protected $fillable = [
        'name',
        'path',
        'hash',
        'parent_album_id',
    ];

    // Функции
    /**
     * Получение альбома по его уникальному хешу
     */
    static public function getByHash($hash): Album {
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
    /**
     * Проверка пользователя на доступ к альбому
     */
    public function hasAccess(User $user = null): bool {
        // Проверка админ ли пользователь
        //if ($user?->is_admin)
        //    return true;

        // Проверка есть ли доступ гостю
        $right = AccessRight
            ::where('user_id' , null)
            ->where('album_id', $this->id)
            ->first();
        if ($right?->allowed)
            return true;

        if ($user) {
            // Проверка есть ли доступ пользователю
            $right = AccessRight
                ::where('user_id' , $user->id)
                ->where('album_id', $this->id)
                ->first();
            if ($right?->allowed)
                return true;
        }
        // TODO: Сделать восходящую (к род. альбомам) проверку доступа, если не было запретов

        return false;
    }

    public function hasAccessCached($user = null) {
        if ($user?->is_admin) return true;

        $cacheKey = "access:to=$this->hash;for=$user?->id";
        $allow = Cache::get($cacheKey);

        if ($allow === null) {
            $allow = $this->hasAccess($user);
            //$allow = Album::hasAccessFastById($this->id, $user?->id); // TODO: перейти на крутой метод
            Cache::put($cacheKey, $allow, 86400);
        }
        return $allow;
    }

     public static function hasAccessCachedByHash($albumHash, $user = null) {
        if ($user?->is_admin) return true;

        $cacheKey = "access:to=$albumHash;for=$user?->id";
        $allow = Cache::get($cacheKey);

        if ($allow === null) {
            $album = Album::getByHash($albumHash);
            $allow = $album->hasAccess($user);
            //$allow = Album::hasAccessFastByHash($albumHash, $user?->id); // TODO: перейти на крутой метод
            Cache::put($cacheKey, $allow, 86400);
        }
        return $allow;
    }

    public static function hasAccessFastByHash(string $hash, int $userId = null) {
        $res = DB
            ::table('access_rights')
            ->rightJoin('albums', 'access_rights.album_id', '=', 'albums.id')
            ->where('user_id', $userId)
            ->where('hash', $hash)
            ->select('allowed', 'parent_album_id', 'path')
            ->first();

        if ($res !== null) {
            if ($res->allowed !== null) return $res->allowed;
            if ($res->path === '/')     return false;
        }
        return Album::hasAccessFastById($res->parent_album_id, $userId);
    }

    public static function hasAccessFastById(int $albumId, int $userId = null): bool {
        $res = DB
            ::table('access_rights')
            ->rightJoin('albums', 'access_rights.album_id', '=', 'albums.id')
            ->where('user_id', $userId)
            ->where('albums_id', $albumId)
            ->select('allowed', 'parent_album_id', 'path')
            ->first();
        // TODO: сделать несколько селектов в одном запросе, а то where обнуляют все поиски альбома и path уже не чекнуть
        throw new ApiException(500, [
            'res' => $res,
            'user_id' => $userId,
            'album_id' => $albumId,
        ]);
        if ($res !== null) {
            if ($res->allowed !== null) return $res->allowed;
            if ($res->path === '/')     return false;
        }
        return Album::hasAccessFastById($res->parent_album_id, $userId);
    }

    // Связи
    public function images() {
        return $this->hasMany(Image::class);
    }
    public function accessRights() {
        return $this->hasMany(AccessRight::class);
    }
    public function parentAlbum() {
        return $this->belongsTo(Album::class, 'parent_album_id');
    }
    public function childAlbums() {
        return $this->hasMany(Album::class, 'parent_album_id');
    }
}
