<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Boutique;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DistanceService
{
    /**
     * Calculer la distance entre deux points en utilisant la formule Haversine
     *
     * @param float $lat1 Latitude du premier point
     * @param float $lon1 Longitude du premier point
     * @param float $lat2 Latitude du deuxième point
     * @param float $lon2 Longitude du deuxième point
     * @return float Distance en kilomètres
     */
    public function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Rayon de la Terre en kilomètres

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * Obtenir les coordonnées GPS d'une adresse (géocodage)
     *
     * @param string $address Adresse complète
     * @return array|null ['lat' => float, 'lng' => float] ou null si échec
     */
    public function geocodeAddress(string $address): ?array
    {
        try {
            // Utiliser l'API Nominatim (OpenStreetMap) - gratuite et sans clé API
            $response = Http::timeout(5)->get('https://nominatim.openstreetmap.org/search', [
                'q' => $address,
                'format' => 'json',
                'limit' => 1,
                'addressdetails' => 1,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
                    return [
                        'lat' => (float) $data[0]['lat'],
                        'lng' => (float) $data[0]['lon'],
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning("Erreur lors du géocodage de l'adresse", [
                'address' => $address,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Obtenir les coordonnées d'une adresse (Address model)
     *
     * @param Address $address
     * @return array|null ['lat' => float, 'lng' => float] ou null
     */
    public function getAddressCoordinates(Address $address): ?array
    {
        // Si l'adresse a déjà des coordonnées stockées, les utiliser
        // Sinon, faire un géocodage
        $fullAddress = $address->full_address;
        return $this->geocodeAddress($fullAddress);
    }

    /**
     * Obtenir les coordonnées d'une boutique
     *
     * @param Boutique $boutique
     * @return array|null ['lat' => float, 'lng' => float] ou null
     */
    public function getBoutiqueCoordinates(Boutique $boutique): ?array
    {
        if ($boutique->latitude && $boutique->longitude) {
            return [
                'lat' => (float) $boutique->latitude,
                'lng' => (float) $boutique->longitude,
            ];
        }

        // Sinon, géocoder l'adresse
        $address = $boutique->city . ', ' . $boutique->postal_code . ', Madagascar';
        return $this->geocodeAddress($address);
    }

    /**
     * Obtenir les coordonnées d'un transporteur (lat/lng stockés, adresse par défaut ou géocodage).
     * Utilise la relation addresses si déjà chargée pour éviter N+1.
     *
     * @param User $transporter
     * @return array|null ['lat' => float, 'lng' => float] ou null
     */
    public function getTransporterCoordinates(User $transporter): ?array
    {
        if (isset($transporter->latitude) && isset($transporter->longitude)
            && (float) $transporter->latitude != 0 && (float) $transporter->longitude != 0) {
            return [
                'lat' => (float) $transporter->latitude,
                'lng' => (float) $transporter->longitude,
            ];
        }

        $defaultAddress = $transporter->relationLoaded('addresses')
            ? $transporter->addresses->where('is_default', true)->first()
            : $transporter->addresses()->where('is_default', true)->first();

        if ($defaultAddress) {
            return $this->getAddressCoordinates($defaultAddress);
        }

        if (!empty($transporter->address)) {
            return $this->geocodeAddress($transporter->address);
        }

        if (!empty($transporter->geographic_address)) {
            return $this->geocodeAddress($transporter->geographic_address);
        }

        return null;
    }

    /**
     * Calculer la distance entre deux adresses
     *
     * @param Address|Boutique|User $from Point de départ
     * @param Address|Boutique|User $to Point d'arrivée
     * @return float|null Distance en kilomètres ou null si impossible à calculer
     */
    public function calculateDistance($from, $to): ?float
    {
        $fromCoords = null;
        $toCoords = null;

        // Obtenir les coordonnées du point de départ
        if ($from instanceof Address) {
            $fromCoords = $this->getAddressCoordinates($from);
        } elseif ($from instanceof Boutique) {
            $fromCoords = $this->getBoutiqueCoordinates($from);
        } elseif ($from instanceof User) {
            $fromCoords = $this->getTransporterCoordinates($from);
        } elseif (is_array($from) && isset($from['lat']) && isset($from['lng'])) {
            $fromCoords = $from;
        }

        // Obtenir les coordonnées du point d'arrivée
        if ($to instanceof Address) {
            $toCoords = $this->getAddressCoordinates($to);
        } elseif ($to instanceof Boutique) {
            $toCoords = $this->getBoutiqueCoordinates($to);
        } elseif ($to instanceof User) {
            $toCoords = $this->getTransporterCoordinates($to);
        } elseif (is_array($to) && isset($to['lat']) && isset($to['lng'])) {
            $toCoords = $to;
        }

        if (!$fromCoords || !$toCoords) {
            return null;
        }

        return $this->calculateHaversineDistance(
            $fromCoords['lat'],
            $fromCoords['lng'],
            $toCoords['lat'],
            $toCoords['lng']
        );
    }
}
