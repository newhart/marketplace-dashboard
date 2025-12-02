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
        $imageUrl = null;
        if ($this->relationLoaded('images') && $this->images->count() > 0) {
            $imagePath = $this->images[0]->path;
            $imageUrl = asset('storage/' . $imagePath);
        }

        return  [
            'id' => $this->id , 
            'name' => $this->name , 
            'short_description' => $this->short_description ,
            'unit' => $this->unit ,
            'description' => $this->description , 
            'image' => $imageUrl ?? $this->image, 
            'price' => $this->price , 
            'rating' => $this->rating , 
            'category' => new CategoryResource($this->whenLoaded('category')) , 
            'firstActiveImage' => $this->firstActiveImage ,
        ] ; 
    }
}
