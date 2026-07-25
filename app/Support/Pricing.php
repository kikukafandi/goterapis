<?php

namespace App\Support;

/**
 * Rincian biaya pesanan. Sumber tunggal kebenaran angka uang.
 * Komisi dihitung dari harga layanan saja; transport diteruskan penuh ke terapis.
 */
class Pricing
{
    /**
     * @return array{price:int,transport_fee:int,service_fee:int,total:int,commission:int,payout:int}
     */
    public static function breakdown(int $price, int $transportFee = 0): array
    {
        $serviceFee = (int) config('goterapis.service_fee');
        $commission = (int) round($price * config('goterapis.commission_percent') / 100);

        return [
            'price' => $price,
            'transport_fee' => $transportFee,
            'service_fee' => $serviceFee,
            'total' => $price + $transportFee + $serviceFee, // dibayar pengguna
            'commission' => $commission,
            'payout' => $price + $transportFee - $commission, // diterima terapis
        ];
    }

    /** Kompensasi terapis saat pembatalan mendadak (dalam jendela larangan). */
    public static function cancellationCompensation(int $price, \DateTimeInterface $scheduledAt, ?\DateTimeInterface $now = null): int
    {
        $now ??= new \DateTimeImmutable();
        $hoursLeft = ($scheduledAt->getTimestamp() - $now->getTimestamp()) / 3600;

        if ($hoursLeft > config('goterapis.cancel_free_hours')) {
            return 0; // batal gratis
        }

        return (int) round($price * config('goterapis.cancel_compensation_percent') / 100);
    }

    /**
     * Rincian pengembalian dana saat pembatalan.
     * Biaya layanan TIDAK dikembalikan (menutup fee payment gateway & operasional).
     *
     * @return array{refund:int,compensation:int,fee_kept:int}
     */
    public static function cancellationRefund(
        int $price,
        int $transportFee,
        int $serviceFee,
        bool $paid,
        \DateTimeInterface $scheduledAt,
        bool $byTherapist,
        ?\DateTimeInterface $now = null,
    ): array {
        // Belum dibayar → tak ada dana yang berpindah.
        if (! $paid) {
            return ['refund' => 0, 'compensation' => 0, 'fee_kept' => 0];
        }

        // Terapis yang membatalkan → pelanggan tidak dirugikan, kembalikan seluruh yang dibayar.
        if ($byTherapist) {
            return ['refund' => $price + $transportFee + $serviceFee, 'compensation' => 0, 'fee_kept' => 0];
        }

        // Pelanggan membatalkan → biaya layanan ditahan; kompensasi terapis bila mendadak.
        $compensation = self::cancellationCompensation($price, $scheduledAt, $now);

        return [
            'refund' => $price + $transportFee - $compensation,
            'compensation' => $compensation,
            'fee_kept' => $serviceFee,
        ];
    }
}
