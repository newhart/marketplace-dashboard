<?php

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return  [
            'id' => $this->id , 
            'name' => $this->name , 
            'short_description' => $this->short_description ,
            'unit' => $this->unit ,
            'description' => $this->description , 
            'image' => $this->image , 
            'price' => $this->price , 
            'rating' => $this->rating , 
            'category' => new CategoryResource($this->whenLoaded('category'))
        ] ; 
    }
}
