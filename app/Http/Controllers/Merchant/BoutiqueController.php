<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use App\Http\Resources\BoutiqueResource;
use App\Http\Resources\BoutiqueCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BoutiqueController extends Controller
{
    /**
     * Display a listing of the merchant's boutiques
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $perPage = max(1, min($perPage, 50));

        $query = Boutique::whereHas('merchant', function ($q) {
            $q->where('user_id', Auth::id());
        })->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $boutiques = $query->paginate($perPage);

        return new BoutiqueCollection($boutiques);
    }

    /**
     * Store a newly created boutique
     */
    public function store(Request $request)
    {
        $merchant = Auth::user()->merchant;

        if (!$merchant) {
            return response()->json([
                'message' => 'Vous devez avoir un compte commerçant'
            ], 403);
        }

        // Pre-process request data to handle JSON strings in FormData
        $input = $request->all();
        if (isset($input['opening_hours']) && is_string($input['opening_hours'])) {
            $decoded = json_decode($input['opening_hours'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $input['opening_hours'] = $decoded;
            }
        }

        $validator = Validator::make($input, [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'photo' => 'nullable', // Changed to nullable to accept file or base64 string
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'postal_box' => 'nullable|string|max:255',
            'opening_hours' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('boutiques', 'public');
            $data['photo'] = $path;
        } elseif ($request->filled('photo')) {
            // Handle Base64 image
            $photo = $request->input('photo');
            if (is_string($photo)) {
                if (preg_match('/^data:image\/(\w+);base64,/', $photo, $type)) {
                    $imageContent = substr($photo, strpos($photo, ',') + 1);
                    $type = strtolower($type[1]); // jpg, png, gif

                    if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
                        return response()->json([
                            'message' => 'Validation failed',
                            'errors' => ['photo' => ['Format image non supporté (jpg, jpeg, png, gif uniquement)']]
                        ], 422);
                    }

                    $decodedImage = base64_decode($imageContent);
                    if ($decodedImage === false) {
                        return response()->json([
                            'message' => 'Validation failed',
                            'errors' => ['photo' => ['Invalid base64 string']]
                        ], 422);
                    }

                    $filename = 'boutiques/' . uniqid() . '.' . $type;
                    Storage::disk('public')->put($filename, $decodedImage);
                    $data['photo'] = $filename;
                } elseif (strpos($photo, 'file://') === 0) {
                    return response()->json([
                        'message' => 'Validation failed',
                        'errors' => ['photo' => ['Les URIs file:// ne sont pas supportées. Veuillez envoyer l\'image en Base64.']]
                    ], 422);
                }
            }
        }

        $data['merchant_id'] = $merchant->id;

        $boutique = Boutique::create($data);

        return response()->json([
            'message' => 'Boutique créée avec succès',
            'data' => new BoutiqueResource($boutique)
        ], 201);
    }

    /**
     * Display the specified boutique
     */
    public function show($id)
    {
        $boutique = Boutique::whereHas('merchant', function ($q) {
            $q->where('user_id', Auth::id());
        })->findOrFail($id);

        return response()->json(new BoutiqueResource($boutique));
    }

    /**
     * Update the specified boutique
     */
    public function update(Request $request, $id)
    {
        $boutique = Boutique::whereHas('merchant', function ($q) {
            $q->where('user_id', Auth::id());
        })->findOrFail($id);

        // Pre-process request data to handle JSON strings in FormData
        $input = $request->all();
        if (isset($input['opening_hours']) && is_string($input['opening_hours'])) {
            $decoded = json_decode($input['opening_hours'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $input['opening_hours'] = $decoded;
            }
        }

        $validator = Validator::make($input, [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'photo' => 'nullable', // Changed to nullable
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'postal_box' => 'nullable|string|max:255',
            'opening_hours' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo
            if ($boutique->photo) {
                Storage::disk('public')->delete($boutique->photo);
            }
            $path = $request->file('photo')->store('boutiques', 'public');
            $data['photo'] = $path;
        } elseif ($request->filled('photo')) {
            // Handle Base64 image
            $photo = $request->input('photo');
            if (is_string($photo)) {
                if (preg_match('/^data:image\/(\w+);base64,/', $photo, $type)) {
                    // Delete old photo if exists
                    if ($boutique->photo) {
                        Storage::disk('public')->delete($boutique->photo);
                    }

                    $imageContent = substr($photo, strpos($photo, ',') + 1);
                    $type = strtolower($type[1]); // jpg, png, gif

                    if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
                        return response()->json([
                            'message' => 'Validation failed',
                            'errors' => ['photo' => ['Format image non supporté (jpg, jpeg, png, gif uniquement)']]
                        ], 422);
                    }

                    $decodedImage = base64_decode($imageContent);
                    if ($decodedImage === false) {
                        return response()->json([
                            'message' => 'Validation failed',
                            'errors' => ['photo' => ['Invalid base64 string']]
                        ], 422);
                    }

                    $filename = 'boutiques/' . uniqid() . '.' . $type;
                    Storage::disk('public')->put($filename, $decodedImage);
                    $data['photo'] = $filename;
                } elseif (strpos($photo, 'file://') === 0) {
                    return response()->json([
                        'message' => 'Validation failed',
                        'errors' => ['photo' => ['Les URIs file:// ne sont pas supportées. Veuillez envoyer l\'image en Base64.']]
                    ], 422);
                }
            }
        }

        $boutique->update($data);

        return response()->json([
            'message' => 'Boutique mise à jour avec succès',
            'data' => new BoutiqueResource($boutique)
        ]);
    }

    /**
     * Remove the specified boutique
     */
    public function destroy($id)
    {
        $boutique = Boutique::whereHas('merchant', function ($q) {
            $q->where('user_id', Auth::id());
        })->findOrFail($id);

        // Delete photo from storage
        if ($boutique->photo && Storage::disk('public')->exists($boutique->photo)) {
            Storage::disk('public')->delete($boutique->photo);
        }

        $boutique->delete();

        return response()->json([
            'message' => 'Boutique supprimée avec succès'
        ]);
    }
}
