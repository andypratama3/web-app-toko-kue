<?php

namespace Database\Seeders;

use App\Models\DeliveryZone;
use App\Models\Region;
use Illuminate\Database\Seeder;

class DeliveryZoneSeeder extends Seeder
{
    public function run(): void
    {
        $regions = Region::all();

        foreach ($regions as $region) {
            $baseZones = match ($region->slug) {
                'surabaya' => [
                    ['area_name' => 'Pusat Kota Surabaya', 'landmark_keyword' => 'tunjungan, gubeng, kenjeran', 'distance_km' => 3.0, 'ongkir' => 10000],
                    ['area_name' => 'Surabaya Utara', 'landmark_keyword' => 'bulungan, wonokromo', 'distance_km' => 6.0, 'ongkir' => 10000],
                    ['area_name' => 'Surabaya Barat', 'landmark_keyword' => 'citraland, sukatani', 'distance_km' => 9.0, 'ongkir' => 10000],
                    ['area_name' => 'Sidoarjo', 'landmark_keyword' => 'sidoarjo, gedangan', 'distance_km' => 12.0, 'ongkir' => 15000],
                    ['area_name' => 'Gresik', 'landmark_keyword' => 'gresik, manyar', 'distance_km' => 15.0, 'ongkir' => 15000],
                ],
                'malang' => [
                    ['area_name' => 'Pusat Kota Malang', 'landmark_keyword' => 'jalan langstar, dinoyo', 'distance_km' => 2.0, 'ongkir' => 10000],
                    ['area_name' => 'Malang Selatan', 'landmark_keyword' => 'sukun, bandungan', 'distance_km' => 5.0, 'ongkir' => 10000],
                    ['area_name' => 'Batu', 'landmark_keyword' => 'batu, jatim park', 'distance_km' => 10.0, 'ongkir' => 15000],
                    ['area_name' => 'Singosari', 'landmark_keyword' => 'singosari, tulusmulyo', 'distance_km' => 12.0, 'ongkir' => 15000],
                    ['area_name' => 'Lawang', 'landmark_keyword' => 'lawang, pujer', 'distance_km' => 14.0, 'ongkir' => 15000],
                ],
                'denpasar' => [
                    ['area_name' => 'Pusat Kota Denpasar', 'landmark_keyword' => 'renon, panjer, sudirman', 'distance_km' => 2.0, 'ongkir' => 10000],
                    ['area_name' => 'Denpasar Selatan', 'landmark_keyword' => 'sanur, panitta', 'distance_km' => 5.0, 'ongkir' => 10000],
                    ['area_name' => 'Kuta', 'landmark_keyword' => 'kuta, legian, seminyak', 'distance_km' => 8.0, 'ongkir' => 10000],
                    ['area_name' => 'Canggu', 'landmark_keyword' => 'canggu, echo beach', 'distance_km' => 12.0, 'ongkir' => 15000],
                    ['area_name' => 'Ubud', 'landmark_keyword' => 'ubud, gianyar', 'distance_km' => 15.0, 'ongkir' => 15000],
                ],
                default => [
                    ['area_name' => 'Pusat Kota', 'landmark_keyword' => 'pusat, kota', 'distance_km' => 5.0, 'ongkir' => 10000],
                    ['area_name' => 'Area Sekitar', 'landmark_keyword' => 'sekitar', 'distance_km' => 10.0, 'ongkir' => 10000],
                ],
            };

            foreach ($baseZones as $zone) {
                DeliveryZone::create(array_merge($zone, [
                    'region_id' => $region->id,
                    'is_active' => true,
                ]));
            }
        }
    }
}
