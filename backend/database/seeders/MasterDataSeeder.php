<?php

namespace Database\Seeders;

use App\Models\AmenityMaster;
use App\Models\PropertyTypeMaster;
use Illuminate\Database\Seeder;

/**
 * Property types and amenities — the two pieces of MasterDataController's
 * reference data that aren't geographic. Cities/localities are seeded by
 * WorldCitiesSeeder + IndianLocalitiesSeeder instead (see those for why
 * they're split out).
 */
class MasterDataSeeder extends Seeder
{
    protected array $propertyTypes = [
        'Apartment/Flat',
        'Independent House/Villa',
        'Plot/Land',
        'Commercial Office Space',
        'Commercial Shop',
        'Farmhouse',
        'Penthouse',
        'Studio Apartment',
    ];

    /**
     * @var array<string, array<int, string>>
     */
    protected array $amenitiesByCategory = [
        'Building' => ['Lift/Elevator', 'Power Backup', 'Security/CCTV', 'Fire Safety', 'Intercom'],
        'Recreation' => ['Swimming Pool', 'Gymnasium', 'Clubhouse', "Children's Play Area", 'Garden/Park'],
        'Parking' => ['Covered Parking', 'Visitor Parking'],
        'Utilities' => ['Water Supply (24x7)', 'Piped Gas', 'Rainwater Harvesting', 'Waste Disposal'],
        'Connectivity' => ['Wi-Fi/Internet', 'DTH/Cable Provision'],
    ];

    public function run(): void
    {
        foreach ($this->propertyTypes as $name) {
            PropertyTypeMaster::firstOrCreate(['name' => $name]);
        }

        foreach ($this->amenitiesByCategory as $category => $names) {
            foreach ($names as $name) {
                AmenityMaster::firstOrCreate(['name' => $name], ['category' => $category]);
            }
        }
    }
}
