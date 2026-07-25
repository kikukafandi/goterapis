<?php

namespace Tests\Feature;

use App\Support\Pricing;
use Tests\TestCase;

class PricingTest extends TestCase
{
    public function test_breakdown_computes_commission_total_and_payout(): void
    {
        config([
            'goterapis.commission_percent' => 15,
            'goterapis.service_fee' => 3000,
        ]);

        $b = Pricing::breakdown(price: 100_000, transportFee: 15_000);

        $this->assertSame(15_000, $b['commission']);          // 15% dari harga
        $this->assertSame(118_000, $b['total']);              // harga+transport+biaya layanan
        $this->assertSame(100_000, $b['payout']);             // harga+transport-komisi
    }

    public function test_last_minute_cancellation_charges_compensation(): void
    {
        config([
            'goterapis.cancel_free_hours' => 2,
            'goterapis.cancel_compensation_percent' => 50,
        ]);

        $now = new \DateTimeImmutable('2026-07-20 10:00:00');

        // 1 jam sebelum jadwal → dalam jendela larangan → kompensasi 50%
        $this->assertSame(
            50_000,
            Pricing::cancellationCompensation(100_000, new \DateTimeImmutable('2026-07-20 11:00:00'), $now),
        );

        // 5 jam sebelum jadwal → batal gratis
        $this->assertSame(
            0,
            Pricing::cancellationCompensation(100_000, new \DateTimeImmutable('2026-07-20 15:00:00'), $now),
        );
    }

    public function test_refund_menahan_biaya_layanan_dan_menghitung_kompensasi(): void
    {
        config(['goterapis.cancel_free_hours' => 2, 'goterapis.cancel_compensation_percent' => 50]);
        $now = new \DateTimeImmutable('2026-07-20 10:00:00');

        // Belum dibayar → tak ada dana berpindah
        $this->assertSame(
            ['refund' => 0, 'compensation' => 0, 'fee_kept' => 0],
            Pricing::cancellationRefund(100_000, 15_000, 3_000, paid: false, scheduledAt: new \DateTimeImmutable('2026-07-20 15:00:00'), byTherapist: false, now: $now),
        );

        // Pelanggan batal jauh hari → refund harga+transport, biaya layanan ditahan
        $this->assertSame(
            ['refund' => 115_000, 'compensation' => 0, 'fee_kept' => 3_000],
            Pricing::cancellationRefund(100_000, 15_000, 3_000, paid: true, scheduledAt: new \DateTimeImmutable('2026-07-20 15:00:00'), byTherapist: false, now: $now),
        );

        // Pelanggan batal mendadak → dipotong kompensasi 50% harga
        $this->assertSame(
            ['refund' => 65_000, 'compensation' => 50_000, 'fee_kept' => 3_000],
            Pricing::cancellationRefund(100_000, 15_000, 3_000, paid: true, scheduledAt: new \DateTimeImmutable('2026-07-20 11:00:00'), byTherapist: false, now: $now),
        );

        // Terapis yang batal → pelanggan dapat kembali seluruh yang dibayar
        $this->assertSame(
            ['refund' => 118_000, 'compensation' => 0, 'fee_kept' => 0],
            Pricing::cancellationRefund(100_000, 15_000, 3_000, paid: true, scheduledAt: new \DateTimeImmutable('2026-07-20 11:00:00'), byTherapist: true, now: $now),
        );
    }
}
