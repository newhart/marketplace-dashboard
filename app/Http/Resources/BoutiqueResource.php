<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Product;
use App\Http\Resources\ProductResource;

class BoutiqueResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'merchant_id' => $this->merchant_id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'photo' => $this->photo ? asset('storage/' . $this->photo) : null,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'postal_box' => $this->postal_box,
            'opening_date' => $this->opening_date,
            'closing_date' => $this->closing_date,
            'opening_hours' => $this->opening_hours,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'products' => ProductResource::collection(
                Product::where('user_id', $this->merchant->user_id)
                    ->inRandomOrder()
                    ->limit(4)
                    ->get()
            ),
        ];
    }
}
