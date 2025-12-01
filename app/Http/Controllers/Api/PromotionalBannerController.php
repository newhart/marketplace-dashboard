<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PromotionalBanner;
use Illuminate\Http\Request;

class PromotionalBannerController extends Controller
{
    /**
     * Get active promotional banners
     */
    public function index()
    {
        $banners = PromotionalBanner::active()
            ->current()
            ->orderBy('display_order', 'asc')
            ->get()
            ->map(function ($banner) {
                return [
                    'id' => (string) $banner->id,
                    'title' => $banner->title,
                    'subtitle' => $banner->subtitle,
                    'image' => $banner->image_url,
                    'product_id' => $banner->product_id ? (string) $banner->product_id : null,
                    'start_date' => $banner->start_date,
                    'end_date' => $banner->end_date,
                ];
            });

        return response()->json($banners);
    }
}
