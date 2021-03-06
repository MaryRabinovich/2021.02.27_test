<?php

namespace App\Http\Resources\Api\Drug;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\SubstanceResource;

class PartialMatchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // return parent::toArray($request);
        return [
            'id' => $this->id,
            'substances_count' => $this->substances->count(),
            'isset_substances' => $this->isset_substances,
            'substances' => SubstanceResource::collection($this->substances)
        ];
    }
}
