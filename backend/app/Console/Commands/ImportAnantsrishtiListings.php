<?php

namespace App\Console\Commands;

use App\Models\CityMaster;
use App\Models\Property;
use App\Models\PropertyTypeMaster;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * One-off import of real leads collected via a Google Form (Anantsrishti
 * Society, Jambhul/Kanhe Phata, Maval taluka, Pune district) — not a
 * seeder, since this is real operational data meant to run exactly once,
 * not something to replay on every fresh install.
 *
 * The form captured no GPS coordinates, only a text address — every row
 * here gets the same approximate pin for the Kanhe/Maval area, which
 * whoever manages each listing should drag to the exact spot via the
 * property detail page's map picker. Photos (two rows had Google Drive
 * share links, not directly fetchable) are intentionally not imported —
 * add them manually through the same page's uploader.
 *
 * Idempotent: re-running finds existing users by phone and existing
 * properties by (owner_id, title) rather than duplicating them.
 */
#[Signature('app:import-anantsrishti-listings')]
#[Description('Import the Anantsrishti Society property leads collected via Google Form')]
class ImportAnantsrishtiListings extends Command
{
    // Approximate — no exact address/coordinates were collected on the
    // form. Correct via the map picker once the real spot is confirmed.
    private const APPROX_LATITUDE = 18.7333;

    private const APPROX_LONGITUDE = 73.4667;

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $listings = [
        [
            'owner_name' => 'Suhas Pavangadkar',
            'owner_phone' => '9860559391',
            'owner_email' => 'snpavangadkar@gmail.com',
            'bhk' => 3,
            'address' => 'ANANTSRISHTI Society HIJQ buildings, Jambhul, Tal. Maval, Dist. Pune',
            'area_sqft' => 750,
            'floor_no' => null,
            'total_floors' => null,
            'price' => 3_050_000,
            'reserve_price' => null,
            'negotiable' => true,
            'notes' => 'Two and half BHK, carpet area 750 sq ft.',
        ],
        [
            'owner_name' => 'Shankaranand Yashwant Yadav',
            'owner_phone' => '9730308762',
            'owner_email' => 'syyadav1972@gmail.com',
            'bhk' => 1,
            'address' => 'K307, Anantsrishti, Jambhul, Tal. Maval, Dist. Pune',
            'area_sqft' => 582,
            'floor_no' => 3,
            'total_floors' => 9,
            'price' => 2_500_000,
            'reserve_price' => 2_300_000,
            'negotiable' => true,
            'notes' => 'No parking. Photos on file with the original Google Form submission — attach via this listing\'s detail page.',
        ],
        [
            'owner_name' => 'Shrikrishna Korde',
            'owner_phone' => '9890960905',
            'owner_email' => 'Shrikrishna.Korde@sulzer.com',
            'bhk' => 1,
            'address' => 'Anantsrishti, Kanhe Phata, Tal. Maval, Dist. Pune',
            'area_sqft' => 595,
            'floor_no' => 5,
            'total_floors' => null,
            'price' => 2_300_000,
            'reserve_price' => 2_200_000,
            'negotiable' => true,
            'notes' => 'No parking. Property is 10 years old. Photos on file with the original Google Form submission — attach via this listing\'s detail page.',
        ],
    ];

    public function handle(): int
    {
        $city = CityMaster::where('name', 'Pune')->where('country', 'India')->first();

        if (! $city) {
            $this->error('Pune (India) not found in cities_master — run WorldCitiesSeeder first.');

            return self::FAILURE;
        }

        $propertyType = PropertyTypeMaster::where('name', 'Apartment/Flat')->first();

        if (! $propertyType) {
            $this->error('"Apartment/Flat" not found in property_types_master — run MasterDataSeeder first.');

            return self::FAILURE;
        }

        foreach ($this->listings as $listing) {
            $owner = User::firstOrCreate(
                ['phone' => $listing['owner_phone']],
                ['name' => $listing['owner_name'], 'email' => $listing['owner_email'], 'status' => 'active'],
            );

            if (! $owner->hasAnyRole(['seller', 'agent', 'admin'])) {
                $owner->assignRole('seller');
            }

            $title = "{$listing['bhk']} BHK Flat — Anantsrishti Society, Jambhul (Kanhe Phata)";

            $description = trim($listing['notes'].
                ($listing['reserve_price'] ? "\n\nOwner's bottom-line price: ₹".number_format($listing['reserve_price']) : ''));

            $property = Property::firstOrCreate(
                ['owner_id' => $owner->id, 'title' => $title],
                [
                    'description' => $description,
                    'property_type_id' => $propertyType->id,
                    'listing_type' => 'sale',
                    'price' => $listing['price'],
                    'price_negotiable' => $listing['negotiable'],
                    'area_sqft' => $listing['area_sqft'],
                    'bedrooms' => $listing['bhk'],
                    'floor_no' => $listing['floor_no'],
                    'total_floors' => $listing['total_floors'],
                    'city_id' => $city->id,
                    'locality_text' => 'Jambhul (Kanhe Phata), Maval',
                    'address' => $listing['address'],
                    'latitude' => self::APPROX_LATITUDE,
                    'longitude' => self::APPROX_LONGITUDE,
                    'status' => 'pending_review',
                ],
            );

            $this->info("{$owner->name} (#{$owner->id}) -> property #{$property->id}: {$title}");
        }

        $this->newLine();
        $this->warn('Coordinates are approximate for the whole Kanhe/Maval area, not the exact building — correct each listing\'s pin via its detail page. Photos were not imported — add them the same way.');

        return self::SUCCESS;
    }
}
