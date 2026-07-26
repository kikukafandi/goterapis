<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'category' => fake()->randomElement(array_keys(Product::CATEGORIES)),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(10000, 500000),
            'stock' => fake()->numberBetween(0, 100),
            'weight_grams' => fake()->numberBetween(50, 2000),
            'origin' => fake()->city(),
            'storage_instructions' => fake()->sentence(),
            'is_promoted' => false,
            'status' => 'published',
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft', 'published_at' => null]);
    }

    public function promoted(): static
    {
        return $this->state(['is_promoted' => true]);
    }
}
