<?php

namespace App\Http\Controllers;

use App\Http\Requests\TagRenameRequest;
use App\Http\Requests\TagRequest;
use App\Models\Image;
use App\Models\Tag;
use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TagController extends Controller
{
    // Вывод всех или поиск тега
    public function showAllOrSearch(Request $request)
    {
        if ($request->tag) {
            $search = "%$request->tag%";
            $tags = Tag::where('value', 'LIKE', $search)->get();
        }
        else
            $tags = Tag::all();

        return response($tags);
    }
    // Создание тега
    public function create(TagRequest $request)
    {
        Tag::create(['value' => $request->tag]);
        return response(null, 201);
    }
    // Переименование тега
    public function rename(TagRenameRequest $request)
    {
        $tag = Tag::where('value', $request->old_value)->first();
        if (!$tag)
            throw new ApiException(404, 'Tag not found');

        $tagWithNewValue = Tag::where('value', $request->new_value)->first();
        if ($tag)
            throw new ApiException(409, 'Tag with this value already exist');

        $tag->value = $request->new_value;
        $tag->save();
        return response(null, 204);
    }
    // Удаление тега
    public function delete(TagRequest $request)
    {
        $tag = Tag::where('name', $request->value)->first();
        if (!$tag)
            throw new ApiException(404, 'Tag not found');

        $tag->delete();
        return response(null, 204);
    }
    // Выставление тега на картинку
    public function set(TagRequest $request, $albumHash, $imageHash)
    {
        $tag = Tag::where('value', $request->tag)->first();
        if (!$tag)
            throw new ApiException(404, 'Tag not found');

        $image = Image::getByHash($albumHash, $imageHash);

        $attachedTag = DB::table('tag_image')
            ->where('image_id', $image->id)
            ->where('tag_id', $tag->id)
            ->first();
        if ($attachedTag)
            throw new ApiException(409, 'This tag already set');

        DB::table('tag_image')->insert([
            'image_id' => $image->id,
            'tag_id' => $tag->id,
        ]);

        return response(null, 204);
    }
    // Удаление тега с картинки
    public function unset(TagRequest $request, $albumHash, $imageHash)
    {
        $tag = Tag::where('value', $request->tag)->first();
        if (!$tag)
            throw new ApiException(404, 'Tag not found');

        $image = Image::getByHash($albumHash, $imageHash);

        $attachedTag = DB::table('tag_image')
            ->where('image_id', $image->id)
            ->where('tag_id', $tag->id)
            ->first();
        if (!$attachedTag)
            throw new ApiException(409, 'This tag already not set');

        DB::table('tag_image')
            ->where('image_id', $image->id)
            ->where('tag_id', $tag->id)
            ->delete();

        return response(null, 204);
    }
}
