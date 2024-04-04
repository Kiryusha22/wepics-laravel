<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\Requests\ReactionRequest;
use App\Models\Image;
use App\Models\Reaction;
use App\Models\ReactionImage;
use Illuminate\Http\Request;

class ReactionController extends Controller
{
    // Получение разрешённых реакций
    public function showAll()
    {
        $reactions = Reaction::get();
        return response()->json($reactions);
    }
    // Добавление разрешённых реакций
    public function add(ReactionRequest $request)
    {
        foreach ($request->reactions  as $reaction)
        {
            Reaction::create(['value'=>$reaction]);
        }
        return response(null, 204);

    }
    // Удаление разрешённых реакций
    public function remove(ReactionRequest $request)
    {

        foreach ($request->reactions  as $reaction)
        {
            $reactionFromDB = Reaction::where('value',$reaction)->first();
            if(!$reactionFromDB) throw new ApiException(404, 'reaction not found');
            $reactionFromDB->delete();
        }
        return response(null, 204);
    }
    // Выставление реакций на картинку
    public function set(Request $request, $albumHash, $imageHash)
    {
        // Проверяем, передан ли эмодзи
       $emoji = $request->input('emoji');
        if (!$emoji) {
            return response()->json(['message' => 'Эмодзи не указан'], 422);
        }

        // Проверяем, есть ли такое эмодзи в "разрешённых" реакциях
        $allowedReaction = Reaction::whereRaw("value LIKE '$emoji'")
            ->first();

        if (!$allowedReaction) {
            return response()->json(['message' => 'Эмодзи не разрешено'], 403);
        }
        $image = Image::getByHash($albumHash, $imageHash);
        ReactionImage::create([
            'image_id'=>$image->id,
            'reaction_id'=> $allowedReaction->id,
            'user_id'=> request()->user()->id
        ]);
        return response()->json(['message' => 'Реакция установлена'], 200);
    }
    // Удаление реакции с картинки
    public function unset(Request $request, $albumHash, $imageHash)
    {
        // Проверяем, передан ли эмодзи
        $emoji = $request->input('emoji');
        if (!$emoji) {
            return response()->json(['message' => 'Эмодзи не указан'], 422);
        }

        // Получаем картинку по хешу
        $image = Image::getByHash($albumHash, $imageHash);
        if (!$image) {
            return response()->json(['message' => 'Картинка не найдена'], 404);
        }

        // Получаем реакцию по эмодзи
        $reaction = Reaction::where("value", "LIKE", $emoji)->first();
        if (!$reaction) {
            return response()->json(['message' => 'Реакция не найдена'], 404);
        }

        // Удаляем реакцию с картинки
        $reactionImage = ReactionImage::where('image_id', $image->value)
            ->where('reaction_id', $reaction->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($reactionImage) {
            $reactionImage->delete();
            return response()->json(['message' => 'Реакция удалена'], 200);
        } else {
            return response()->json(['message' => 'Реакция не найдена'], 404);
        }
    }
}
