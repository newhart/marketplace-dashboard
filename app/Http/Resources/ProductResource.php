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

        // Récupérer toutes les images (limitées à 5 dans le service)
        $images = [];
        if ($this->relationLoaded('images')) {
            $images = $this->images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'path' => asset('storage/' . $image->path),
                    'is_main' => $image->is_main ?? false,
                    'created_at' => $image->created_at
                ];
            })->toArray();
        }

        // Calculer la moyenne des avis (rating moyen)
        $averageRating = null;
        if ($this->relationLoaded('reviews')) {
            $averageRating = round($this->reviews->avg('rating'), 1);
        } else {
            $averageRating = round($this->reviews()->avg('rating'), 1);
        }

        return  [
            'id' => $this->id , 
            'name' => $this->name , 
            'short_description' => $this->short_description ,
            'unity' => $this->unity ,
            'description' => $this->description , 
            'image' => $imageUrl ?? $this->image, 
            'images' => $images, // Toutes les images (jusqu'à 5 maximum)
            'price' => $this->price , 
            'rating' => $averageRating , 
            'category' => new CategoryResource($this->whenLoaded('category')) , 
            'firstActiveImage' => $this->firstActiveImage ,
            'origin' => $this->origin , 
            'stock' => $this->stock
        ] ; 
    }
}
