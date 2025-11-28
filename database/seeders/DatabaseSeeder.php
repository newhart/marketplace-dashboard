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
        // Mapping manuel enrichi et précis
        $this->categoryMapping = [
            // Produits laitiers, oeufs, Fromages
            'en:dairies' => ['parent' => 'Produits laitiers, oeufs, Fromages', 'sub' => 'Crémerie, oeufs, laits, boissons végétales'],
            'en:dairy-products' => ['parent' => 'Produits laitiers, oeufs, Fromages', 'sub' => 'Fromages'],
            'en:yogurts' => ['parent' => 'Produits laitiers, oeufs, Fromages', 'sub' => 'Yaourts, desserts'],
            'en:cheeses' => ['parent' => 'Produits laitiers, oeufs, Fromages', 'sub' => 'Fromages'],
            'en:milks' => ['parent' => 'Produits laitiers, oeufs, Fromages', 'sub' => 'Crémerie, oeufs, laits, boissons végétales'],
            'en:cream-butters' => ['parent' => 'Produits laitiers, oeufs, Fromages', 'sub' => 'Crémerie, oeufs, laits, boissons végétales'],
            'en:desserts' => ['parent' => 'Produits laitiers, oeufs, Fromages', 'sub' => 'Desserts, fromages enfant'],
            
            // Boucherie, volaille, poissonerie
            'en:meat' => ['parent' => 'Boucherie, volaille, poissonerie', 'sub' => 'Boucherie'],
            'en:poultry' => ['parent' => 'Boucherie, volaille, poissonerie', 'sub' => 'Volaille, lapin'],
            'en:fish' => ['parent' => 'Boucherie, volaille, poissonerie', 'sub' => 'Poissons, crustacés'],
            'en:seafood' => ['parent' => 'Boucherie, volaille, poissonerie', 'sub' => 'Poissons, crustacés'],
            'en:seafood-products' => ['parent' => 'Boucherie, volaille, poissonerie', 'sub' => 'Traiteur de la mer'],
            'en:fish-products' => ['parent' => 'Boucherie, volaille, poissonerie', 'sub' => 'Traiteur de la mer'],
            'en:beef' => ['parent' => 'Boucherie, volaille, poissonerie', 'sub' => 'Boucherie'],
            'en:pork' => ['parent' => 'Boucherie, volaille, poissonerie', 'sub' => 'Boucherie'],
            'en:chicken' => ['parent' => 'Boucherie, volaille, poissonerie', 'sub' => 'Volaille, lapin'],
            'en:nuggets' => ['parent' => 'Boucherie, volaille, poissonerie', 'sub' => 'Pané, nuggets, grignottes'],
            
            // Pain, pâtisserie
            'en:breads' => ['parent' => 'Pain, pâtisserie', 'sub' => 'Pains, pâtisseries fraîches'],
            'en:pastries' => ['parent' => 'Pain, pâtisserie', 'sub' => 'Pains, pâtisseries fraîches'],
            'en:cakes' => ['parent' => 'Pain, pâtisserie', 'sub' => 'Pains précuits, pâtisseries surgelées'],
            'en:biscuits' => ['parent' => 'Pain, pâtisserie', 'sub' => 'Pains apéritif, taosts'],
            'en:cookies' => ['parent' => 'Pain, pâtisserie', 'sub' => 'Pains apéritif, taosts'],
            'en:viennoiseries' => ['parent' => 'Pain, pâtisserie', 'sub' => 'Brioches, pains au lait emballés'],
            'en:croissants' => ['parent' => 'Pain, pâtisserie', 'sub' => 'Brioches, pains au lait emballés'],
            'en:burger-buns' => ['parent' => 'Pain, pâtisserie', 'sub' => 'Pains burger, pain du monde'],
            'en:sliced-breads' => ['parent' => 'Pain, pâtisserie', 'sub' => 'Pains de mie'],
            
            // Epicerie sucrée
            'en:coffees' => ['parent' => 'Epicerie sucrée', 'sub' => 'Cafés'],
            'en:teas' => ['parent' => 'Epicerie sucrée', 'sub' => 'Thés, infusions, chocolat en poudre'],
            'en:chocolates' => ['parent' => 'Epicerie sucrée', 'sub' => 'Chocolats'],
            'en:candies' => ['parent' => 'Epicerie sucrée', 'sub' => 'Bonbons, confiseries'],
            'en:breakfast-cereals' => ['parent' => 'Epicerie sucrée', 'sub' => 'Petit déjeuner'],
            'en:sugars' => ['parent' => 'Epicerie sucrée', 'sub' => 'Sucres, farine, aides à la patisseries'],
            'en:confectionery' => ['parent' => 'Epicerie sucrée', 'sub' => 'Bonbons, confiseries'],
            'en:biscuits-and-cakes' => ['parent' => 'Epicerie sucrée', 'sub' => 'Gâteaux moelleux'],
            'en:jam-honey' => ['parent' => 'Epicerie sucrée', 'sub' => 'Compotes, crémes dessert'],
            'en:chocolate-spreads' => ['parent' => 'Epicerie sucrée', 'sub' => 'Chocolats'],
            
            // Eaux, jus, soda, thés glacés
            'en:waters' => ['parent' => 'Eaux, jus, soda, thés glacés', 'sub' => 'Eaux'],
            'en:juices' => ['parent' => 'Eaux, jus, soda, thés glacés', 'sub' => 'Jus de fruits, jus de légumes'],
            'en:sodas' => ['parent' => 'Eaux, jus, soda, thés glacés', 'sub' => 'Colas, boissons gazeuses, énergisantes'],
            'en:soft-drinks' => ['parent' => 'Eaux, jus, soda, thés glacés', 'sub' => 'Colas, boissons gazeuses, énergisantes'],
            'en:energy-drinks' => ['parent' => 'Eaux, jus, soda, thés glacés', 'sub' => 'Colas, boissons gazeuses, énergisantes'],
            'en:iced-teas' => ['parent' => 'Eaux, jus, soda, thés glacés', 'sub' => 'Thés, boissons plates aux fruits'],
            'en:syrups' => ['parent' => 'Eaux, jus, soda, thés glacés', 'sub' => 'Sirops, concentrés'],
            'en:plant-based-milks' => ['parent' => 'Eaux, jus, soda, thés glacés', 'sub' => 'Lait, boissons végétales'],
            'en:non-alcoholic-beers' => ['parent' => 'Eaux, jus, soda, thés glacés', 'sub' => 'Bières, vins et apéritifs sans alcool'],
            
            // Vins, bières, alcools
            'en:beers' => ['parent' => 'Vins, bières, alcools', 'sub' => 'Bières, fûts, cidres'],
            'en:wines' => ['parent' => 'Vins, bières, alcools', 'sub' => 'Vins'],
            'en:spirits' => ['parent' => 'Vins, bières, alcools', 'sub' => 'Apéritifs, spiritueux'],
            'en:champagnes' => ['parent' => 'Vins, bières, alcools', 'sub' => 'Champagnes, vins effervescents'],
            'en:cocktails' => ['parent' => 'Vins, bières, alcools', 'sub' => 'Cocktails à faire soi-même'],
            'en:ciders' => ['parent' => 'Vins, bières, alcools', 'sub' => 'Bières, fûts, cidres'],
            'en:aperitifs' => ['parent' => 'Vins, bières, alcools', 'sub' => 'Apéritifs, spiritueux'],
            
            // Fruits et légumes
            'en:fruits' => ['parent' => 'Fruits, légumes', 'sub' => 'Fruits frais'],
            'en:vegetables' => ['parent' => 'Fruits, légumes', 'sub' => 'Légumes'],
            'en:dried-fruits' => ['parent' => 'Fruits, légumes', 'sub' => 'Fruits secs, graines'],
            'en:frozen-fruits' => ['parent' => 'Fruits, légumes', 'sub' => 'Fruits secs, graines'],
            'en:frozen-vegetables' => ['parent' => 'Fruits, légumes', 'sub' => 'Légumes'],
            'en:organic-fruits' => ['parent' => 'Fruits, légumes', 'sub' => 'Fruits, légumes bio'],
            'en:organic-vegetables' => ['parent' => 'Fruits, légumes', 'sub' => 'Fruits, légumes bio'],
            'en:salads' => ['parent' => 'Fruits, légumes', 'sub' => 'Légumes prêts à consommer'],
            'n:fresh-juices' => ['parent' => 'Fruits, légumes', 'sub' => 'Jus de fruits frais'],
            'en:plants-flowers' => ['parent' => 'Fruits, légumes', 'sub' => 'Fleurs, plantes'],
            
            // Surgelés
            'en:frozen-foods' => ['parent' => 'Surgelés', 'sub' => 'Pizzas'],
            'en:frozen-vegetables' => ['parent' => 'Surgelés', 'sub' => 'Légumes, fruits'],
            'en:frozen-meals' => ['parent' => 'Surgelés', 'sub' => 'Apéritifs, plats cuisinés, produits du monde'],
            'en:ice-creams' => ['parent' => 'Surgelés', 'sub' => 'Glaces'],
            'en:frozen-pizzas' => ['parent' => 'Surgelés', 'sub' => 'Pizzas'],
            'en:frozen-french-fries' => ['parent' => 'Surgelés', 'sub' => 'Frites, prommes de terre'],
            'en:frozen-meat' => ['parent' => 'Surgelés', 'sub' => 'Viandes'],
            'en:frozen-fish' => ['parent' => 'Surgelés', 'sub' => 'Poissons, fruits de mer'],
            'en:frozen-desserts' => ['parent' => 'Surgelés', 'sub' => 'Pâtisseries, viennoiseries'],
            
            // Epicerie salée
            'en:pasta' => ['parent' => 'Epicerie salée', 'sub' => 'Pâtes'],
            'en:rice' => ['parent' => 'Epicerie salée', 'sub' => 'Riz, semoules, légumes secs'],
            'en:canned-foods' => ['parent' => 'Epicerie salée', 'sub' => 'Conserves, soupes'],
            'en:sauces' => ['parent' => 'Epicerie salée', 'sub' => 'Sauces, huiles, aides culinaires'],
            'en:oils' => ['parent' => 'Epicerie salée', 'sub' => 'Sauces, huiles, aides culinaires'],
            'en:vinegars' => ['parent' => 'Epicerie salée', 'sub' => 'Sauces, huiles, aides culinaires'],
            'en:soups' => ['parent' => 'Epicerie salée', 'sub' => 'Conserves, soupes'],
            'en:legumes' => ['parent' => 'Epicerie salée', 'sub' => 'Riz, semoules, légumes secs'],
            'en:spices' => ['parent' => 'Epicerie salée', 'sub' => 'Sauces, huiles, aides culinaires'],
            'en:salt' => ['parent' => 'Epicerie salée', 'sub' => 'Sauces, huiles, aides culinaires'],
            'en:appetizers' => ['parent' => 'Epicerie salée', 'sub' => 'Apéritif'],
            'n:ready-meals' => ['parent' => 'Epicerie salée', 'sub' => 'Plats cuisinés'],
            
            // Hygiène et beauté
            'en:soaps' => ['parent' => 'Hygiène, beauté', 'sub' => 'Soins du corps'],
            'en:shampoos' => ['parent' => 'Hygiène, beauté', 'sub' => 'Soins des cheveux'],
            'en:toothpastes' => ['parent' => 'Hygiène, beauté', 'sub' => 'Hygiène dentaire'],
            'n:deodorants' => ['parent' => 'Hygiène, beauté', 'sub' => 'Soins du corps'],
            'n:creams-lotions' => ['parent' => 'Hygiène, beauté', 'sub' => 'Soins du corps'],
            'n:makeup' => ['parent' => 'Hygiène, beauté', 'sub' => 'Soins du visages, maquillage'],
            'n:mens-care' => ['parent' => 'Hygiène, beauté', 'sub' => 'Soins homme'],
            'n:tissues-paper' => ['parent' => 'Hygiène, beauté', 'sub' => 'Mouchoirs, papier toilette, cotons'],
            'n:feminine-hygiene' => ['parent' => 'Hygiène, beauté', 'sub' => 'Protections hygièniques'],
            'n:pharmacy' => ['parent' => 'Hygiène, beauté', 'sub' => 'Petite parapharmacie'],
            
            // Tout pour bébé
            'en:baby-foods' => ['parent' => 'Tout pour bébé', 'sub' => 'Repas de bébé'],
            'en:infant-formulas' => ['parent' => 'Tout pour bébé', 'sub' => 'Laits, petits-déjeuners de bébé'],
            'en:baby-milks' => ['parent' => 'Tout pour bébé', 'sub' => 'Laits, petits-déjeuners de bébé'],
            'en:diapers' => ['parent' => 'Tout pour bébé', 'sub' => 'Couches, toilette de bébé'],
            'en:baby-care' => ['parent' => 'Tout pour bébé', 'sub' => 'Puériculture'],
            'en:baby-accessories' => ['parent' => 'Tout pour bébé', 'sub' => 'Vêtements, chaussures bébé'],
            'en:baby-snacks' => ['parent' => 'Tout pour bébé', 'sub' => 'Desserts, goûters, jus'],
            
            // Bio et nutrition
            'en:organic-foods' => ['parent' => 'Bio et nutrition', 'sub' => 'Bio et écologique'],
            'en:vegan-foods' => ['parent' => 'Bio et nutrition', 'sub' => 'Végétarien, vegan'],
            'en:vegetarian-foods' => ['parent' => 'Bio et nutrition', 'sub' => 'Végétarien, vegan'],
            'en:gluten-free-foods' => ['parent' => 'Bio et nutrition', 'sub' => 'Sans gluten'],
            'en:lactose-free-foods' => ['parent' => 'Bio et nutrition', 'sub' => 'Sans lactose'],
            'en:sugar-free-foods' => ['parent' => 'Bio et nutrition', 'sub' => 'Sans sucres, sans sucres ajoutés'],
            'en:sports-nutrition' => ['parent' => 'Bio et nutrition', 'sub' => 'Nutrition sportive'],
            'n:food-supplements' => ['parent' => 'Bio et nutrition', 'sub' => 'Compléments alimentaires'],
            'en:light-products' => ['parent' => 'Bio et nutrition', 'sub' => 'Produits allégés, bien-être'],
            
            // Charcuterie, traiteur
            'en:charcuterie' => ['parent' => 'Charcuterie, traiteur', 'sub' => 'Charcuterie'],
            'en:deli-meats' => ['parent' => 'Charcuterie, traiteur', 'sub' => 'Charcuterie'],
            'en:catering' => ['parent' => 'Charcuterie, traiteur', 'sub' => 'Traiteur'],
            'en:ready-meals' => ['parent' => 'Charcuterie, traiteur', 'sub' => 'Plats suicinés, snacking, salade'],
            'en:salads' => ['parent' => 'Charcuterie, traiteur', 'sub' => 'Plats suicinés, snacking, salade'],
            'en:vegetarian-catering' => ['parent' => 'Charcuterie, traiteur', 'sub' => 'Traiteur végétal'],
            'en:aperitifs' => ['parent' => 'Charcuterie, traiteur', 'sub' => 'Apéritifs, tartinables'],
            'en:spreads' => ['parent' => 'Charcuterie, traiteur', 'sub' => 'Apéritifs, tartinables'],
            
            // Produits du monde
            'en:asian-foods' => ['parent' => 'Produits du monde', 'sub' => 'Asie'],
            'en:italian-foods' => ['parent' => 'Produits du monde', 'sub' => 'Italie'],
            'en:mexican-foods' => ['parent' => 'Produits du monde', 'sub' => 'Tex Mex'],
            'en:mediterranean-foods' => ['parent' => 'Produits du monde', 'sub' => 'Italie'],
            'en:halal-foods' => ['parent' => 'Produits du monde', 'sub' => 'Moyen-Orient, Halal'],
            'en:ethnic-foods' => ['parent' => 'Produits du monde', 'sub' => 'Autres produits du monde'],
            
            // Entretien, maison
            'n:cleaning-products' => ['parent' => 'Entretien, accessoires de la maison', 'sub' => 'Produits d\'entretien'],
            'n:household-paper' => ['parent' => 'Entretien, accessoires de la maison', 'sub' => 'Papier toilette, essuie-tout, mouchoir'],
            'n:laundry-care' => ['parent' => 'Entretien, accessoires de la maison', 'sub' => 'Soin du linge'],
            'n:dishwashing' => ['parent' => 'Entretien, accessoires de la maison', 'sub' => 'Vaisselle, entretien lave vaisselle'],
            'n:trash-bags' => ['parent' => 'Entretien, accessoires de la maison', 'sub' => 'Sacs poubelles, accessoires ménagers'],
            'n:kitchen-accessories' => ['parent' => 'Entretien, accessoires de la maison', 'sub' => 'Accessoires cuisine, maison'],
            'n:stationery' => ['parent' => 'Entretien, accessoires de la maison', 'sub' => 'Fournitures scolaires, bureau, loisirs'],
            
            // Animalerie
            'en:pet-foods' => ['parent' => 'Animalerie', 'sub' => 'Accessoires animalerie'],
            'en:dog-foods' => ['parent' => 'Animalerie', 'sub' => 'Chien'],
            'en:cat-foods' => ['parent' => 'Animalerie', 'sub' => 'Chat'],
            'en:pet-accessories' => ['parent' => 'Animalerie', 'sub' => 'Accessoires animalerie'],
            
            // Jouets, jeux
            'en:toys' => ['parent' => 'Jouets, jeux vidéo, livres', 'sub' => 'Jeux, jouets'],
            'en:video-games' => ['parent' => 'Jouets, jeux vidéo, livres', 'sub' => 'Jeux vidéo'],
            'en:books' => ['parent' => 'Jouets, jeux vidéo, livres', 'sub' => 'Livres'],
            'en:sports-equipment' => ['parent' => 'Jouets, jeux vidéo, livres', 'sub' => 'Sport, plein air'],
            'n:creative-hobbies' => ['parent' => 'Jouets, jeux vidéo, livres', 'sub' => 'Loisirs créatifs, art'],
            'n:costumes' => ['parent' => 'Jouets, jeux vidéo, livres', 'sub' => 'Déguisements'],
            'n:gift-boxes' => ['parent' => 'Jouets, jeux vidéo, livres', 'sub' => 'Coffrets cadeaux'],
        ];
    }

    /**
     * Récupérer et importer les produits d'une catégorie
     */
    private function importProductsFromCategory(string $slug, string $categoryName, $merchants, $allUsers): void
    {
        // Récupérer toutes les catégories parent uniquement
        $availableCategories = Category::whereNull('parent_id')->get();
        
        if ($availableCategories->isEmpty()) {
            echo "  ✗ Aucune catégorie disponible\n";
            return;
        }

        $page = 1;
        $pageSize = 100;
        $remainingProducts = $this->productsToImport;
        $maxRetries = 3;
        $retryDelay = 2; // secondes

        while ($this->productsCreated < $this->productsToImport) {
            $response = null;
            $attempt = 0;
            
            // Retry strategy pour les timeouts
            while ($attempt < $maxRetries && !$response) {
                try {
                    $response = Http::timeout(45)->get("https://world.openfoodfacts.org/category/{$slug}/{$page}.json");
                    
                    if (!$response->ok()) {
                        echo "  Erreur HTTP {$response->status()} pour la catégorie {$categoryName}\n";
                        $response = null;
                    }
                } catch (\Exception $e) {
                    $attempt++;
                    if ($attempt < $maxRetries) {
                        echo "  Tentative {$attempt}/{$maxRetries} échouée, nouvelle tentative dans {$retryDelay}s...\n";
                        sleep($retryDelay);
                        $retryDelay *= 2; // Exponential backoff
                    } else {
                        echo "  Erreur de connexion après {$maxRetries} tentatives pour {$categoryName}\n";
                        break 2; // Sortir de la boucle while principale
                    }
                }
            }
            
            if (!$response) {
                echo "  Impossible de récupérer les produits pour {$categoryName}\n";
                break;
            }

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
        $categories = array_map('trim', explode(',', $productCategories));
        
        // Mots-clés de correspondance précis avec les catégories PARENT
        $keywordMap = [
            // Produits laitiers, oeufs, Fromages
            'dairy|milk|cheese|yogurt|fromage|lait|yaourt|cream|butter|beurre|crème' => [
                'Produits laitiers, oeufs, Fromages'
            ],
            
            // Boucherie, volaille, poissonerie
            'meat|poultry|fish|seafood|viande|volaille|poisson|crustace|steak|beef|pork|chicken|turkey|salmon|tuna|cod' => [
                'Boucherie, volaille, poissonerie'
            ],
            
            // Pain, pâtisserie
            'bread|pastry|cake|biscuit|pain|patisserie|gateau|viennoiserie|croissant|baguette|brioche|toast|burger bun' => [
                'Pain, pâtisserie'
            ],
            
            // Epicerie sucrée
            'coffee|tea|chocolate|candy|sweet|sugar|cacao|bonbon|confiserie|cookie|gateau|dessert|sucrerie|cereals|cereal|granola|muesli|breakfast|petit-dejeuner' => [
                'Epicerie sucrée'
            ],
            
            // Eaux, jus, soda, thés glacés
            'water|juice|soda|drink|eau|jus|boisson|soft drink|nectar|sirop|thé glacé|ice tea|smoothie|milkshake' => [
                'Eaux, jus, soda, thés glacés'
            ],
            
            // Hygiène, beauté
            'soap|shampoo|toothpaste|hygiene|beauty|care|soins|cosmetic|makeup|dental|cream|lotion|deodorant|perfume' => [
                'Hygiène, beauté'
            ],
            
            // Tout pour bébé
            'baby|infant|bebe|toddler|diaper|formula|baby food|puériculture|couche|lait bébé|repas bébé' => [
                'Tout pour bébé'
            ],
            
            // Bio et nutrition
            'gluten-free|lactose-free|diet|sport|supplement|nutrition|protein|allergen-free|light|dietetic|complement|wellness|fitness|muscle|energy|vitamins|minerals|bio|organic|naturel|nature|plant-based|vegetable-based|vegan|vegetarian' => [
                'Bio et nutrition'
            ],
            
            // Fruits, légumes
            'fruit|vegetable|legume|fruit|bio|frais|sec|graine|jus frais|fleur|plante' => [
                'Fruits, légumes'
            ],
            
            // Charcuterie, traiteur
            'charcuterie|traiteur|salade|snacking|apéritif|tartinable|végétal|plats cuisinés' => [
                'Charcuterie, traiteur'
            ],
            
            // Surgelés
            'frozen|surgelé|pizza|glace|apéritif|plats cuisinés|légume|fruit|frite|pomme de terre|viande|poisson|fruit de mer|pâtisserie|viennoiserie' => [
                'Surgelés'
            ],
            
            // Epicerie salée
            'salt|salé|apéritif|sauce|huile|culinaire|conserve|soupe|pâte|riz|semoule|légume sec|plat cuisiné|épicerie du monde' => [
                'Epicerie salée'
            ],
            
            // Vins, bières, alcools
            'wine|beer|alcohol|vin|bière|cidre|apéritif|spiritueux|champagne|cocktail|sans alcool' => [
                'Vins, bières, alcools'
            ],
            
            // Entretien, accessoires de la maison
            'paper|toilet|tissue|towel|laundry|detergent|cleaning|trash|bag|accessory|kitchen|home|school|office|supply' => [
                'Entretien, accessoires de la maison'
            ],
            
            // Animalerie
            'pet|animal|cat|dog|chat|chien|bird|fish|rongeur|accessory' => [
                'Animalerie'
            ],
            
            // Jouets, jeux vidéo, livres
            'toy|game|video game|book|livre|sport|outdoor|déguisement|creative|art|coffret|cadeau|marque' => [
                'Jouets, jeux vidéo, livres'
            ],
            
            // Electroménager, cuisine
            'appliance|kitchen|electroménager|cuisine|aspirateur|cleaning|beauty|well-being|climatisation|heating|ironing|sewing|dryer' => [
                'Electroménager, cuisine'
            ],
            
            // Mode, bijoux, bagagerie
            'fashion|jewelry|bag|luggage|clothing|shoes|woman|man|child|baby|bijoux|vêtement|chaussure|maroquinerie' => [
                'Mode, bijoux, bagagerie'
            ],
            
            // Billetterie, traiteur, voyage
            'ticket|travel|gift card|voyage|photo|traiteur|télécom|coffret|cadeau' => [
                'Billetterie, traiteur, voyage'
            ],
            
            // Jardin, auto, brico
            'garden|diy|auto|motorcycle|bricolage|accessoire' => [
                'Jardin, auto, brico'
            ],
            
            // High-tech, téléphonie
            'high-tech|phone|tv|video projector|home cinema|computer|audio|hifi|connected object|urban mobility|photo|camera|reconditionned' => [
                'High-tech, téléphonie'
            ],
            
            // Meuble, linge de maison
            'furniture|sofa|linen|decoration|bedding|literie|meuble|linge de maison' => [
                'Meuble, linge de maison'
            ],
            
            // Produits du monde
            'asian|chinese|japanese|thai|indian|italian|mexican|halal|kosher|world cuisine|ethnic|spicy|curry|sushi|pizza|pasta|tacos' => [
                'Produits du monde'
            ],
            
            // Entretien, maison
            'cleaning|detergent|soap|household|home|paper|tissue|trash bag|kitchen|laundry|dish|vacuum|maintenance|accessories' => [
                'Papier toilette, essuie-tout, mouchoir',
                'Soin du linge', 
                'Vaisselle, entretien lave vaisselle', 
                'Produits d\'entretien', 
                'Sacs poubelles, accessoires ménagers',
                'Accessoires cuisine, maison',
                'Fournitures scolaires, bureau, loisirs'
            ],
            
            // Animalerie
            'pet|dog|cat|animal|pet food|chien|chat|animalerie|bird|fish|hamster|accessories|litter|toy' => [
                'Chat', 
                'Chien', 
                'Rongeurs, oiseaux, poissons', 
                'Accessoires animalerie',
                'Produits de nos régions'
            ],
            
            // Jouets, jeux
            'toy|game|video game|book|sport|outdoor|hobby|creative|art|gift|costume|lego|puzzle|board game|console|playstation|xbox' => [
                'Jeux, jouets', 
                'Jeux vidéo', 
                'Sport, plein air', 
                'Livres', 
                'Déguisements', 
                'Loisirs créatifs, art',
                'Coffrets cadeaux',
                'Marques jouets, jeux vidéo'
            ],
        ];
        
        // Chercher une correspondance dans les catégories du produit
        foreach ($categories as $productCat) {
            $productCatLower = strtolower($productCat);
            
            // Priorité 1: Correspondances exactes de mots-clés spécifiques
            $specificMatches = [
                // CATÉGORIES IMPORTANTES - PRIORITÉ MAXIMALE
                'plant-based foods and beverages' => ['Bio et nutrition'],
                'plant-based foods' => ['Bio et nutrition'],
                'fruits-and-vegetables-based-foods' => ['Fruits, légumes'],
                'dairy-substitutes' => ['Produits laitiers, oeufs, Fromages'],
                
                // PETIT DÉJEUNER
                'petit-dejeuner' => ['Epicerie sucrée'],
                'breakfasts' => ['Epicerie sucrée'],
                'breakfast-cereals' => ['Epicerie sucrée'],
                'cereals-and-potatoes' => ['Epicerie salée'],
                'cereals-and-their-products' => ['Epicerie salée'],
                'mixed nuts' => ['Fruits, légumes'],
                'nuts-and-their-products' => ['Fruits, légumes'],
                'nuts' => ['Fruits, légumes'],
                'seeds' => ['Fruits, légumes'],
                'cheeses' => ['Produits laitiers, oeufs, Fromages'],
                'cow-cheeses' => ['Produits laitiers, oeufs, Fromages'],
                'dairies' => ['Produits laitiers, oeufs, Fromages'],
                'dairy-desserts' => ['Produits laitiers, oeufs, Fromages'],
                'dairy-substitutes' => ['Produits laitiers, oeufs, Fromages'],
                'milks' => ['Produits laitiers, oeufs, Fromages'],
                'yogurts' => ['Produits laitiers, oeufs, Fromages'],
                'fermented-milk-products' => ['Produits laitiers, oeufs, Fromages'],
                'wines' => ['Vins, bières, alcools'],
                'alcoholic-beverages' => ['Vins, bières, alcools'],
                'beers' => ['Vins, bières, alcools'],
                'waters' => ['Eaux, jus, soda, thés glacés'],
                'beverages' => ['Eaux, jus, soda, thés glacés'],
                'plant-based-beverages' => ['Eaux, jus, soda, thés glacés'],
                'fruit-based-beverages' => ['Eaux, jus, soda, thés glacés'],
                'hot-beverages' => ['Epicerie sucrée'],
                'carbonated-drinks' => ['Eaux, jus, soda, thés glacés'],
                'coffees' => ['Epicerie sucrée'],
                'teas' => ['Epicerie sucrée'],
                'chocolates' => ['Epicerie sucrée'],
                'cocoa-and-its-products' => ['Epicerie sucrée'],
                'chocolate-candies' => ['Epicerie sucrée'],
                'confectioneries' => ['Epicerie sucrée'],
                'candies' => ['Epicerie sucrée'],
                'sweet-snacks' => ['Epicerie sucrée'],
                'salty-snacks' => ['Charcuterie, traiteur'],
                'snacks' => ['Charcuterie, traiteur'],
                'biscuits-and-cakes' => ['Epicerie sucrée'],
                'biscuits-and-crackers' => ['Pain, pâtisserie'],
                'biscuits' => ['Pain, pâtisserie'],
                'cakes' => ['Pain, pâtisserie'],
                'breads' => ['Pain, pâtisserie'],
                'pastas' => ['Epicerie salée'],
                'spreads' => ['Charcuterie, traiteur'],
                'sweet-spreads' => ['Epicerie sucrée'],
                'salted-spreads' => ['Charcuterie, traiteur'],
                'plant-based-spreads' => ['Charcuterie, traiteur'],
                'sauces' => ['Epicerie salée'],
                'condiments' => ['Epicerie salée'],
                'vegetable-oils' => ['Epicerie salée'],
                'fats' => ['Epicerie salée'],
                'vegetable-fats' => ['Epicerie salée'],
                'olive-tree-products' => ['Epicerie salée'],
                'honeys' => ['Epicerie sucrée'],
                'bee-products' => ['Epicerie sucrée'],
                'jams' => ['Epicerie sucrée'],
                'sweeteners' => ['Epicerie sucrée'],
                'sugars' => ['Epicerie sucrée'],
                'soups' => ['Epicerie salée'],
                'canned-foods' => ['Epicerie salée'],
                'canned-vegetables' => ['Epicerie salée'],
                'canned-plant-based-foods' => ['Epicerie salée'],
                'baby foods' => ['Tout pour bébé'],
                'diapers' => ['Tout pour bébé'],
                'soaps' => ['Hygiène, beauté'],
                'shampoos' => ['Hygiène, beauté'],
                'toothpastes' => ['Hygiène, beauté'],
                'dried-plant-based-foods' => ['Fruits, légumes'],
                'fruits-based-foods' => ['Fruits, légumes'],
                'vegetables-based-foods' => ['Fruits, légumes'],
                'vegetables' => ['Fruits, légumes'],
                'fruits' => ['Fruits, légumes'],
                'legumes' => ['Epicerie salée'],
                'legumes-and-their-products' => ['Epicerie salée'],
                'meats-and-their-products' => ['Boucherie, volaille, poissonerie'],
                'meats' => ['Boucherie, volaille, poissonerie'],
                'prepared-meats' => ['Charcuterie, traiteur'],
                'hams' => ['Charcuterie, traiteur'],
                'sausages' => ['Charcuterie, traiteur'],
                'chicken-and-its-products' => ['Boucherie, volaille, poissonerie'],
                'chickens' => ['Boucherie, volaille, poissonerie'],
                'poultries' => ['Boucherie, volaille, poissonerie'],
                'fishes-and-their-products' => ['Boucherie, volaille, poissonerie'],
                'fishes' => ['Boucherie, volaille, poissonerie'],
                'seafood' => ['Boucherie, volaille, poissonerie'],
                'fatty-fishes' => ['Boucherie, volaille, poissonerie'],
                'frozen-foods' => ['Surgelés'],
                'frozen-desserts' => ['Surgelés'],
                'ice-creams-and-sorbets' => ['Surgelés'],
                'desserts' => ['Epicerie sucrée'],
                'meals' => ['Charcuterie, traiteur'],
                'meals-with-meat' => ['Charcuterie, traiteur'],
                'appetizers' => ['Charcuterie, traiteur'],
                'crisps' => ['Charcuterie, traiteur'],
                'chips-and-fries' => ['Surgelés'],
                'pizzas-pies-and-quiches' => ['Surgelés'],
                'dietary-supplements' => ['Bio et nutrition'],
                'fermented-foods' => ['Bio et nutrition'],
                'farming-products' => ['Fruits, légumes'],
                'groceries' => ['Epicerie salée'],
                'dried-products' => ['Fruits, légumes'],
                'fruit-and-vegetable-preserves' => ['Epicerie sucrée'],
                'fruit-juices' => ['Eaux, jus, soda, thés glacés'],
                'juices-and-nectars' => ['Eaux, jus, soda, thés glacés'],
                'beverages-and-beverages-preparations' => ['Eaux, jus, soda, thés glacés'],
            ];
            
            foreach ($specificMatches as $specificTerm => $targetCategories) {
                if (strpos($productCatLower, $specificTerm) !== false) {
                    foreach ($targetCategories as $targetName) {
                        $category = $availableCategories->first(function ($cat) use ($targetName) {
                            return strtolower($cat->name) === strtolower($targetName);
                        });
                        
                        if ($category) {
                            echo "    ✓ Correspondance SPÉCIFIQUE trouvée: '{$productCat}' -> '{$category->name}'\n";
                            return $category;
                        }
                    }
                }
            }
            
            // Priorité 2: Mots-clés généraux par ordre de précision
            foreach ($keywordMap as $pattern => $targetCategories) {
                if (preg_match('/' . $pattern . '/i', $productCatLower)) {
                    // Trouver une catégorie parent correspondante
                    foreach ($targetCategories as $targetName) {
                        $category = $availableCategories->first(function ($cat) use ($targetName) {
                            return strtolower($cat->name) === strtolower($targetName);
                        });
                        
                        if ($category) {
                            echo "    ✓ Correspondance trouvée: '{$productCat}' -> '{$category->name}'\n";
                            return $category;
                        }
                    }
                }
            }
        }
        
        // Si aucune correspondance, retourner une catégorie aléatoire
        $randomCategory = $availableCategories->random();
        echo "    ⚠ Aucune correspondance pour '{$productCategories}', catégorie aléatoire: '{$randomCategory->name}'\n";
        return $randomCategory;
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
