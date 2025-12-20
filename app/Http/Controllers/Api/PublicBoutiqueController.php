<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use App\Http\Resources\BoutiqueResource;
use App\Http\Resources\BoutiqueCollection;
use Illuminate\Http\Request;

class PublicBoutiqueController extends Controller
{
    /**
     * Display a listing of active boutiques (public endpoint)
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $perPage = max(1, min($perPage, 50));

        $query = Boutique::where('is_active', true)
            ->with(['merchant'])
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $boutiques = $query->paginate($perPage);

        return new BoutiqueCollection($boutiques);
    }

    /**
     * Display the specified active boutique (public endpoint)
     */
    public function show($id)
    {
        $boutique = Boutique::where('is_active', true)
            ->with(['merchant'])
            ->find($id);

        if (!$boutique) {
            return response()->json([
                'message' => 'Boutique non trouvée ou inactive'
            ], 404);
        }

        return response()->json(new BoutiqueResource($boutique));
    }
}
