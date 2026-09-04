<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    public function test_semua_halaman_error_dapat_dirender(): void
    {
        foreach ([401, 403, 404, 419, 429, 500, 503] as $status) {
            $html = view("errors.{$status}")->render();

            $this->assertStringContainsString("Kesalahan {$status}", $html);
            $this->assertStringContainsString(route('home'), $html);
        }
    }
}
