<?php

namespace App\Services;

use App\Http\Resources\CategoryCollection;
use App\Models\Category;

class CategoryService
{
    public function rays()
    {
        $categories = Category::whereHas('products')
            ->get();
        return new CategoryCollection($categories);
    }
}
