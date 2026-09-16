<?php

namespace Database\Seeders;

use App\Models\CityMaster;
use App\Models\LocalityMaster;
use Illuminate\Database\Seeder;

/**
 * Hand-curated neighborhoods for a handful of major Indian cities — no
 * public dataset covers locality-level data worldwide (or even
 * India-wide), so these stay hand-picked. Must run after
 * WorldCitiesSeeder, which is what actually creates the city rows these
 * attach to (looked up by name — a few GeoNames names differ from the
 * common English one, e.g. "Bangalore" -> "Bengaluru").
 */
class IndianLocalitiesSeeder extends Seeder
{
    /**
     * @var array<string, array<int, string>>
     */
    protected array $localitiesByCity = [
        'Mumbai' => ['Andheri West', 'Bandra West', 'Powai', 'Thane West', 'Malad West'],
        'Pune' => ['Baner', 'Hinjewadi', 'Kothrud', 'Viman Nagar', 'Wakad'],
        'Bengaluru' => ['Whitefield', 'Koramangala', 'Indiranagar', 'Electronic City', 'HSR Layout'],
        'Delhi' => ['Dwarka', 'Rohini', 'Saket', 'Vasant Kunj', 'Karol Bagh'],
        'Hyderabad' => ['Gachibowli', 'Hitech City', 'Kondapur', 'Madhapur', 'Banjara Hills'],
        'Chennai' => ['OMR', 'Anna Nagar', 'Velachery', 'T Nagar', 'Adyar'],
        'Ahmedabad' => ['Satellite', 'Bopal', 'Vastrapur', 'SG Highway', 'Prahlad Nagar'],
    ];

    public function run(): void
    {
        foreach ($this->localitiesByCity as $cityName => $localities) {
            $city = CityMaster::where('name', $cityName)->where('country', 'India')->first();

            if (! $city) {
                $this->command?->warn("Skipping localities for \"{$cityName}\" — no matching city row (run WorldCitiesSeeder first).");

                continue;
            }

            foreach ($localities as $localityName) {
                LocalityMaster::firstOrCreate(['city_id' => $city->id, 'name' => $localityName]);
            }
        }
    }
}
