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
        $categories = array_map('trim', explode(',', $productCategories));
        
        // Mots-clés de correspondance précis avec VOS catégories
        $keywordMap = [
            // Produits laitiers, oeufs, Fromages
            'dairy|milk|cheese|yogurt|fromage|lait|yaourt|cream|butter|beurre|crème' => [
                'Crémerie, oeufs, laits, boissons végétales', 
                'Fromages', 
                'Yaourts, desserts',
                'Desserts, fromages enfant'
            ],
            
            // Boucherie, volaille, poissonerie
            'meat|poultry|fish|seafood|viande|volaille|poisson|crustace|steak|beef|pork|chicken|turkey|salmon|tuna|cod' => [
                'Boucherie', 
                'Volaille, lapin', 
                'Poissons, crustacés',
                'Traiteur de la mer',
                'Pané, nuggets, grignottes'
            ],
            
            // Pain, pâtisserie
            'bread|pastry|cake|biscuit|pain|patisserie|gateau|viennoiserie|croissant|baguette|brioche|toast|burger bun' => [
                'Pains, pâtisseries fraîches', 
                'Pains de mie', 
                'Pains burger, pain du monde',
                'Brioches, pains au lait emballés',
                'Pains apéritif, taosts',
                'Pains précuits, pâtisseries surgelées'
            ],
            
            // Petit déjeuner - CORRIGÉ
            'breakfast|petit-dejeuner|cereals|cereal|granola|muesli|porridge|oatmeal' => [
                'Petit déjeuner',
                'Cafés',
                'Thés, infusions, chocolat en poudre',
                'Biscuits',
                'Compotes, crémes dessert'
            ],
            
            // Epicerie sucrée - SÉPARÉ
            'coffee|tea|chocolate|candy|sweet|sugar|cacao|bonbon|confiserie|cookie|gateau|dessert|sucrerie' => [
                'Cafés', 
                'Thés, infusions, chocolat en poudre', 
                'Gâteaux moelleux',
                'Chocolats',
                'Bonbons, confiseries',
                'Compotes, crémes dessert',
                'Sucres, farine, aides à la patisseries'
            ],
            
            // Eaux, jus, soda, thés glacés - SANS BEVERAGES
            'water|juice|soda|drink|eau|jus|boisson|soft drink|nectar|sirop|thé glacé|ice tea|smoothie|milkshake' => [
                'Eaux', 
                'Jus de fruits, jus de légumes', 
                'Colas, boissons gazeuses, énergisantes',
                'Thés, boissons plates aux fruits',
                'Sirops, concentrés',
                'Bières, vins et apéritifs sans alcool',
                'Lait, boissons végétales'
            ],
            
            // Produits végétaux et bio - SÉPARÉ
            'plant-based|vegetable-based|vegan|vegetarian|bio|organic|naturel|nature|beverages|preparations|aliments|boissons|foods' => [
                'Bio et écologique',
                'Végétarien, vegan', 
                'Fruits, légumes bio',
                'Produits du monde'
            ],
            
            // Hygiène, beauté
            'soap|shampoo|toothpaste|hygiene|beauty|care|soins|cosmetic|makeup|dental|cream|lotion|deodorant|perfume' => [
                'Hygiène dentaire',
                'Soins du corps', 
                'Soins des cheveux', 
                'Soins du visages, maquillage',
                'Soins homme',
                'Mouchoirs, papier toilette, cotons',
                'Protections hygièniques',
                'Petite parapharmacie'
            ],
            
            // Tout pour bébé
            'baby|infant|bebe|toddler|diaper|formula|baby food|puériculture|couche|lait bébé|repas bébé' => [
                'Laits, petits-déjeuners de bébé',
                'Repas de bébé', 
                'Tout pour bébé', 
                'Desserts, goûters, jus',
                'Couches, toilette de bébé',
                'Puériculture',
                'Vêtements, chaussures bébé'
            ],
            
            // Bio et nutrition - SANS DOUBLONS
            'gluten-free|lactose-free|diet|sport|supplement|nutrition|protein|allergen-free|light|dietetic|complement|wellness|fitness|muscle|energy|vitamins|minerals' => [
                'Sans gluten', 
                'Sans lactose',
                'Sans sucres, sans sucres ajoutés',
                'Nutrition sportive',
                'Compléments alimentaires',
                'Produits allégés, bien-être'
            ],
            
            // Fruits, légumes - PLUS PRÉCIS
            'fruit|vegetable|produce|legume|apple|banana|orange|tomato|carrot|potato|onion|garlic|salad|greens|fresh|citrus|berry|strawberry|pear|peach|grape|melon|pineapple' => [
                'Fruits frais', 
                'Légumes', 
                'Fruits secs, graines', 
                'Légumes prêts à consommer',
                'Jus de fruits frais',
                'Fleurs, plantes'
            ],
            
            // Surgelés
            'frozen|ice-cream|pizza|surgele|glace|frozen food|congelé|surgelé|frozen vegetables|frozen meat|frozen fish' => [
                'Pizzas', 
                'Apéritifs, plats cuisinés, produits du monde',
                'Légumes, fruits', 
                'Frites, prommes de terre',
                'Viandes', 
                'Poissons, fruits de mer', 
                'Glaces',
                'Pâtisseries, viennoiseries'
            ],
            
            // Epicerie salée
            'pasta|rice|cereal|breakfast|pates|riz|noodle|couscous|quinoa|semoule|legumes secs|conserves|soup|sauce|oil|vinegar|salt|pepper|spice|herb' => [
                'Apéritif',
                'Sauces, huiles, aides culinaires', 
                'Conserves, soupes', 
                'Pâtes', 
                'Riz, semoules, légumes secs',
                'Plats cuisinés',
                'Epiceries du monde'
            ],
            
            // Vins, bières, alcools
            'beer|wine|spirits|alcohol|biere|vin|champagne|cocktail|whiskey|vodka|rum|gin|cider|aperitif|digestif' => [
                'Bières, fûts, cidres', 
                'Apéritifs, spiritueux', 
                'Vins', 
                'Champagnes, vins effervescents',
                'Cocktails à faire soi-même',
                'Bières, vins et apéritifs sans alcool'
            ],
            
            // Charcuterie, traiteur
            'charcuterie|saucisson|ham|bacon|salami|paté|rillettes|traiteur|plats cuisines|snacking|salade|ready meal|tapas|apéritif|tartinable' => [
                'Charcuterie',
                'Traiteur', 
                'Plats suicinés, snacking, salade', 
                'Traiteur végétal',
                'Apéritifs, tartinables'
            ],
            
            // Produits du monde
            'asian|chinese|japanese|thai|indian|italian|mexican|halal|kosher|world cuisine|ethnic|spicy|curry|sushi|pizza|pasta|tacos' => [
                'Asie',
                'Moyen-Orient, Halal', 
                'Tex Mex', 
                'Italie', 
                'Autres produits du monde'
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
                'plant-based foods and beverages' => ['Bio et écologique'],
                'plant-based foods' => ['Bio et écologique'],
                'fruits-and-vegetables-based-foods' => ['Fruits, légumes bio'],
                'dairy-substitutes' => ['Crémerie, oeufs, laits, boissons végétales'],
                
                // PETIT DÉJEUNER
                'petit-dejeuner' => ['Petit déjeuner'],
                'breakfasts' => ['Petit déjeuner'],
                'breakfast-cereals' => ['Petit déjeuner'],
                'cereals-and-potatoes' => ['Riz, semoules, légumes secs'],
                'cereals-and-their-products' => ['Pâtes', 'Riz, semoules, légumes secs'],
                'mixed nuts' => ['Fruits secs, graines'],
                'nuts-and-their-products' => ['Fruits secs, graines'],
                'nuts' => ['Fruits secs, graines'],
                'seeds' => ['Fruits secs, graines'],
                'cheeses' => ['Fromages'],
                'cow-cheeses' => ['Fromages'],
                'dairies' => ['Crémerie, oeufs, laits, boissons végétales'],
                'dairy-desserts' => ['Yaourts, desserts'],
                'dairy-substitutes' => ['Crémerie, oeufs, laits, boissons végétales'],
                'milks' => ['Crémerie, oeufs, laits, boissons végétales'],
                'yogurts' => ['Yaourts, desserts'],
                'fermented-milk-products' => ['Fromages', 'Yaourts, desserts'],
                'wines' => ['Vins'],
                'alcoholic-beverages' => ['Bières, fûts, cidres', 'Vins', 'Apéritifs, spiritueux'],
                'beers' => ['Bières, fûts, cidres'],
                'waters' => ['Eaux'],
                'beverages' => ['Eaux', 'Jus de fruits, jus de légumes', 'Colas, boissons gazeuses, énergisantes'],
                'plant-based-beverages' => ['Lait, boissons végétales'],
                'fruit-based-beverages' => ['Jus de fruits, jus de légumes'],
                'hot-beverages' => ['Cafés', 'Thés, infusions, chocolat en poudre'],
                'carbonated-drinks' => ['Colas, boissons gazeuses, énergisantes'],
                'coffees' => ['Cafés'],
                'teas' => ['Thés, infusions, chocolat en poudre'],
                'chocolates' => ['Chocolats'],
                'cocoa-and-its-products' => ['Chocolats'],
                'chocolate-candies' => ['Chocolats', 'Bonbons, confiseries'],
                'confectioneries' => ['Bonbons, confiseries'],
                'candies' => ['Bonbons, confiseries'],
                'sweet-snacks' => ['Bonbons, confiseries', 'Gâteaux moelleux'],
                'salty-snacks' => ['Apéritif'],
                'snacks' => ['Apéritif'],
                'biscuits-and-cakes' => ['Gâteaux moelleux', 'Biscuits'],
                'biscuits-and-crackers' => ['Biscuits', 'Pains apéritif, taosts'],
                'biscuits' => ['Biscuits'],
                'cakes' => ['Gâteaux moelleux'],
                'breads' => ['Pains, pâtisseries fraîches'],
                'pastas' => ['Pâtes'],
                'spreads' => ['Apéritifs, tartinables'],
                'sweet-spreads' => ['Compotes, crémes dessert'],
                'salted-spreads' => ['Apéritifs, tartinables'],
                'plant-based-spreads' => ['Apéritifs, tartinables'],
                'sauces' => ['Sauces, huiles, aides culinaires'],
                'condiments' => ['Sauces, huiles, aides culinaires'],
                'vegetable-oils' => ['Sauces, huiles, aides culinaires'],
                'fats' => ['Sauces, huiles, aides culinaires'],
                'vegetable-fats' => ['Sauces, huiles, aides culinaires'],
                'olive-tree-products' => ['Sauces, huiles, aides culinaires'],
                'honeys' => ['Compotes, crémes dessert'],
                'bee-products' => ['Compotes, crémes dessert'],
                'jams' => ['Compotes, crémes dessert'],
                'sweeteners' => ['Sucres, farine, aides à la patisseries'],
                'sugars' => ['Sucres, farine, aides à la patisseries'],
                'soups' => ['Conserves, soupes'],
                'canned-foods' => ['Conserves, soupes'],
                'canned-vegetables' => ['Conserves, soupes'],
                'canned-plant-based-foods' => ['Conserves, soupes'],
                'baby foods' => ['Repas de bébé'],
                'diapers' => ['Couches, toilette de bébé'],
                'soaps' => ['Soins du corps'],
                'shampoos' => ['Soins des cheveux'],
                'toothpastes' => ['Hygiène dentaire'],
                'plant-based foods and beverages' => ['Bio et écologique'],
                'plant-based foods' => ['Bio et écologique'],
                'dried-plant-based-foods' => ['Fruits secs, graines'],
                'fruits-and-vegetables-based-foods' => ['Fruits, légumes bio'],
                'fruits-based-foods' => ['Fruits frais'],
                'vegetables-based-foods' => ['Légumes'],
                'vegetables' => ['Légumes'],
                'fruits' => ['Fruits frais'],
                'legumes' => ['Riz, semoules, légumes secs'],
                'legumes-and-their-products' => ['Riz, semoules, légumes secs'],
                'meats-and-their-products' => ['Boucherie'],
                'meats' => ['Boucherie'],
                'prepared-meats' => ['Charcuterie'],
                'hams' => ['Charcuterie'],
                'sausages' => ['Charcuterie'],
                'chicken-and-its-products' => ['Volaille, lapin'],
                'chickens' => ['Volaille, lapin'],
                'poultries' => ['Volaille, lapin'],
                'fishes-and-their-products' => ['Poissons, crustacés'],
                'fishes' => ['Poissons, crustacés'],
                'seafood' => ['Poissons, crustacés'],
                'fatty-fishes' => ['Poissons, crustacés'],
                'frozen-foods' => ['Surgelés'],
                'frozen-desserts' => ['Glaces'],
                'ice-creams-and-sorbets' => ['Glaces'],
                'desserts' => ['Gâteaux moelleux', 'Yaourts, desserts'],
                'meals' => ['Plats cuisinés'],
                'meals-with-meat' => ['Plats cuisinés'],
                'appetizers' => ['Apéritif'],
                'crisps' => ['Apéritif'],
                'chips-and-fries' => ['Frites, prommes de terre'],
                'pizzas-pies-and-quiches' => ['Pizzas'],
                'dietary-supplements' => ['Compléments alimentaires'],
                'fermented-foods' => ['Bio et écologique'],
                'farming-products' => ['Fruits, légumes bio'],
                'groceries' => ['Epicerie salée'],
                'dried-products' => ['Fruits secs, graines'],
                'fruit-and-vegetable-preserves' => ['Compotes, crémes dessert'],
                'fruit-juices' => ['Jus de fruits, jus de légumes'],
                'juices-and-nectars' => ['Jus de fruits, jus de légumes'],
                'beverages-and-beverages-preparations' => ['Eaux', 'Jus de fruits, jus de légumes'],
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
                    // Trouver une sous-catégorie correspondante
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
