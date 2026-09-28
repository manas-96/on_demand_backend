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
}
