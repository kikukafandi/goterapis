<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class BadgeTest extends TestCase
{
    public function test_slug_renders_matching_badge_image(): void
    {
        $html = Blade::render('<x-badge status="pilihan" />');

        $this->assertStringContainsString('images/badges/badge_terapis_pilihan.webp', $html);
        $this->assertStringContainsString('Terapis Pilihan', $html);
        $this->assertFileExists(public_path('images/badges/badge_terapis_pilihan.webp'));
    }

    public function test_label_is_resolved_to_the_same_badge(): void
    {
        $html = Blade::render('<x-badge status="Terapis Berpengalaman" />');

        $this->assertStringContainsString('badge_terapis_berpengalaman.webp', $html);
    }

    public function test_unknown_status_renders_nothing(): void
    {
        $html = Blade::render('<x-badge status="tidak-ada" />');

        $this->assertStringNotContainsString('badge_', $html);
    }
}
