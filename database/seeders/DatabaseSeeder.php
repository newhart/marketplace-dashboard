<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Crée des utilisateurs
        User::factory()->count(10)->create();

        $allUsers = User::all();
        $merchants = $allUsers->where('role', 'merchant');
        if ($merchants->isEmpty()) {
            $merchants = $allUsers;
        }

        // Appel API OpenFoodFacts pour récupérer les catégories
        $response = Http::get("https://world.openfoodfacts.org/categories.json");

        if (!$response->ok()) {
            echo "❌ Erreur lors de la récupération des catégories depuis OpenFoodFacts\n";
            return;
        }

        $data = $response->json();
        $tags = $data['tags'] ?? [];
        $categoryModels = [];

        echo "📦 Récupération de " . count($tags) . " catégories depuis OpenFoodFacts...\n";

        // On ne garde que les 15 premières catégories
        foreach (array_slice($tags, 0, 15) as $tag) {
            $slug = $tag['id'];
            $name = $tag['name'];

            // Récupérer l'image de la catégorie via l'API : chercher la première image valide parmi les produits
            $image = null;
            try {
                $categoryResponse = Http::get("https://world.openfoodfacts.org/category/{$slug}/1.json");
                if ($categoryResponse->ok()) {
                    $categoryData = $categoryResponse->json();
                    $products = $categoryData['products'] ?? [];

                    // Chercher la première image valide parmi les produits
                    foreach ($products as $product) {
                        if (!empty($product['image_url'])) {
                            $image = $product['image_url'];
                            break;
                        } elseif (!empty($product['image_front_url'])) {
                            $image = $product['image_front_url'];
                            break;
                        } elseif (!empty($product['image_small_url'])) {
                            $image = $product['image_small_url'];
                            break;
                        }
                    }
                }
            } catch (\Exception $e) {
                // En cas d'erreur, on continue avec une image par défaut
            }

            // Si aucune image trouvée, utiliser une image par défaut
            if (empty($image)) {
                $image = 'https://via.placeholder.com/300x200?text=' . urlencode($name);
            }

            // Création de la catégorie
            $category = Category::create([
                'parent_id' => null,
                'name' => $name,
                'path' => 'root/' . $slug,
                'user_id' => $allUsers->random()->id,
                'image' => $image,
            ]);

            $categoryModels[$slug] = $category;
            echo "✅ Catégorie créée : {$name} (ID: {$slug})\n";
        }

        echo "\n🛍️ Récupération des produits pour chaque catégorie...\n";

        // Pour chaque catégorie : récupération de produits depuis OpenFoodFacts
        foreach ($categoryModels as $slug => $category) {
            echo "\n📋 Récupération des produits pour la catégorie : {$category->name}\n";
            echo "🔗 URL API : https://world.openfoodfacts.org/category/{$slug}/1.json\n";

            $response = Http::get("https://world.openfoodfacts.org/category/{$slug}/1.json");

            if (!$response->ok()) {
                echo "⚠️ Impossible de récupérer les produits pour {$category->name} (Status: {$response->status()})\n";
                continue;
            }

            $data = $response->json();
            $products = $data['products'] ?? [];

            echo "📊 Nombre de produits reçus de l'API : " . count($products) . "\n";

            if (empty($products)) {
                echo "⚠️ Aucun produit trouvé pour la catégorie {$category->name}\n";
                continue;
            }

            $count = 0;
            $skipped = 0;

            // Prendre seulement les 10 premiers produits pour éviter les timeouts
            $productsToProcess = array_slice($products, 0, 10);

            foreach ($productsToProcess as $index => $item) {
                // Récupérer le code produit
                $productCode = $item['code'] ?? null;

                if (empty($productCode)) {
                    echo "  ⚠️ Produit #{$index} ignoré : code produit manquant\n";
                    $skipped++;
                    continue;
                }

                echo "  🔍 Traitement du produit #{$index} avec le code : {$productCode}\n";

                // Récupérer les détails complets du produit via l'API v0
                $productDetailResponse = Http::get("https://world.openfoodfacts.org/api/v0/product/{$productCode}.json");

                if (!$productDetailResponse->ok()) {
                    echo "    ⚠️ Impossible de récupérer les détails du produit {$productCode}\n";
                    $skipped++;
                    continue;
                }

                $productDetail = $productDetailResponse->json();

                if ($productDetail['status'] !== 1) {
                    echo "    ⚠️ Produit {$productCode} non trouvé ou invalide\n";
                    $skipped++;
                    continue;
                }

                $productData = $productDetail['product'] ?? [];

                // Essayer différents champs pour le nom du produit
                $productName = null;
                if (!empty($productData['product_name'])) {
                    $productName = $productData['product_name'];
                } elseif (!empty($productData['product_name_fr'])) {
                    $productName = $productData['product_name_fr'];
                } elseif (!empty($productData['product_name_en'])) {
                    $productName = $productData['product_name_en'];
                } elseif (!empty($productData['generic_name'])) {
                    $productName = $productData['generic_name'];
                } elseif (!empty($productData['generic_name_fr'])) {
                    $productName = $productData['generic_name_fr'];
                } elseif (!empty($productData['brands'])) {
                    $productName = $productData['brands'] . ' - Produit';
                }

                if (empty($productName)) {
                    echo "    ⚠️ Produit ignoré : nom manquant\n";
                    $skipped++;
                    continue;
                }

                echo "    📦 Nom du produit : {$productName}\n";

                // Gestion de l'image du produit
                $productImage = null;
                if (isset($productData['image_url']) && !empty($productData['image_url'])) {
                    $productImage = $productData['image_url'];
                } elseif (isset($productData['image_front_url']) && !empty($productData['image_front_url'])) {
                    $productImage = $productData['image_front_url'];
                } elseif (isset($productData['image_small_url']) && !empty($productData['image_small_url'])) {
                    $productImage = $productData['image_small_url'];
                } else {
                    $productImage = 'https://via.placeholder.com/400x400?text=' . urlencode($productName);
                }

                // Gestion du prix
                $price = 10.0; // Prix par défaut
                if (isset($productData['product_quantity']) && !empty($productData['product_quantity'])) {
                    $price = rand(100, 1000) / 10;
                }

                // Gestion de la description
                $description = null;
                if (isset($productData['ingredients_text']) && !empty($productData['ingredients_text'])) {
                    $description = $productData['ingredients_text'];
                } elseif (isset($productData['generic_name']) && !empty($productData['generic_name'])) {
                    $description = $productData['generic_name'];
                }

                // Gestion de la description courte
                $shortDescription = null;
                if (isset($productData['generic_name']) && !empty($productData['generic_name'])) {
                    $shortDescription = $productData['generic_name'];
                } elseif (isset($productData['brands']) && !empty($productData['brands'])) {
                    $shortDescription = "Marque : " . $productData['brands'];
                }

                try {
                    $product = Product::create([
                        'name'              => $productName,
                        'short_description' => $shortDescription,
                        'description'       => $description,
                        'price'             => $price,
                        'price_promo'       => $price * 0.9, // 10% de réduction
                        'image'             => $productImage,
                        'rating'            => rand(10, 50) / 10,
                        'category_id'       => $category->id,
                        'user_id'           => $merchants->random()->id,
                        'unity'             => 'pcs',
                        'stock'             => rand(10, 100),
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);

                    echo "    ✅ Produit créé : {$productName} (ID: {$product->id})\n";
                    $count++;
                } catch (\Exception $e) {
                    echo "    ❌ Erreur lors de la création du produit : " . $e->getMessage() . "\n";
                    $skipped++;
                }

                // Pause pour éviter de surcharger l'API
                sleep(1);
            }

            echo "✅ {$count} produits créés, {$skipped} ignorés pour {$category->name}\n";
        }

        echo "\n🎉 Importation réussie : catégories + produits OpenFoodFacts insérés.\n";
        echo "📊 Résumé :\n";
        echo "   - " . count($categoryModels) . " catégories créées\n";
        echo "   - " . Product::count() . " produits créés\n";
    }
}
