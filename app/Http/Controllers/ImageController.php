<?php

namespace App\Http\Controllers;

use App\Enums\ImageExtensionsEnum;
use App\Enums\SortTypesEnum;
use App\Exceptions\ApiDebugException;
use App\Exceptions\ApiException;
use App\Http\Requests\AlbumImagesRequest;
use App\Http\Requests\FilenameCheckRequest;
use App\Http\Requests\UploadRequest;
use App\Http\Resources\ImageResource;
use App\Models\Album;
use App\Models\Image;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public static function indexingImages(Album $album): void
    {
        // TODO: Перейти на свою индексацию через glob для быстрой и одновременной индексации картинок и папок (мб всех файлов)
        // Путь к альбому
        $path = Storage::path("images$album->path");

        //$start = microtime(true);

        // Получение файлов альбома
        $files = File::files($path); // FIXME: медленное и боится символических ссылок в отличии от Storage::

        //$time_elapsed_secs = microtime(true) - $start;
        //throw new ApiDebugException($path, $time_elapsed_secs);


        // Получение разрешённых расширений файлов
        $allowedExtensions = array_column(ImageExtensionsEnum::cases(), 'value');

        // Получение имеющихся картинок в БД
        $imagesInDB = $album->images->toArray();

        // Проход по файлам
        foreach ($files as $file) {
            $name = basename($file);

            //throw new ApiDebugException($name);

            // Проверка наличия в БД картинки, создание если нет
            $key = array_search($name, array_column($imagesInDB, 'name'));
            if ($key !== false) {
                unset($imagesInDB[$key]);
                $imagesInDB = array_values($imagesInDB);
                continue;
            }

            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if (!in_array($extension, $allowedExtensions))
                continue;

            $hash = md5(File::get($file));
            $imageModel = Image
                ::where('hash', $hash)
                ->where('album_id', $album->id)
                ->first();
            if ($imageModel) {
                $imageModel->name = $name;
                $imageModel->save();
                continue;
            }

            try {
                $sizes = getimagesize($file);
            }
            catch (\Exception $ex) {
                throw new ApiDebugException($file, $name, $ex->getMessage());
            }

            if (!$sizes) continue;

            Image::create([
                'name' => $name,
                'hash' => $hash,
                'date' => Carbon::createFromTimestamp(File::lastModified($file)),
                'size' => File::size($file),
                'width'  => $sizes[0],
                'height' => $sizes[1],
                'album_id' => $album->id,
            ]);
        }

        // Удаление не найденных картинок в БД
        Image::destroy(array_column($imagesInDB, 'id'));
    }

    public function upload(UploadRequest $request, $albumHash)
    {
        $album = Album::getByHash($albumHash);
        $files = $request->file('images');
        $path = "images$album->path";
        $allowedExts = array_column(ImageExtensionsEnum::cases(), 'value');
        $allowedExitsImploded = implode(',', $allowedExts);

        $responses = [];
        foreach ($files as $file) {
            $fileName = $file->getClientOriginalName();
            $fileExt  = $file->extension();

            // Валидация файла
            $validator = Validator::make(['file' => $file], [
                'file' => ['mimes:'. $allowedExitsImploded],
            ]);
            if ($validator->fails()) {
                // Сохранение плохого ответа API
                $responses['errored'][] = [
                    'name'    => $fileName,
                    'message' => $validator->errors(),
                ];
                continue;
            }

            // Проверка существования того же файла
            $imageHash = md5(File::get($file->getRealPath()));

            $image = Image
                ::where('album_id', $album->id)
                ->where('hash', $imageHash)
                ->first();

            if ($image) {
                // Сохранение плохого ответа API
                $responses['errored'][] = [
                    'name'    => $fileName,
                    'message' => "Image with md5 hash \"$imageHash\" already exist in this album",
                ];
                continue;
            }

            // Наименование повторяющихся
            $fileNameNoExt = basename($fileName, ".$fileExt");
            $num = 1;
            while (Storage::exists("$path$fileName")) {
                $fileName = "$fileNameNoExt ($num).$fileExt";
                $num++;
            }

            // Сохранение файла в хранилище
            $file->storeAs($path,$fileName);

            $sizes = getimagesize(Storage::path($path.$fileName));

            // Сохранение записи в БД
            $imageDB = Image::create([
                'name' => $fileName,
                'hash' => $imageHash,
                'date' => Carbon::createFromTimestamp(Storage::lastModified($path.$fileName)),
                'size' => Storage::size($path.$fileName),
                'width'  => $sizes[0],
                'height' => $sizes[1],
                'album_id' => $album->id,
            ]);

            // Сохранение успешного ответа API
            $responses['successful'][] = ImageResource::make($imageDB);
        }
        return response($responses);
    }

    public function test0(AlbumImagesRequest $request, $albumHash) {
        $start = microtime(true);
        $path = 'C:\\Users\\Kopch\\Repos\\wepics\\wepics-laravel\\storage\\app\\images/moe/Selected/Port/';

        $files = Storage::files('images/moe/Parsed/kemonoparty/patreon/12281898');
        $time_elapsed_secs = microtime(true) - $start;

        throw new ApiDebugException($time_elapsed_secs, $files);
    }

    public function test1(AlbumImagesRequest $request, $albumHash) {
        $start = microtime(true);
        $path = 'C:\\Users\\Kopch\\Repos\\wepics\\wepics-laravel\\storage\\app\\images/moe/Selected/Port/';
        $dirItems = scandir($path);

        $files = [];
        foreach($dirItems as $item)
            if (is_file($path . $item))
                $files[] = $item;
        /*
        foreach($dirItems as $index => &$item)
            if(is_dir($path . $item))
                unset($dirItems[$index]);

        $files = array_values($dirItems);
        */
        $time_elapsed_secs = microtime(true) - $start;

        throw new ApiDebugException($time_elapsed_secs, $files, $dirItems);
    }

    public function test2(AlbumImagesRequest $request, $albumHash) {
        $start = microtime(true);
        $path = 'C:\\Users\\Kopch\\Repos\\wepics\\wepics-laravel\\storage\\app\\images/moe/Selected/Port/';

        $files = array_filter(glob("$path*", GLOB_MARK), fn ($path) => !in_array($path[-1], ['/', '\\']));

        $time_elapsed_secs = microtime(true) - $start;

        throw new ApiDebugException($time_elapsed_secs, $files);
    }

    public function showAll(AlbumImagesRequest $request, $albumHash)
    {
        $album = Album::getByHash($albumHash);
        $user = $request->user();
        if (!$album->hasAccessCached($user))
            throw new ApiException(403, 'Forbidden for you');

        $cacheKey = "albumIndexing:hash=$albumHash";
        if (!Cache::get($cacheKey)) {
            ImageController::indexingImages($album);
            Cache::put($cacheKey, true, 600);
        };

        $searchedTags = null;
        $tagsString = $request->tags;
        if ($tagsString)
            $searchedTags = explode(',', $tagsString);

        $allowedSorts = array_column(SortTypesEnum::cases(), 'value');
        $sortType = $request->sort ?? $allowedSorts[0];

        $sortDirection = $request->has('reverse') ? 'DESC' : 'ASC';
        $naturalSort = "udf_NaturalSortFormat(name, 10, '.') $sortDirection";
        $orderByRaw = match ($sortType) {
            'name'  =>                                "$naturalSort",
            'ratio' => "width / height $sortDirection, $naturalSort",
            default =>      "$sortType $sortDirection, $naturalSort",
        };

        $limit = intval($request->limit);
        if (!$limit)
            $limit = 30;

        if (!$searchedTags)
            $imagesFromDB = Image
                ::where('album_id', $album->id)
                ->orderByRaw($orderByRaw)
                ->paginate($limit);
        else
            $imagesFromDB = Image
                ::where('album_id', $album->id)
                ->orderByRaw($orderByRaw)
                ->withAllTags($searchedTags)
                ->paginate($limit);


        $response = [
            'page'     => $imagesFromDB->currentPage(),
            'per_page' => $imagesFromDB->perPage(),
            'total'    => $imagesFromDB->total(),
            'pictures' => ImageResource::collection($imagesFromDB->items()),
        ];
        if (!$album->hasAccessCached())
            $response['sign'] = $this->getSign($user, $albumHash);

        return response($response);
    }
    public function getSign(User $user, $albumHash): string {
        $cacheKey = "signAccess:to=$albumHash;for=$user->id";
        $cachedSign = Cache::get($cacheKey);
        if ($cachedSign) return $user->id .'_'. $cachedSign;

        $currentDay = date("Y-m-d");
        $userToken = $user->tokens[0]->value;

        $string = $userToken . $currentDay . $albumHash;
        $signCode = base64_encode(Hash::make($string));

        Cache::put($cacheKey, $signCode, 3600);
        return $user->id .'_'. $signCode;
    }

    public function checkSign($albumHash, $sign): bool
    {
        try {
            $signExploded = explode('_', $sign);
            $userId   = $signExploded[0];
            $signCode = $signExploded[1];
        }
        catch (\Exception $e) {
            return false;
        }

        $cacheKey = "signAccess:to=$albumHash;for=$userId";
        $cachedSign = Cache::get("signAccess:to=$albumHash;for=$userId");
        if ($cachedSign === $signCode) return true;

        $user = User::find($signExploded[0]);
        if (!$user)
            return false;

        $currentDay = date("Y-m-d");
        $string = $user->tokens[0]->value . $currentDay . $albumHash;

        $allow = Hash::check($string, base64_decode($signExploded[1]));
        Cache::put($cacheKey, $signCode, 3600);

        return $allow;
    }
    public function thumb($albumHash, $imageHash, $orientation, $size)
    {
        // Проверка доступа
        $sign = request()->sign;
        if (
            !Album::hasAccessCachedByHash($albumHash) &&
            !($sign && $this->checkSign($albumHash, $sign))
        ) throw new ApiException(403, 'Forbidden for you');

        // Проверка наличия превью в файлах
        $thumbPath = "thumbs/$imageHash-$orientation$size.webp";
        if (!Storage::exists($thumbPath)) {
            // Проверка запрашиваемого размера и редирект, если не прошло
            $askedSize = $size;
            $allowedSizes = [144, 240, 360, 480, 720, 1080];
            $allowSize = false;
            foreach ($allowedSizes as $allowedSize) {
                if ($size <= $allowedSize) {
                    $size = $allowedSize;
                    $allowSize = true;
                    break;
                }
            }
            if (!$allowSize) $size = $allowedSizes[count($allowedSizes)-1];
            if ($askedSize != $size)
                return redirect()->route('get.image.thumb', [
                    $albumHash, $imageHash, $orientation, $size
                ])->header('Cache-Control', ['max-age=86400', 'private']);;

            // Проверка наличия превью в файлах x2
            $thumbPath = "thumbs/$imageHash-$orientation$size.webp";
            if (!Storage::exists($thumbPath)) {
                // Создание превью
                if (!isset($image)) $image = Image::getByHash($albumHash, $imageHash);

                $imagePath = 'images'. $image->album->path . $image->name;

                $manager = new ImageManager(new Driver());
                $thumb = $manager->read(Storage::get($imagePath));

                if ($orientation == 'w')
                    $thumb->scale(width: $size);
                else
                    $thumb->scale(height: $size);

                if (!Storage::exists('thumbs'))
                    Storage::makeDirectory('thumbs');

                $thumb->toWebp(90)->save(Storage::path($thumbPath));
            }
        }
        return response()->file(Storage::path($thumbPath), ['Cache-Control' => ['max-age=86400', 'private']]);
    }

    public function show($albumHash, $imageHash)
    {
        $image = Image::getByHash($albumHash, $imageHash);
        if (!$image->album->hasAccessCached(request()->user()))
            throw new ApiException(403, 'Forbidden for you');

        return response(ImageResource::make($image));
    }

    public function orig($albumHash, $imageHash)
    {
        $image = Image::getByHash($albumHash, $imageHash);
        if (!$image->album->hasAccessCached(request()->user()))
            throw new ApiException(403, 'Forbidden for you');

        $path = Storage::path('images'. $image->album->path . $image->name);
        return response()->file($path);
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
