<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    /** Minta kode OTP lewat gateway WhatsApp palsu lalu baca kodenya dari pesan yang terkirim. */
    protected function kodeOtp(User $user, string $purpose): string
    {
        config(['services.whatsapp.url' => 'http://whatsapp.test', 'services.whatsapp.token' => 'rahasia']);
        Http::fake(['http://whatsapp.test/messages' => Http::response([], 201)]);

        $this->actingAs($user)->post(route('mitra.otp.send'), ['purpose' => $purpose])->assertSessionHasNoErrors();

        $code = null;
        Http::assertSent(function ($request) use (&$code) {
            preg_match('/(\d{6})/', (string) $request['message'], $matches);
            $code = $matches[1] ?? $code;

            return true;
        });

        return (string) $code;
    }
}
