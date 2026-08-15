<?php

namespace Tests\Feature;

use App\Models\TherapistDocument;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LegalTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_legal_pages_are_public_and_named(): void
    {
        foreach (config('legal.documents') as $slug => $document) {
            $this->get(route('legal.show', $slug))
                ->assertOk()
                ->assertSee($document['title'])
                ->assertSee(config('legal.version'));
        }
    }

    public function test_legal_page_visibly_warns_when_configuration_is_draft(): void
    {
        config(['legal.version' => 'DRAFT']);

        $this->get(route('legal.show', 'kebijakan-privasi'))
            ->assertOk()
            ->assertSee('Draf dokumen hukum — belum siap untuk produksi');
    }

    public function test_footer_links_to_every_legal_document(): void
    {
        $footer = view('partials.footer')->render();

        foreach (array_keys(config('legal.documents')) as $slug) {
            $this->assertStringContainsString(route('legal.show', $slug), $footer);
        }
    }

    public function test_only_admin_can_download_private_therapist_document(): void
    {
        Storage::fake('local');
        $therapist = User::factory()->create(['role' => 'therapist']);
        $profile = TherapistProfile::create([
            'user_id' => $therapist->id,
            'gender' => 'pria',
            'experience_years' => 2,
            'province' => 'Jawa Barat',
            'city' => 'Bandung',
            'serves_call' => true,
            'verification_status' => 'anggota',
        ]);
        Storage::disk('local')->put('therapist/dokumen/ktp.jpg', 'rahasia');
        $document = TherapistDocument::create([
            'therapist_profile_id' => $profile->id,
            'type' => 'ktp',
            'path' => 'therapist/dokumen/ktp.jpg',
        ]);

        $this->get(route('admin.document.download', $document))->assertRedirect(route('login'));
        $this->actingAs($therapist)->get(route('admin.document.download', $document))->assertRedirect(route('home'));

        // Berkas kini disajikan inline supaya admin bisa memeriksanya tanpa mengunduh;
        // yang dijaga tetap sama, yaitu hanya admin yang boleh mengaksesnya.
        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($admin)->get(route('admin.document.download', $document));
        $response->assertOk();
        $this->assertSame('rahasia', $response->streamedContent());
    }
}
