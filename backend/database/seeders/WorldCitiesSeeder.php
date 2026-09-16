<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Cities worldwide (~34k, population 15,000+), derived from GeoNames'
 * public-domain cities15000 dataset + admin1CodesASCII/countryInfo for
 * readable state/country names (see data/world_cities.csv — regenerate
 * via the script noted at the bottom of this file if it ever needs
 * refreshing).
 *
 * Only name/country/state are stored — no locality/neighborhood data
 * exists at this scale for most of the world, so non-Indian cities get
 * a free-text locality on the property form instead of a dropdown (see
 * the make_locality_optional_on_properties_table migration). Run
 * MasterDataSeeder's IndianLocalitiesSeeder afterward to attach the
 * hand-curated Indian localities to the rows this seeder creates.
 */
class WorldCitiesSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('cities_master')->whereNotNull('country')->exists()) {
            $this->command?->info('World cities already seeded, skipping.');

            return;
        }

        $path = __DIR__.'/data/world_cities.csv';
        $handle = fopen($path, 'r');
        fgetcsv($handle); // header row

        $now = now();
        $batch = [];

        while (($row = fgetcsv($handle)) !== false) {
            [$name, $country, $state] = $row;

            $batch[] = [
                'name' => $name,
                'country' => $country,
                'state' => $state !== '' ? $state : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= 1000) {
                DB::table('cities_master')->insert($batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            DB::table('cities_master')->insert($batch);
        }

        fclose($handle);
    }
}

// To regenerate data/world_cities.csv from a fresh GeoNames export, see
// the ETL script kept in this project's session history — it joins
// cities15000.txt + admin1CodesASCII.txt + countryInfo.txt into the
// name,country,state,latitude,longitude,population columns this file
// reads (only the first three are actually used above).
