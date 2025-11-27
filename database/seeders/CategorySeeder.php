<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Structure complète des catégories
        $data = [
            'Produits laitiers, oeufs, Fromages' => [
                'Crémerie, oeufs, laits, boissons végétales',
                'Yaourts, desserts',
                'Fromages',
                'Desserts, fromages enfant',
            ],
            'Boucherie, volaille, poissonerie' => [
                'Boucherie',
                'Volaille, lapin',
                'Pané, nuggets, grignottes',
                'Poissons, crustacés',
                'Traiteur de la mer',
            ],
            'Pain, pâtisserie' => [
                'Pains, pâtisseries fraîches',
                'Pains de mie',
                'Pains burger, pain du monde',
                'Brioches, pains au lait emballés',
                'Pains apéritif, taosts',
                'Pains précuits, pâtisseries surgelées',
            ],
            'Epicerie sucrée' => [
                'Cafés',
                'Thés, infusions, chocolat en poudre',
                'Petit déjeuner',
                'Biscuits',
                'Gâteaux moelleux',
                'Chocolats',
                'Bonbons, confiseries',
                'Compotes, crémes dessert',
                'Sucres, farine, aides à la patisseries',
            ],
            'Eaux, jus, soda, thés glacés' => [
                'Eaux',
                'Jus de fruits, jus de légumes',
                'Colas, boissons gazeuses, énergisantes',
                'Thés, boissons plates aux fruits',
                'Sirops, concentrés',
                'Bières, vins et apéritifs sans alcool',
                'Lait, boissons végétales',
            ],
            'Hygiène, beauté' => [
                'Hygiène dentaire',
                'Soins du corps',
                'Soins des cheveux',
                'Soins du visages, maquillage',
                'Soins homme',
                'Mouchoirs, papier toilette, cotons',
                'Protections hygièniques',
                'Petite parapharmacie',
            ],
            'Tout pour bébé' => [
                'Laits, petits-déjeuners de bébé',
                'Repas de bébé',
                'Tout pour bébé',
                'Desserts, goûters, jus',
                'Couches, toilette de bébé',
                'Puériculture',
                'Vêtements, chaussures bébé',
            ],
            'Produits du monde' => [
                'Asie',
                'Moyen-Orient, Halal',
                'Tex Mex',
                'Italie',
                'Autres produits du monde',
            ],
            'Bio et nutrition' => [
                'Bio et écologique',
                'Végétarien, vegan',
                'Sans gluten',
                'Sans sucres, sans sucres ajoutés',
                'Sans lactose',
                'Nutrition sportive',
                'Compléments alimentaires',
                'Produits allégés, bien-être',
            ],
            'Fruits, légumes' => [
                'Fruits, légumes bio',
                'Fruits frais',
                'Fruits secs, graines',
                'Légumes',
                'Légumes prêts à consommer',
                'Jus de fruits frais',
                'Fleurs, plantes',
            ],
            'Charcurterie, traiteur' => [
                'Charcuterie',
                'Traiteur',
                'Plats suicinés, snacking, salade',
                'Traiteur végétal',
                'Apéritifs, tartinables',
            ],
            'Surgelés' => [
                'Pizzas',
                'Apéritifs, plats cuisinés, produits du monde',
                'Légumes, fruits',
                'Frites, prommes de terre',
                'Viandes',
                'Poissons, fruits de mer',
                'Glaces',
                'Pâtisseries, viennoiseries',
            ],
            'Epicerie salée' => [
                'Apéritif',
                'Sauces, huiles, aides culinaires',
                'Conserves, soupes',
                'Pâtes',
                'Riz, semoules, légumes secs',
                'Plats cuisinés',
                'Epiceries du monde',
            ],
            'Vins, bières, alcools' => [
                'Bières, fûts, cidres',
                'Apéritifs, spiritueux',
                'Vins',
                'Champagnes, vins effervescents',
                'Cocktails à faire soi-même',
                'Bières, vins et apéritifs sans alcool',
            ],
            'Entretien, accessoires de la maison' => [
                'Papier toilette, essuie-tout, mouchoir',
                'Soin du linge',
                'Vaisselle, entretien lave vaisselle',
                'Produits d\'entretien',
                'Sacs poubelles, accessoires ménagers',
                'Accessoires cuisine, maison',
                'Fournitures scolaires, bureau, loisirs',
            ],
            'Animalerie' => [
                'Chat',
                'Chien',
                'Rongeurs, oiseaux, poissons',
                'Accessoires animalerie',
                'Produits de nos régions',
            ],
            'Jouets, jeux vidéo, livres' => [
                'Jeux, jouets',
                'Jeux vidéo',
                'Sport, plein air',
                'Livres',
                'Déguisements',
                'Loisirs créatifs, art',
                'Coffrets cadeaux',
                'Marques jouets, jeux vidéo',
            ],
            'Electroménager, cuisine' => [
                'Gros electroménager',
                'Petits appareils de cuisine',
                'Cuisine, arts de la table',
                'Aspirateur, nettoyage',
                'Beauté, bien-être',
                'Climatisation, chauffage',
                'Repassage, couture, séchoir',
            ],
            'Mode, bijoux, bagagerie' => [
                'Bijoux',
                'Vêtements, chausures femme',
                'Vêtements, chausures homme',
                'Vêtements, chaussures enfant',
                'Bagagerie, maroquinerie',
                'Vêtements, chaussures bébé',
            ],
            'Billetterie, traiteur, voyage' => [
                'Billeterie',
                'Cartes cadeaux',
                'Voyages',
                'Billetterie, traiteur, voyage',
                'Développement photo',
                'Traiteur',
                'Télécom',
                'Coffrets cadeaux',
            ],
            'Jardin, auto, brico' => [
                'Jardin',
                'Bricolage',
                'Auto, Moto',
                'Accessoires animalerie',
            ],
            'High-tech, téléphonie' => [
                'Téléphonie',
                'TV, vidéoprojecteur, home cinema',
                'Informatique',
                'Audio, hifi',
                'Objets conenctés',
                'Mobilité urbaine électrique',
                'Photo, Caméra',
                'High tech reconditionné',
            ],
            'Meuble, linge de maison' => [
                'Linge de maison',
                'Meubles, canapés',
                'Literie',
                'Décoration',
                'Puériculture',
            ],
        ];

        // Créer les catégories principales et sous-catégories
        foreach ($data as $categoryName => $subCategories) {
            $categoryPath = $this->generatePath($categoryName);
            $category = Category::create([
                'name' => $categoryName,
                'user_id' => 1,
                'path' => $categoryPath,
                'parent_id' => null,
            ]);

            // Créer les sous-catégories
            foreach ($subCategories as $subCategoryName) {
                $subCategoryPath = $categoryPath . '/' . $this->generatePath($subCategoryName);
                Category::create([
                    'name' => $subCategoryName,
                    'user_id' => 1,
                    'path' => $subCategoryPath,
                    'parent_id' => $category->id,
                ]);
            }
        }
    }

    /**
     * Génère un slug à partir du nom de la catégorie
     */
    private function generatePath(string $name): string
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
    }
}
