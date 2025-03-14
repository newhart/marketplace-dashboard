<?php

namespace App\Http\Controllers;

use App\Services\CategoryService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(public CategoryService $categoryService)
    {
        
    }
    public function  rays()
    {
        return  response()->json($this->categoryService->rays());
    }
}
