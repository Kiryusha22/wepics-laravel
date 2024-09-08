<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiDebugException;
use App\Exceptions\ApiException;
use App\Http\Requests\FilenameCheckRequest;
use App\Models\Album;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AlbumController extends Controller
{
    public static function indexingAlbumChildren(Album $album): array
    {
        // TODO: Перейти на свою индексацию через glob для быстрой и одновременной индексации картинок и папок (мб всех файлов)
        // Получение пути к альбому и его папок
        $localPath = "images$album->path";
        $folders = Storage::directories($localPath);
        $childrenInDB = $album->childAlbums->toArray();

        // Проход по папкам альбома для ответа (дочерние альбомы)
        $children = [];
        foreach ($folders as $folder) {
            $path = $album->path . basename($folder) .'/';

            // Проверка наличия в БД вложенного альбома, создание если нет
            $key = array_search($path, array_column($childrenInDB, 'path'));
            if ($key !== false) {
                $childAlbum = $childrenInDB[$key];
                unset($childrenInDB[$key]);
                $childrenInDB = array_values($childrenInDB);
            }
            else {
                $childAlbum = Album::create([
                    'name' => basename($path),
                    'path' => $path,
                    'hash' => Str::random(25),
                    'parent_album_id' => $album->id
                ]);
            }
            $children[] = $childAlbum;
        }
        // Удаление оставшихся альбомов в БД
        Album::destroy(array_column($childrenInDB, 'id'));

        $album->last_indexation = now();
        $album->save();
        return $children;
    }
    public function reindex($hash)
    {
        // Получение пользователя
        $user = request()->user();

        // Получение альбома из БД и проверка доступа пользователю
        $targetAlbum = Album::getByHash($hash);
        if (!$targetAlbum->hasAccessCached($user))
            throw new ApiException(403, 'Forbidden for you');

        AlbumController::indexingAlbumChildren($targetAlbum);

        ImageController::indexingImages($targetAlbum);

        return response(null);
    }
    public function get($hash)
    {
        // Получение пользователя
        $user = request()->user();

        // Получение альбома из БД и проверка доступа пользователю
        $targetAlbum = Album::getByHash($hash);
        if (!$targetAlbum->hasAccessCached($user))
            throw new ApiException(403, 'Forbidden for you');

        // Получение вложенных альбомов из БД если индексировалось, иначе индексировать
        //TODO: мб добавить опцию через сколько времени надо переиндексировать?
        if ($targetAlbum->last_indexation === null)
            $allowedChildren = AlbumController::indexingAlbumChildren($targetAlbum);
        else {
            $children = Album::where('parent_album_id', $targetAlbum->id)->get();
            $allowedChildren = [];
            foreach ($children as $child)
                if ($child->hasAccessCached($user))
                    $allowedChildren[] = $child;
        }

        // Проход по родителям альбома для ответа (цепочка родителей)
        $parentsChain = [];
        $parentId = $targetAlbum->parent_album_id;
        while ($parentId) {
            $parent = Album::find($parentId);

            // Прерывание записи, если в БД нет альбома с таким ID или нет доступа к n-родительскому альбому
            if (!$parent || !$parent->hasAccessCached($user)) break;

            // Прерывание записи, если нет доступа к n-родительскому альбому
            $parentId = $parent->parent_album_id;
            $parentsChain[] = $parent;
        }

        // Компактный объект ответа
        $response = ['name' => $targetAlbum->name];
        if ($targetAlbum->last_indexation)
            $response['last_indexation'] = $targetAlbum->last_indexation;

        if (count($allowedChildren)) {
            foreach ($allowedChildren as $album)
                $childrenRefined[$album->name] = ['hash' => $album->hash];

            $response['children'] = $childrenRefined;
        }
        if (count($parentsChain)) {
            foreach (array_reverse($parentsChain) as $album) {
                if ($album->path === '/') $parentsChainRefined['/'] = ['hash' => $album->hash];
                else             $parentsChainRefined[$album->name] = ['hash' => $album->hash];
            }
            $response['parentsChain'] = $parentsChainRefined;
        }
        return response($response);
    }

    public function create(FilenameCheckRequest $request, $hash)
    {
        $parentAlbum = Album::getByHash($hash);
        $newFolderName = $request->name;

        $path = "images$parentAlbum->path$newFolderName";
        if (Storage::exists($path))
            throw new ApiException(409, 'Album with this name already exist');

        Storage::createDirectory($path);
        $newAlbum = Album::create([
            'name' => basename($path),
            'path' => "$parentAlbum->path$newFolderName/",
            'hash' => Str::random(25),
            'parent_album_id' => $parentAlbum->id
        ]);
        return response($newAlbum);
    }

    public function rename(FilenameCheckRequest $request, $hash)
    {
        $album = Album::getByHash($hash);
        $newFolderName = $request->name;

        $oldLocalPath = "images$album->path";
        $newPath = dirname($album->path) .'/'. $newFolderName .'/';
        $newLocalPath = "images$newPath";
        if (Storage::exists($newPath))
            throw new ApiException(409, 'Album with this name already exist');

        Storage::move($oldLocalPath, $newLocalPath);
        $album->update([
            'name' => basename($newPath),
            'path' => "$newPath",
        ]);
        return response(null, 204);
    }

    public function delete($hash)
    {
        $album = Album::getByHash($hash);
        $path = Storage::path("images$album->path");

        if ($album->path == '/')
            File::cleanDirectory($path);
        else
            File::deleteDirectory($path);

        $album->delete();
        return response(null, 204);
    }
}
