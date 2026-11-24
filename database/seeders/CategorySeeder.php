<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoriesData = [
            'Interior Residential / Commercial Repairs & Maintenance' => [
                'Refrigeration Repairs/ Maintenance',
                'Plumbing',
                'Locksmiths',
                'Appliance Repairs',
                'Garage Door Repairs',
                'Handyman',
                'Furnace Repairs',
                'HVAC',
                'Electrical',
                'Windows & Doors',
            ],
            'Cleaning & Organization' => [
                'House Cleaning',
                'Carpet Cleaning',
                'Upholstery Cleaning',
                'Home Organization',
                'Deep Cleaning',
                'Junk Removal',
                'Duct & Vent Cleaning',
                'Pool Cleaning',
                'Commercial Cleaners',
            ],
            'Renovations & Upgrades' => [
                'General Contracting',
                'Carpenters',
                'Bathroom Remodeling',
                'Kitchen Remodeling',
                'Flooring Installation',
                'Interior Design',
                'Carpet Installation',
                'Interior Painting',
                'Basement Remodeling',
            ],
            'Exterior Residential / Commercial Care' => [
                'Refrigeration Maintenance and repairs',
                'Roofing',
                'House Painting',
                'Window Washing',
                'Chimney Sweeps',
                'Pools',
                'Gutter Cleaning',
                'Deck Contractors',
                'Siding',
                'Concrete & Masonry',
            ],
            'Landscaping & Outdoor Services' => [
                'Lawn Care',
                'Landscaping Design',
                'Gardening',
                'Tree Trimming',
                'Sprinkler System Repairs',
                'Artificial Turf Installation',
                'Stump Grinding',
                'Sod Installation',
                'Arborists',
            ],
            'Moving' => [
                'Helpers',
                'Local Movers',
                'Long Distance Movers',
                'Piano Movers',
                'Packing & Unpacking',
                'Move In & Move Out Cleaning',
                'Storage Companies',
                'Furniture Movers',
            ],
            'Installation & Assembly' => [
                'Holiday Light Hanging',
                'TV Mounting',
                'Security Camera Installation',
                'Appliance Installation',
                'Furniture Assembly',
                'Ceiling Fan Installation',
                'Generator Installation',
                'Art & Picture Hanging',
                'Gym Equipment Assembly',
            ],
            'Pest Control' => [
                'Mosquito Control',
                'Rodent Control',
                'Bee Removal',
                'Bed Bug Exterminators',
                'Wasp Nest Removal',
                'Termites',
            ],
            'Trending Services' => [
                'Attic insulators',
                'Laptop repair',
                'Health coaches',
                'Home theater installation',
            ],
        ];

        foreach ($categoriesData as $parentName => $subcategories) {
            $parentSlug = Str::slug($parentName);

            $parentCategory = ServiceCategory::updateOrCreate(
                ['slug' => $parentSlug],
                [
                    'parent_id' => null,
                    'name' => $parentName,
                    'tags' => $this->getDefaultTagsFor($parentName),
                    'is_active' => true,
                ]
            );

            // Create base service for parent
            Service::updateOrCreate(
                ['slug' => $parentSlug.'-service'],
                [
                    'service_category_id' => $parentCategory->id,
                    'name' => $parentCategory->name.' Service',
                    'description' => 'General '.$parentCategory->name,
                    'base_price' => 0.00,
                    'is_active' => true,
                ]
            );

            foreach ($subcategories as $subName) {
                $subSlug = Str::slug($subName);

                $subCategory = ServiceCategory::updateOrCreate(
                    ['slug' => $subSlug],
                    [
                        'parent_id' => $parentCategory->id,
                        'name' => $subName,
                        'tags' => $this->getDefaultTagsFor($subName),
                        'is_active' => true,
                    ]
                );

                Service::updateOrCreate(
                    ['slug' => $subSlug.'-service'],
                    [
                        'service_category_id' => $subCategory->id,
                        'name' => $subCategory->name.' Service',
                        'description' => 'General '.$subCategory->name,
                        'base_price' => 0.00,
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    /**
     * Return default search tags based on category or subcategory name.
     *
     * @return array<int, string>
     */
    private function getDefaultTagsFor(string $name): array
    {
        $lower = strtolower($name);

        return match (true) {
            str_contains($lower, 'plumb') => ['leak', 'pipe', 'tap', 'faucet', 'drain', 'basin', 'toilet', 'flush', 'sink', 'plumber'],
            str_contains($lower, 'refrigerat') => ['fridge', 'refrigerator', 'freezer', 'cooling', 'ice maker', 'compressor'],
            str_contains($lower, 'locksmith') => ['lock', 'key', 'door', 'deadbolt', 'lockout', 'handle', 'latch'],
            str_contains($lower, 'appliance') => ['washing machine', 'dryer', 'microwave', 'oven', 'dishwasher', 'stove'],
            str_contains($lower, 'garage') => ['garage', 'roller door', 'opener', 'spring', 'remote'],
            str_contains($lower, 'handyman') => ['carpenter', 'assembly', 'drill', 'fixing', 'mounting', 'patch'],
            str_contains($lower, 'furnace') => ['furnace', 'heating', 'heater', 'boiler', 'burner'],
            str_contains($lower, 'hvac') => ['ac', 'air conditioning', 'cooling', 'hvac', 'duct', 'thermostat', 'filter'],
            str_contains($lower, 'electr') => ['electrician', 'wiring', 'switch', 'light', 'circuit', 'breaker', 'short circuit', 'socket', 'plug'],
            str_contains($lower, 'window') || str_contains($lower, 'door') => ['glass', 'hinge', 'frame', 'sliding door', 'window pane'],
            str_contains($lower, 'house clean') => ['maid', 'deep clean', 'mop', 'sweep', 'vacuum', 'dusting'],
            str_contains($lower, 'carpet clean') => ['rug', 'steam clean', 'stain removal', 'carpet shampoo'],
            str_contains($lower, 'upholstery') => ['sofa', 'couch', 'chair', 'cushion', 'fabric clean'],
            str_contains($lower, 'home organ') => ['declutter', 'closet', 'wardrobe', 'storage', 'cabinet'],
            str_contains($lower, 'deep clean') => ['move out', 'sanitization', 'scrubbing', 'disinfection'],
            str_contains($lower, 'junk') => ['trash', 'garbage', 'debris', 'waste', 'haul away'],
            str_contains($lower, 'vent') => ['air duct', 'dryer vent', 'exhaust', 'airflow'],
            str_contains($lower, 'pool') => ['pool', 'chlorine', 'skimmer', 'algae', 'filtration'],
            str_contains($lower, 'roof') => ['roof', 'leak', 'shingles', 'gutter', 'tiles'],
            str_contains($lower, 'paint') => ['paint', 'wall', 'roller', 'primer', 'color', 'painter'],
            str_contains($lower, 'bath') => ['shower', 'bathtub', 'tiles', 'vanity', 'restroom'],
            str_contains($lower, 'kitchen') => ['countertop', 'cabinets', 'sink', 'backsplash', 'island'],
            str_contains($lower, 'floor') => ['hardwood', 'laminate', 'vinyl', 'tiles', 'parquet'],
            str_contains($lower, 'carpenter') => ['woodwork', 'shelves', 'table', 'furniture', 'cabinetry'],
            str_contains($lower, 'lawn') || str_contains($lower, 'landscap') => ['grass', 'mowing', 'yard', 'edging', 'weed'],
            str_contains($lower, 'garden') => ['plants', 'flower', 'fertilizer', 'soil', 'pruning'],
            str_contains($lower, 'tree') => ['tree', 'branch', 'pruning', 'cutting', 'trimming'],
            str_contains($lower, 'mover') || str_contains($lower, 'moving') => ['relocation', 'truck', 'packing', 'boxes', 'transport'],
            str_contains($lower, 'pest') || str_contains($lower, 'mosquito') => ['mosquito', 'fogging', 'insect', 'larvae', 'spray'],
            str_contains($lower, 'rodent') => ['rats', 'mice', 'trap', 'rodent', 'poison'],
            str_contains($lower, 'tv') || str_contains($lower, 'mount') => ['television', 'wall bracket', 'mounting', 'screen'],
            str_contains($lower, 'camera') => ['cctv', 'surveillance', 'security camera', 'security'],
            default => array_values(array_filter(explode(' ', str_replace(['&', '/', '-'], ' ', $lower)))),
        };
    }
}
