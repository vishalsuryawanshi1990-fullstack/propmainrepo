<?php

namespace Database\Seeders;

use App\Models\AmenityMaster;
use App\Models\CityMaster;
use App\Models\LocalityMaster;
use App\Models\PropertyTypeMaster;
use Illuminate\Database\Seeder;

/**
 * Reference data every "post a property" form needs for its dropdowns
 * (MasterDataController) — never had real rows, only the migrations
 * that create these tables. Real Indian cities/localities since phone
 * numbers/currency elsewhere in the app are India-specific.
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
     * @var array<string, array{state: string, localities: array<int, string>}>
     */
    protected array $cities = [
        'Mumbai' => ['state' => 'Maharashtra', 'localities' => ['Andheri West', 'Bandra West', 'Powai', 'Thane West', 'Malad West']],
        'Pune' => ['state' => 'Maharashtra', 'localities' => ['Baner', 'Hinjewadi', 'Kothrud', 'Viman Nagar', 'Wakad']],
        'Bangalore' => ['state' => 'Karnataka', 'localities' => ['Whitefield', 'Koramangala', 'Indiranagar', 'Electronic City', 'HSR Layout']],
        'Delhi' => ['state' => 'Delhi', 'localities' => ['Dwarka', 'Rohini', 'Saket', 'Vasant Kunj', 'Karol Bagh']],
        'Hyderabad' => ['state' => 'Telangana', 'localities' => ['Gachibowli', 'Hitech City', 'Kondapur', 'Madhapur', 'Banjara Hills']],
        'Chennai' => ['state' => 'Tamil Nadu', 'localities' => ['OMR', 'Anna Nagar', 'Velachery', 'T Nagar', 'Adyar']],
        'Ahmedabad' => ['state' => 'Gujarat', 'localities' => ['Satellite', 'Bopal', 'Vastrapur', 'SG Highway', 'Prahlad Nagar']],
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

        foreach ($this->cities as $cityName => $details) {
            $city = CityMaster::firstOrCreate(['name' => $cityName], ['state' => $details['state']]);

            foreach ($details['localities'] as $localityName) {
                LocalityMaster::firstOrCreate(['city_id' => $city->id, 'name' => $localityName]);
            }
        }

        foreach ($this->amenitiesByCategory as $category => $names) {
            foreach ($names as $name) {
                AmenityMaster::firstOrCreate(['name' => $name], ['category' => $category]);
            }
        }
    }
}
