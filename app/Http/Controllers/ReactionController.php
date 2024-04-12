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
        foreach ($request->reactions as $reaction) {
            // Проверяем, существует ли реакция с таким значением
            if (!Reaction::where('value', $reaction)->exists()) {
                Reaction::create(['value' => $reaction]);
            }
        }

        return response(null, 204);
    }
    // Удаление разрешённых реакций
    public function remove(ReactionRequest $request)
    {
        foreach ($request->reactions  as $reaction) {
            $reactionFromDB = Reaction::where('value',$reaction)->first();
            if(!$reactionFromDB)
                throw new ApiException(404, "Reaction \"$reaction\" not found");

            $reactionFromDB->delete();
        }
        return response(null, 204);
    }
    // Выставление реакций на картинку
    public function set(Request $request, $albumHash, $imageHash)
    {
       $emoji = $request->reaction;
        if (!$emoji)
            throw new ApiException(422, 'Reaction not specified');

        $allowedReaction = Reaction::where('value', 'LIKE', $emoji)->first();
        if (!$allowedReaction)
            throw new ApiException(404, 'Reaction not allowed');

        $image = Image::getByHash($albumHash, $imageHash);

        $reactionImage = ReactionImage
            ::where('image_id', $image->id)
            ->where('reaction_id', $allowedReaction->id)
            ->where('user_id', $request->user()->id)
            ->first();
        if ($reactionImage)
            throw new ApiException(409, 'You already set this reaction');

        ReactionImage::create([
            'image_id'=>$image->id,
            'reaction_id'=> $allowedReaction->id,
            'user_id'=> request()->user()->id
        ]);
        return response(null, 204);
    }
    // Удаление реакции с картинки
    public function unset(Request $request, $albumHash, $imageHash)
    {
        $emoji = $request->reaction;
        if (!$emoji)
            throw new ApiException(422, 'Reaction not specified');

        $allowedReaction = Reaction::where('value', 'LIKE', $emoji)->first();
        if (!$allowedReaction)
            throw new ApiException(404, 'Reaction not allowed');

        $image = Image::getByHash($albumHash, $imageHash);

        $reactionImage = ReactionImage
            ::where('image_id', $image->id)
            ->where('reaction_id', $allowedReaction->id)
            ->where('user_id', $request->user()->id)
            ->first();
        if (!$reactionImage)
            throw new ApiException(409, 'You did not set this reaction');

        ReactionImage
            ::where('image_id', $image->id)
            ->where('reaction_id', $allowedReaction->id)
            ->where('user_id', $request->user()->id)
            ->delete();
        return response(null, 204);
    }
}
