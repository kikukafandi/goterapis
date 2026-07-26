<?php

namespace Database\Factories;

use App\Models\PromotionBanner;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PromotionBanner> */
class PromotionBannerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'image_path' => 'banner-promosi/'.$this->faker->uuid().'.webp',
            'title' => $this->faker->sentence(5),
            'subtitle' => $this->faker->sentence(),
            'cta_label' => null,
            'cta_url' => null,
            'is_active' => true,
            'sort_order' => 0,
            'starts_at' => null,
            'ends_at' => null,
        ];
    }
}
