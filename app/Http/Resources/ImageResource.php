<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
//          'id'        => $this->id,
            'name'      => $this->name,
            'hash'      => $this->hash,
            'date'      => $this->date,
            'size'      => $this->size,
            'width'     => $this->width,
            'height'    => $this->height,
//          'album_id'  => $this->album_id,
            'tags'      =>      TagResource::collection($this->tags),
            'reactions' => ReactionResource::collection($this->reactions),
        ];
    }
}
