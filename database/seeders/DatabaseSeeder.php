<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class DatabaseSeeder extends Seeder
{
    private $categoryMapping = [];
    private $productsCreated = 0;
    private $productsToImport = 5000;

    public function run(): void
    {
        // Créer un super admin en premier
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@marketplace.test',
            'password' => bcrypt('password123#'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        echo "\n✓ Super Admin créé : {$superAdmin->email}\n\n";

        // Appeler le CategorySeeder avec le super admin
        $this->call([CategorySeeder::class]);
        
        // Associer les catégories au super admin
        Category::whereNull('parent_id')->update(['user_id' => $superAdmin->id]);
        Category::whereNotNull('parent_id')->update(['user_id' => $superAdmin->id]);

        echo "✓ Toutes les catégories sont associées au Super Admin\n\n";

        // Crée des utilisateurs
        User::factory()->count(10)->create();

        $allUsers = User::all();
        $merchants = $allUsers->where('role', 'merchant');
        if ($merchants->isEmpty()) {
            $merchants = $allUsers;
        }

        // Charger les catégories créées par CategorySeeder
        $this->buildCategoryMapping();

        echo "\n=== IMPORTATION DE 5000 PRODUITS DEPUIS OPENFOODFACTS ===\n\n";
        
        // Récupérer les catégories d'OpenFoodFacts
        $response = Http::timeout(30)->get("https://world.openfoodfacts.org/categories.json");

        if (!$response->ok()) {
            echo "Erreur lors de la récupération des catégories depuis OpenFoodFacts\n";
            return;
        }

        $data = $response->json();
        $tags = $data['tags'] ?? [];

        echo "Total de " . count($tags) . " catégories disponibles sur OpenFoodFacts\n";
        echo "Objectif : Importer " . $this->productsToImport . " produits\n\n";

        // Boucler sur les catégories pour récupérer les produits
        $categoryCount = 0;
        foreach ($tags as $tag) {
            if ($this->productsCreated >= $this->productsToImport) {
                echo "\n✓ Objectif atteint : {$this->productsCreated} produits importés\n";
                break;
            }

            $slug = $tag['id'];
            $name = $tag['name'];
            $categoryCount++;

            echo "[{$categoryCount}] Catégorie OpenFoodFacts: {$name} (slug: {$slug}) - ";
            echo "Produits à importer : " . ($this->productsToImport - $this->productsCreated) . "\n";

            // Récupérer les produits de cette catégorie
            $this->importProductsFromCategory($slug, $name, $merchants, $allUsers);

            // Pause pour ne pas surcharger l'API
            sleep(1);
        }

        echo "\n=== RÉSUMÉ ===\n";
        echo "Produits créés : {$this->productsCreated} / {$this->productsToImport}\n";
        echo "Catégories utilisées : " . count($this->categoryMapping) . "\n";
    }

    /**
     * Construire un mappage intelligent des catégories OpenFoodFacts vers nos catégories
     */
    private function buildCategoryMapping(): void
    {
        // Récupérer toutes nos catégories
        $ourCategories = Category::with('parent')->get();

        // Mapping manuel intelligent
        $this->categoryMapping = [
            // Produits laitiers
            'en:dairies' => ['parent' => 'Produits laitiers, oeufs, Fromages', 'sub' => 'Crémerie, oeufs, laits, boissons végétales'],
            'en:dairy-products' => ['parent' => 'Produits laitiers, oeufs, Fromages', 'sub' => 'Fromages'],
            'en:yogurts' => ['parent' => 'Produits laitiers, oeufs, Fromages', 'sub' => 'Yaourts, desserts'],
            'en:cheeses' => ['parent' => 'Produits laitiers, oeufs, Fromages', 'sub' => 'Fromages'],
            'en:milks' => ['parent' => 'Produits laitiers, oeufs, Fromages', 'sub' => 'Crémerie, oeufs, laits, boissons végétales'],
            
            // Viande, poisson
            'en:meat' => ['parent' => 'Boucherie, volaille, poissonerie', 'sub' => 'Boucherie'],
            'en:poultry' => ['parent' => 'Boucherie, volaille, poissonerie', 'sub' => 'Volaille, lapin'],
            'en:fish' => ['parent' => 'Boucherie, volaille, poissonerie', 'sub' => 'Poissons, crustacés'],
            'en:seafood' => ['parent' => 'Boucherie, volaille, poissonerie', 'sub' => 'Poissons, crustacés'],
            
            // Pain et pâtisserie
            'en:breads' => ['parent' => 'Pain, pâtisserie', 'sub' => 'Pains, pâtisseries fraîches'],
            'en:pastries' => ['parent' => 'Pain, pâtisserie', 'sub' => 'Pains, pâtisseries fraîches'],
            'en:cakes' => ['parent' => 'Pain, pâtisserie', 'sub' => 'Pains précuits, pâtisseries surgelées'],
            'en:biscuits' => ['parent' => 'Pain, pâtisserie', 'sub' => 'Pains apéritif, taosts'],
            
            // Epicerie sucrée
            'en:coffees' => ['parent' => 'Epicerie sucrée', 'sub' => 'Cafés'],
            'en:teas' => ['parent' => 'Epicerie sucrée', 'sub' => 'Thés, infusions, chocolat en poudre'],
            'en:chocolates' => ['parent' => 'Epicerie sucrée', 'sub' => 'Chocolats'],
            'en:candies' => ['parent' => 'Epicerie sucrée', 'sub' => 'Bonbons, confiseries'],
            'en:breakfast-cereals' => ['parent' => 'Epicerie sucrée', 'sub' => 'Petit déjeuner'],
            'en:sugars' => ['parent' => 'Epicerie sucrée', 'sub' => 'Sucres, farine, aides à la patisseries'],
            
            // Boissons
            'en:waters' => ['parent' => 'Eaux, jus, soda, thés glacés', 'sub' => 'Eaux'],
            'en:juices' => ['parent' => 'Eaux, jus, soda, thés glacés', 'sub' => 'Jus de fruits, jus de légumes'],
            'en:sodas' => ['parent' => 'Eaux, jus, soda, thés glacés', 'sub' => 'Colas, boissons gazeuses, énergisantes'],
            'en:beers' => ['parent' => 'Vins, bières, alcools', 'sub' => 'Bières, fûts, cidres'],
            'en:wines' => ['parent' => 'Vins, bières, alcools', 'sub' => 'Vins'],
            'en:spirits' => ['parent' => 'Vins, bières, alcools', 'sub' => 'Apéritifs, spiritueux'],
            
            // Fruits et légumes
            'en:fruits' => ['parent' => 'Fruits, légumes', 'sub' => 'Fruits frais'],
            'en:vegetables' => ['parent' => 'Fruits, légumes', 'sub' => 'Légumes'],
            'en:dried-fruits' => ['parent' => 'Fruits, légumes', 'sub' => 'Fruits secs, graines'],
            
            // Surgelés
            'en:frozen-foods' => ['parent' => 'Surgelés', 'sub' => 'Pizzas'],
            'en:frozen-vegetables' => ['parent' => 'Surgelés', 'sub' => 'Légumes, fruits'],
            'en:frozen-meals' => ['parent' => 'Surgelés', 'sub' => 'Apéritifs, plats cuisinés, produits du monde'],
            'en:ice-creams' => ['parent' => 'Surgelés', 'sub' => 'Glaces'],
            
            // Epicerie salée
            'en:pasta' => ['parent' => 'Epicerie salée', 'sub' => 'Pâtes'],
            'en:rice' => ['parent' => 'Epicerie salée', 'sub' => 'Riz, semoules, légumes secs'],
            'en:canned-foods' => ['parent' => 'Epicerie salée', 'sub' => 'Conserves, soupes'],
            'en:sauces' => ['parent' => 'Epicerie salée', 'sub' => 'Sauces, huiles, aides culinaires'],
            
            // Hygiène et beauté
            'en:soaps' => ['parent' => 'Hygiène, beauté', 'sub' => 'Soins du corps'],
            'en:shampoos' => ['parent' => 'Hygiène, beauté', 'sub' => 'Soins des cheveux'],
            'en:toothpastes' => ['parent' => 'Hygiène, beauté', 'sub' => 'Hygiène dentaire'],
            
            // Bébé
            'en:baby-foods' => ['parent' => 'Tout pour bébé', 'sub' => 'Repas de bébé'],
            'en:infant-formulas' => ['parent' => 'Tout pour bébé', 'sub' => 'Laits, petits-déjeuners de bébé'],
            
            // Bio
            'en:organic-foods' => ['parent' => 'Bio et nutrition', 'sub' => 'Bio et écologique'],
            'en:vegetarian-foods' => ['parent' => 'Bio et nutrition', 'sub' => 'Végétarien, vegan'],
        ];
    }

    /**
     * Récupérer et importer les produits d'une catégorie
     */
    private function importProductsFromCategory(string $slug, string $categoryName, $merchants, $allUsers): void
    {
        // Récupérer toutes les sous-catégories disponibles
        $availableCategories = Category::whereNotNull('parent_id')->get();
        
        if ($availableCategories->isEmpty()) {
            echo "  ✗ Aucune catégorie disponible\n";
            return;
        }

        $page = 1;
        $remainingProducts = $this->productsToImport - $this->productsCreated;

        while ($this->productsCreated < $this->productsToImport && $remainingProducts > 0) {
            $response = Http::timeout(30)->get("https://world.openfoodfacts.org/category/{$slug}/{$page}.json");

            if (!$response->ok()) {
                echo "  ✗ Erreur lors de la récupération (page {$page})\n";
                break;
            }

            $data = $response->json();
            $products = $data['products'] ?? [];

            if (empty($products)) {
                echo "  ✓ Fin de catégorie atteinte\n";
                break;
            }

            $pageProducts = 0;
            foreach ($products as $item) {
                if ($this->productsCreated >= $this->productsToImport) {
                    break;
                }

                $productCode = $item['code'] ?? null;
                if (empty($productCode)) {
                    continue;
                }

                try {
                    // Extraire les catégories du produit OpenFoodFacts
                    $productCategories = $item['categories'] ?? $categoryName;
                    
                    // Trouver la meilleure catégorie correspondante
                    $targetCategory = $this->findBestCategoryFromProduct($productCategories, $availableCategories);
                    
                    $this->createProductFromOpenFoodFacts($productCode, $targetCategory, $merchants);
                    $pageProducts++;
                    $this->productsCreated++;
                } catch (\Exception $e) {
                    // Continuer silencieusement
                }

                // Pause pour ne pas surcharger
                if ($this->productsCreated % 10 === 0) {
                    echo "  Progression : {$this->productsCreated} produits importés\n";
                    sleep(1);
                }
            }

            if ($pageProducts === 0) {
                break;
            }

            $page++;
            $remainingProducts = $this->productsToImport - $this->productsCreated;
        }
    }

    /**
     * Trouver la meilleure catégorie correspondante à partir des catégories du produit
     */
    private function findBestCategoryFromProduct(string $productCategories, $availableCategories): ?Category
    {
        // Diviser les catégories du produit
        $categories = array_map('trim', explode(',', $productCategories));
        
        // Mots-clés de correspondance intelligente
        $keywordMap = [
            // Produits laitiers
            'dairy|milk|cheese|yogurt|fromage|lait|yaourt' => ['Crémerie, oeufs, laits, boissons végétales', 'Fromages', 'Yaourts, desserts'],
            
            // Viande et poisson
            'meat|poultry|fish|seafood|viande|volaille|poisson|crustace' => ['Boucherie', 'Volaille, lapin', 'Poissons, crustacés'],
            
            // Pain et pâtisserie
            'bread|pastry|cake|biscuit|pain|patisserie|gateau' => ['Pains, pâtisseries fraîches', 'Pains de mie', 'Pains précuits, pâtisseries surgelées'],
            
            // Boissons
            'water|juice|soda|beverage|drink|eau|jus|boisson' => ['Eaux', 'Jus de fruits, jus de légumes', 'Colas, boissons gazeuses, énergisantes'],
            
            // Chocolat et sucre
            'chocolate|candy|sweet|sugar|chocolat|bonbon|sucre' => ['Chocolats', 'Bonbons, confiseries', 'Sucres, farine, aides à la patisseries'],
            
            // Fruits et légumes
            'fruit|vegetable|produce|organic|bio|legume' => ['Fruits frais', 'Légumes', 'Fruits secs, graines'],
            
            // Surgelés
            'frozen|ice-cream|pizza|surgele|glace' => ['Pizzas', 'Légumes, fruits', 'Glaces'],
            
            // Hygiène et beauté
            'soap|shampoo|toothpaste|hygiene|beauty|care|soins' => ['Soins du corps', 'Soins des cheveux', 'Hygiène dentaire'],
            
            // Bébé
            'baby|infant|bebe' => ['Laits, petits-déjeuners de bébé', 'Repas de bébé'],
            
            // Épicerie
            'pasta|rice|cereal|breakfast|pates|riz' => ['Pâtes', 'Riz, semoules, légumes secs', 'Petit déjeuner'],
            
            // Alcool
            'beer|wine|spirits|alcohol|biere|vin' => ['Bières, fûts, cidres', 'Vins', 'Apéritifs, spiritueux'],
        ];
        
        // Chercher une correspondance dans les catégories du produit
        foreach ($categories as $productCat) {
            $productCatLower = strtolower($productCat);
            
            foreach ($keywordMap as $pattern => $targetCategories) {
                if (preg_match('/' . $pattern . '/i', $productCatLower)) {
                    // Trouver une sous-catégorie correspondante
                    foreach ($targetCategories as $targetName) {
                        $category = $availableCategories->first(function ($cat) use ($targetName) {
                            return strtolower($cat->name) === strtolower($targetName);
                        });
                        
                        if ($category) {
                            return $category;
                        }
                    }
                }
            }
        }
        
        // Si aucune correspondance, retourner une catégorie aléatoire
        return $availableCategories->random();
    }

    /**
     * Trouver la meilleure catégorie correspondante
     */
    private function findBestCategory(string $slug, string $categoryName): ?Category
    {
        if (isset($this->categoryMapping[$slug])) {
            $mapping = $this->categoryMapping[$slug];
            $category = Category::where('name', $mapping['sub'])
                ->whereHas('parent', function ($query) use ($mapping) {
                    $query->where('name', $mapping['parent']);
                })
                ->first();
            
            if ($category) {
                return $category;
            }
        }

        // Recherche par correspondance de mots-clés
        $keywords = explode('-', $slug);
        foreach ($keywords as $keyword) {
            $keyword = str_replace('en:', '', $keyword);
            $category = Category::whereNotNull('parent_id')
                ->where('name', 'LIKE', '%' . ucfirst($keyword) . '%')
                ->first();
            
            if ($category) {
                return $category;
            }
        }

        // Fallback : catégorie aléatoire
        return Category::whereNotNull('parent_id')->inRandomOrder()->first();
    }

    /**
     * Créer un produit à partir des données OpenFoodFacts
     */
    private function createProductFromOpenFoodFacts(string $productCode, Category $category, $merchants): void
    {
        $productDetailResponse = Http::timeout(30)->get("https://world.openfoodfacts.org/api/v0/product/{$productCode}.json");

        if (!$productDetailResponse->ok() || $productDetailResponse->json()['status'] !== 1) {
            return;
        }

        $productData = $productDetailResponse->json()['product'] ?? [];

        // Récupérer le nom du produit
        $productName = $productData['product_name'] 
            ?? $productData['product_name_fr'] 
            ?? $productData['product_name_en'] 
            ?? $productData['generic_name'] 
            ?? $productData['generic_name_fr']
            ?? ($productData['brands'] ? $productData['brands'] . ' - Product' : null);

        if (empty($productName)) {
            return;
        }

        // Récupérer l'image
        $productImage = $productData['image_url'] 
            ?? $productData['image_front_url'] 
            ?? $productData['image_small_url'] 
            ?? 'https://via.placeholder.com/400x400?text=' . urlencode($productName);

        // Récupérer le prix (généré aléatoirement)
        $price = rand(100, 10000) / 10;

        // Récupérer la description
        $description = $productData['ingredients_text'] 
            ?? $productData['generic_name'] 
            ?? 'Produit importé depuis OpenFoodFacts';

        // Récupérer la description courte
        $shortDescription = $productData['generic_name'] 
            ?? ($productData['brands'] ? "Marque : " . $productData['brands'] : substr($description, 0, 100));

        Product::create([
            'name'              => $productName,
            'short_description' => $shortDescription,
            'description'       => $description,
            'price'             => $price,
            'price_promo'       => $price * 0.9,
            'image'             => $productImage,
            'rating'            => rand(10, 50) / 10,
            'category_id'       => $category->id,
            'user_id'           => $merchants->random()->id,
            'unity'             => 'pcs',
            'stock'             => rand(10, 100),
        ]);
    }
}
