<?php

namespace App\Http\Resources;

use App\Models\ReactionImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class ImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userId = $request?->user()?->id;
        $reactions = ReactionImage
            ::join('reactions', 'reactions.id', '=', 'reaction_id')
            ->select(
                'value',
                DB::raw('COUNT(*) AS count'),
                DB::raw('COUNT(CASE user_id WHEN '
                    .($userId ? $userId : 'NULL')
                    .' THEN 1 ELSE null END) AS isYouSet')
            )
            ->groupBy('value')
            ->where('image_id', $this->id)
            ->orderBy('count', 'DESC')
            ->get()
            ->toArray();

        $response = [
//          'id'        => $this->id,
            'name'      => $this->name,
            'hash'      => $this->hash,
            'date'      => $this->date,
            'size'      => $this->size,
            'width'     => $this->width,
            'height'    => $this->height,
//          'album_id'  => $this->album_id,
        ];
        $tags = $this->tags;
        if (count($tags))
            $response['tags'] = TagResource::collection($tags);

        if ($reactions) {
            foreach ($reactions as $reaction) {
                $reactionsResponse[$reaction['value']]['count'] = $reaction['count'];

                if ($reaction['isYouSet'])
                    $reactionsResponse[$reaction['value']]['isYouSet'] = true;
            }
            $response['reactions'] = $reactionsResponse;
        }

        return $response;
    }
}
