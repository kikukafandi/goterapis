<?php

namespace Tests\Feature;

use App\Events\ChatMessageSent;
use App\Models\Order;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    private function order(): Order
    {
        $customer = User::factory()->create(['role' => 'user']);
        $therapist = User::factory()->create(['role' => 'therapist']);
        $profile = TherapistProfile::create(['user_id' => $therapist->id, 'verification_status' => 'anggota', 'city' => 'Yogyakarta']);
        $service = Service::create(['name' => 'Pijat', 'slug' => fake()->unique()->slug(), 'category' => 'pijat']);

        return Order::create([
            'code' => fake()->unique()->bothify('GT-CHAT####'), 'user_id' => $customer->id, 'therapist_profile_id' => $profile->id,
            'service_id' => $service->id, 'model' => 'tempat', 'scheduled_at' => now()->addDay(),
            'duration_min' => 60, 'price' => 100000, 'transport_fee' => 0, 'service_fee' => 3000,
            'total' => 103000, 'commission' => 15000, 'payout' => 85000,
        ]);
    }

    public function test_pelanggan_dan_terapis_terkait_bisa_mengirim_pesan(): void
    {
        Event::fake([ChatMessageSent::class]);
        $order = $this->order();

        foreach ([$order->user, $order->therapistProfile->user] as $sender) {
            $this->actingAs($sender)->postJson(route('pesanan.chat.store', $order), ['body' => 'Halo'])->assertCreated()
                ->assertJsonPath('sender_id', $sender->id)->assertJsonPath('sender_name', $sender->name);
        }

        $this->assertSame(2, $order->messages()->count());
        Event::assertDispatched(ChatMessageSent::class, 2);
    }

    public function test_tamu_dan_pengguna_tidak_terkait_dilarang_mengirim(): void
    {
        $order = $this->order();
        $this->postJson(route('pesanan.chat.store', $order), ['body' => 'Halo'])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->postJson(route('pesanan.chat.store', $order), ['body' => 'Halo'])->assertForbidden();
        $this->assertSame(0, $order->messages()->count());
    }

    public function test_socket_id_kosong_tidak_menggagalkan_pesan(): void
    {
        $order = $this->order();

        $this->actingAs($order->user)
            ->withHeader('X-Socket-ID', '')
            ->postJson(route('pesanan.chat.store', $order), ['body' => 'Halo'])
            ->assertCreated();
    }

    public function test_isi_pesan_wajib(): void
    {
        $order = $this->order();

        $this->actingAs($order->user)->postJson(route('pesanan.chat.store', $order), ['body' => ''])->assertStatus(422);
    }

    public function test_isi_pesan_maksimal_seribu_karakter(): void
    {
        $order = $this->order();

        $this->actingAs($order->user)->postJson(route('pesanan.chat.store', $order), ['body' => str_repeat('a', 1001)])->assertStatus(422);
    }

    public function test_peserta_bisa_mengotorisasi_channel_pribadi(): void
    {
        $order = $this->order();

        $this->actingAs($order->user)->postJson('/broadcasting/auth', ['channel_name' => 'private-orders.'.$order->id, 'socket_id' => '1.1'])->assertOk();
    }

    public function test_bukan_peserta_dilarang_mengotorisasi_channel_pribadi(): void
    {
        $order = $this->order();

        $this->assertFalse($order->hasParticipant(User::factory()->create()));
    }

    public function test_inbox_mewajibkan_login_dan_admin_dilarang(): void
    {
        $this->get(route('chat'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('chat'))->assertForbidden();
    }

    public function test_inbox_membatasi_pesanan_untuk_peserta_dan_menampilkan_pesanan_tanpa_pesan(): void
    {
        $first = $this->order();
        $second = $this->order();

        $this->actingAs($first->user)->get(route('chat'))
            ->assertOk()->assertSee($first->therapistProfile->user->name)->assertDontSee($second->therapistProfile->user->name)
            ->assertSee('Belum ada pesan. Ketuk untuk memulai chat.');
        $this->actingAs($first->therapistProfile->user)->get(route('chat'))
            ->assertOk()->assertSee('Pesan')->assertSee($first->user->name)->assertDontSee($second->user->name);
        $this->actingAs($first->user)->get(route('chat'))
            ->assertOk()->assertSee('Percakapan')->assertDontSee('Percakapan pelanggan akan muncul');
        $this->actingAs(User::factory()->create(['role' => 'therapist']))->get(route('chat'))
            ->assertOk()->assertSee('Belum ada percakapan');
    }

    public function test_inbox_menghitung_hanya_pesan_masuk_yang_belum_dibaca(): void
    {
        $order = $this->order();
        $therapist = $order->therapistProfile->user;
        $order->messages()->createMany([
            ['sender_id' => $therapist->id, 'body' => 'Pesan baru'],
            ['sender_id' => $therapist->id, 'body' => 'Sudah dibaca', 'read_at' => now()],
            ['sender_id' => $order->user_id, 'body' => 'Pesan sendiri'],
        ]);

        $this->actingAs($order->user)->get(route('chat'))
            ->assertOk()->assertSee('1 pesan baru')->assertSee('Pesan sendiri');
    }

    public function test_inbox_terapis_menampilkan_pesanan_sebagai_pembeli_dan_penjual_dengan_tautan_tepat(): void
    {
        $soldOrder = $this->order();
        $therapist = $soldOrder->therapistProfile->user;
        $boughtOrder = $this->order();
        $boughtOrder->update(['user_id' => $therapist->id]);

        $this->actingAs($therapist)->get(route('chat'))
            ->assertOk()
            ->assertSee($soldOrder->user->name)
            ->assertSee($boughtOrder->therapistProfile->user->name)
            ->assertSee(route('mitra.pesanan.show', $soldOrder))
            ->assertSee(route('pesanan.show', $boughtOrder));

        $this->actingAs($therapist)->get(route('pesanan.show', $boughtOrder))
            ->assertOk()
            ->assertSee(route('pesanan.show', $boughtOrder));
    }

    public function test_chat_di_detail_pesanan_tidak_menampilkan_tautan_ke_halaman_yang_sama(): void
    {
        $order = $this->order();

        $this->actingAs($order->user)->get(route('pesanan.show', $order))
            ->assertOk()
            ->assertSee($order->code)
            ->assertSee($order->service->name)
            ->assertDontSee('Lihat detail pesanan');
    }

    public function test_membuka_detail_menandai_hanya_pesan_masuk_dan_akses_tidak_sah_tidak_mengubahnya(): void
    {
        $order = $this->order();
        $customerMessage = $order->messages()->create(['sender_id' => $order->user_id, 'body' => 'Dari pelanggan']);
        $therapistMessage = $order->messages()->create(['sender_id' => $order->therapistProfile->user_id, 'body' => 'Dari terapis']);
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('pesanan.show', $order))->assertRedirect(route('pesanan.index'));
        $this->assertNull($therapistMessage->fresh()->read_at);

        $this->actingAs($order->user)->get(route('pesanan.show', $order))->assertOk();
        $this->assertNotNull($therapistMessage->fresh()->read_at);
        $this->assertNull($customerMessage->fresh()->read_at);

        $customerMessage->update(['read_at' => null]);
        $this->actingAs($order->therapistProfile->user)->get(route('mitra.pesanan.show', $order))->assertOk();
        $this->assertNotNull($customerMessage->fresh()->read_at);
    }
}
