<?php

namespace Tests\Feature;

use App\Models\TherapistDocument;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TherapistDocumentReplacementTest extends TestCase
{
    use RefreshDatabase;

    public function test_hanya_pemilik_dapat_mengganti_dokumen(): void
    {
        Storage::fake('local');
        [, , $document] = $this->document();
        $other = User::factory()->create(['role' => 'therapist']);
        TherapistProfile::create(['user_id' => $other->id]);
        $this->actingAs($other)->put(route('mitra.dokumen.replace', $document), ['document' => UploadedFile::fake()->image('baru.jpg')])->assertForbidden();
    }

    public function test_dokumen_approved_dan_pending_tidak_dapat_diganti(): void
    {
        Storage::fake('local');
        foreach (['approved', 'pending'] as $status) {
            [$user, , $document] = $this->document($status);
            $this->actingAs($user)->put(route('mitra.dokumen.replace', $document), ['document' => UploadedFile::fake()->image('baru.jpg')])->assertStatus(422);
        }
    }

    public function test_penggantian_membersihkan_storage_dan_mereset_status_serta_eligibility(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('dokumen/lama.jpg', 'lama');
        [$user, $profile, $document] = $this->document();
        $this->actingAs($user)->put(route('mitra.dokumen.replace', $document), ['document' => UploadedFile::fake()->image('baru.jpg', 800, 800)])->assertRedirect()->assertSessionHas('success');
        $document->refresh();
        Storage::disk('local')->assertExists($document->path);
        Storage::disk('local')->assertMissing('dokumen/lama.jpg');
        $this->assertSame('pending', $document->status);
        $this->assertNull($document->note);
        $profile->refresh();
        $this->assertSame('anggota', $profile->verification_status);
        $this->assertFalse($profile->is_available);
        $this->assertFalse($profile->is_featured);
        $this->assertFalse($profile->isEligible());
        $this->assertFalse(TherapistProfile::eligible()->whereKey($profile)->exists());
    }

    public function test_file_pengganti_divalidasi_ketat(): void
    {
        Storage::fake('local');
        [$user, , $document] = $this->document();
        $this->actingAs($user)->put(route('mitra.dokumen.replace', $document), ['document' => UploadedFile::fake()->create('dokumen.exe', 10, 'application/octet-stream')])->assertSessionHasErrors('document');
        $this->assertSame('rejected', $document->fresh()->status);
    }

    private function document(string $status = 'rejected'): array
    {
        $user = User::factory()->create(['role' => 'therapist']);
        $profile = TherapistProfile::create(['user_id' => $user->id, 'verification_status' => 'identitas', 'is_available' => true, 'is_featured' => true]);
        $document = TherapistDocument::create(['therapist_profile_id' => $profile->id, 'type' => 'ktp', 'path' => 'dokumen/lama.jpg', 'status' => $status, 'note' => 'Buram']);

        return [$user, $profile, $document];
    }
}
