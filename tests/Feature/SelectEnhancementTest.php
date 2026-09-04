<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class SelectEnhancementTest extends TestCase
{
    public function test_semua_select_blade_tercakup_enhancement_atau_fallback_native(): void
    {
        $views = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views')));
        $selects = [];

        foreach ($views as $view) {
            if ($view->isFile() && str_ends_with($view->getFilename(), '.blade.php')) {
                preg_match_all('/<select\b[^>]*>/i', file_get_contents($view->getPathname()), $matches);
                array_push($selects, ...$matches[0]);
            }
        }

        $javascript = file_get_contents(resource_path('js/app.js'));

        $this->assertCount(15, $selects);
        $this->assertEmpty(array_filter($selects, fn (string $select): bool => str_contains($select, 'multiple')));
        $this->assertStringContainsString('select:not([multiple]):not([data-native-select])', $javascript);
        $this->assertStringContainsString("dispatchEvent(new Event('change', { bubbles: true }))", $javascript);
        $this->assertStringContainsString('@keydown.arrow-down.prevent', $javascript);
        $this->assertStringContainsString('@keydown.escape.prevent', $javascript);
        $this->assertStringContainsString('@click.outside', $javascript);
        $this->assertStringContainsString('@click.stop="toggle"', $javascript);
        $this->assertStringNotContainsString('@click="toggle"', $javascript);
        $this->assertStringNotContainsString('wrapper.className = select.className', $javascript);
        $this->assertStringNotContainsString('wrapper.style.cssText = select.style.cssText', $javascript);
        $this->assertStringContainsString("wrapper.classList.add('select-dropdown', ...layoutClasses)", $javascript);
        $this->assertStringContainsString("classes.includes('isian')", $javascript);
        $this->assertStringContainsString("layoutClasses.push('w-full')", $javascript);
        $this->assertStringContainsString("className !== 'appearance-none'", $javascript);
        $this->assertStringContainsString("class=\"select-dropdown__button \${fieldClasses.join(' ')}\"", $javascript);
        $this->assertStringNotContainsString("wrapper.classList.add('isian'", $javascript);
        $this->assertStringContainsString("classList.toggle('is-right-aligned'", $javascript);

        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringNotContainsString("width: 100%;\n        color: var(--color-arang);", $css);
        $this->assertStringContainsString('.select-dropdown__list.is-right-aligned', $css);
        $this->assertSame(1, substr_count($css, '.select-dropdown__button::after'));
        $this->assertStringContainsString(".select-dropdown__button {\n        position: relative;", $css);
        $this->assertMatchesRegularExpression('/\\.select-dropdown__native\\s*\\{[^}]*pointer-events:\\s*none;/s', $css);
        $this->assertStringNotContainsString('.select-dropdown::after', $css);
    }
}
