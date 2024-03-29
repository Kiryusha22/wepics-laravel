<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\Tag;
use Illuminate\Http\Request;
use App\Exceptions\ApiException;

class TagContoller extends Controller
{

    public function showAllAndSearch(Request $request)
    {
        $search = '%'.$request->value.'%';
        $tag = Tag::where('value', 'LIKE', $search)->get();
        return response()->json($tag);
    }

    public function create(Request $request)
    {
        $tag = new Tag;
        $tag->value = $request->value;
        $tag->save();
        return response()->json($tag);
    }


    public function rename(Request $request)
    {
        $tag = Tag::where('value', $request->old_value)->first();
        if ($tag)
        {
            $tag->value = $request->new_value;
            $tag->save();
            return response()->json($tag);
        } else {
            return response()->json(['error' => 'Тег не наден'], 404);
        }
    }


    public function delete(Request $request)
    {
        $tag = Tag::where('value', $request->value)->first();
        if ($tag) {
            $tag->delete();
            return response()->json(['message' => 'Тег удален']);
        } else {
            return response()->json(['error' => 'Тег не найден'], 404);
        }
    }

    // Выставление тега на картинку
    public function set(Request $request, $albumHash, $imageHash)
    {
        $image = Image::getByHash($albumHash, $imageHash);
        $tag = Tag::where('value', $request->tag)->first();
        if (!$tag) throw new ApiException(404, 'tag not found');
        $image->attachTag($tag->value);
        return response()->json(['message' => 'Тег выставлен на картинку']);
    }

    // Удаление тега с картинки
    public function unset(Request $request, $albumHash, $imageHash)
    {
        $image = Image::getByHash($albumHash, $imageHash);
        $tag = Tag::where('value', $request->tag)->first();
        if (!$tag) throw new ApiException(404, 'tag not found');
        $image->detachTag($tag->value);
        return response()->json(['message' => 'Тег удален с картинки']);
    }
}
