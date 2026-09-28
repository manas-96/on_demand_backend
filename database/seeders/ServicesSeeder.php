<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Plumbing & Drainage',
                'image' => 'https://images.unsplash.com/photo-1585704032915-c3400ca199e7?w=600&auto=format&fit=crop&q=80',
                'services' => [
                    ['name' => 'Pipe Leakage Repair', 'base_price' => 500.00],
                    ['name' => 'Drain Cleaning & Unclogging', 'base_price' => 750.00],
                    ['name' => 'Water Heater Installation', 'base_price' => 2500.00],
                ],
            ],
            [
                'name' => 'Electrical Services',
                'image' => 'https://images.unsplash.com/photo-1621905251189-08b45d6a269e?w=600&auto=format&fit=crop&q=80',
                'services' => [
                    ['name' => 'Circuit Breaker Repair', 'base_price' => 600.00],
                    ['name' => 'Light Fixture Installation', 'base_price' => 400.00],
                    ['name' => 'Full House Rewiring Inspection', 'base_price' => 3000.00],
                ],
            ],
            [
                'name' => 'HVAC & Air Conditioning',
                'image' => 'https://images.unsplash.com/photo-1631545806607-e85871fbc0fe?w=600&auto=format&fit=crop&q=80',
                'services' => [
                    ['name' => 'AC Servicing & Cleaning', 'base_price' => 1200.00],
                    ['name' => 'Gas Refill & Leak Fix', 'base_price' => 1800.00],
                ],
            ],
            [
                'name' => 'Carpentry & Handyman',
                'image' => 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=600&auto=format&fit=crop&q=80',
                'services' => [
                    ['name' => 'Custom Cabinet Repair & Fitting', 'base_price' => 400.00],
                    ['name' => 'Door Lock & Security Hardware Fix', 'base_price' => 250.00],
                ],
            ],
            [
                'name' => 'House Cleaning & Sanitization',
                'image' => 'https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=600&auto=format&fit=crop&q=80',
                'services' => [
                    ['name' => 'Deep Home Cleaning', 'base_price' => 800.00],
                    ['name' => 'Sofa & Carpet Sanitization', 'base_price' => 500.00],
                ],
            ],
            [
                'name' => 'Appliance Repair',
                'image' => 'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?w=600&auto=format&fit=crop&q=80',
                'services' => [
                    ['name' => 'Washing Machine Repair', 'base_price' => 650.00],
                    ['name' => 'Refrigerator Servicing', 'base_price' => 900.00],
                ],
            ],
        ];

        foreach ($categories as $catData) {
            $category = ServiceCategory::create([
                'name' => $catData['name'],
                'slug' => Str::slug($catData['name']),
                'image' => $catData['image'],
                'is_active' => true,
            ]);

            foreach ($catData['services'] as $servData) {
                Service::create([
                    'service_category_id' => $category->id,
                    'name' => $servData['name'],
                    'slug' => Str::slug($servData['name']),
                    'description' => 'Professional '.$servData['name'].' by certified technicians.',
                    'base_price' => $servData['base_price'],
                    'is_active' => true,
                ]);
            }
        }
    }
}
