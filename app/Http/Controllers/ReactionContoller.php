<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\Reaction;
use Illuminate\Http\Request;

class ReactionContoller extends Controller
{
    // Получение разрешённых реакций
    public function showAll()
    {
        // Логика для получения всех разрешённых реакций
        $reactions = Reaction::where('allowed', true)->get();
        return response()->json($reactions);
    }

    // Добавление разрешённых реакций
    public function add(Request $request)
    {
        // Логика для добавления новой разрешённой реакции
        $reaction = new Reaction;
        $reaction->name = $request->input('name');
        $reaction->allowed = true;
        $reaction->save();

        return response()->json($reaction, 201);
    }

    // Удаление разрешённых реакций
    public function remove($id)
    {
        // Логика для удаления разрешённой реакции
        $reaction = Reaction::findOrFail($id);
        $reaction->delete();

        return response()->json(null, 204);
    }

    // Выставление реакции на картинку
    public function set(Request $request)
    {
        // Проверяем, чтобы картинка и реакция существовали
        $image = Image::findOrFail($request->input('image_id'));
        $reaction = Reaction::findOrFail($request->input('reaction_id'));

        // Проверяем, что реакция разрешена
        if (!$reaction->allowed) {
            return response()->json(['error' => 'Reaction is not allowed.'], 403);
        }

        // Выставляем реакцию на картинку
        $image->reactions()->attach($reaction);

        return response()->json(['message' => 'Reaction set successfully.'], 200);
    }

    // Удаление реакции с картинки
    public function unset(Request $request)
    {
        // Проверяем, чтобы картинка и реакция существовали
        $image = Image::findOrFail($request->input('image_id'));
        $reaction = Reaction::findOrFail($request->input('reaction_id'));

        // Удаляем реакцию с картинки
        $image->reactions()->detach($reaction);

        return response()->json(['message' => 'Reaction unset successfully.'], 200);
    }
}
