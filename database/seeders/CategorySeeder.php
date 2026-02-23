<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\CategoryService;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryservice = app(CategoryService::class);
        // create categories
        $creme = $categoryservice->create([
            'name' => 'Creme',
        ]);
        $postiche = $categoryservice->create([
            'name' => 'POSTICHE',
        ]);

        //
        $categories = [
            [
                'name' => 'Natural look',
                'parent_id' => $postiche->id,

            ],
            [
                'name' => 'Yaki Braid',
                'parent_id' => $postiche->id,
            ],
            [
                'name' => 'Extra long',
                'parent_id' => $postiche->id,
            ],
        ];
        $cremeCategoryList = [
            [
                'name' => 'Shampoing Amalfi 750',
                'parent_id' => $creme->id,

            ],
            [
                'name' => 'Gel douche Amalfi 750',
                'parent_id' => $creme->id,
            ],

        ];

        foreach ($categories as $category) {
            $categoryservice->create($category);
        }
        foreach ($cremeCategoryList as $category) {
            $categoryservice->create($category);
        }
    }
}
