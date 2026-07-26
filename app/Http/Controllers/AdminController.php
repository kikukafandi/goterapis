<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\PromotionBanner;
use App\Models\Report;
use App\Models\TherapistDocument;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'users' => User::where('role', 'user')->count(),
            'therapists' => TherapistProfile::count(),
            'pending_docs' => TherapistDocument::where('status', 'pending')->count(),
            'orders' => Order::count(),
            'products' => Product::count(),
            'active_banners' => PromotionBanner::visible()->count(),
            'open_reports' => Report::where('status', 'open')->count(),
        ];

        $latest = TherapistProfile::with('user')
            ->withCount(['documents as pending_count' => fn ($q) => $q->where('status', 'pending')])
            ->latest()
            ->take(6)
            ->get();

        return view('admin.dashboard', compact('stats', 'latest'));
    }

    public function therapists(Request $request)
    {
        $therapists = TherapistProfile::with('user')
            ->withCount(['documents as pending_count' => fn ($q) => $q->where('status', 'pending')])
            ->when($request->status, fn ($q, $s) => $q->where('verification_status', $s))
            ->when($request->q, fn ($q, $term) => $q->whereHas('user',
                fn ($u) => $u->where('name', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.therapists.index', compact('therapists'));
    }

    public function therapist(TherapistProfile $therapist)
    {
        $therapist->load('user', 'documents', 'services');

        return view('admin.therapists.show', compact('therapist'));
    }

    /** Setujui / tolak satu dokumen. */
    public function reviewDocument(Request $request, TherapistDocument $document)
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $document->update($data);

        return back()->with('ok', 'Dokumen diperbarui.');
    }

    /** Naikkan/ubah status verifikasi terapis. */
    public function updateStatus(Request $request, TherapistProfile $therapist)
    {
        $data = $request->validate([
            'verification_status' => ['required', 'in:anggota,identitas,berpengalaman,terdaftar,pilihan'],
            'is_featured' => ['sometimes', 'boolean'],
        ]);

        $therapist->update([
            'verification_status' => $data['verification_status'],
            'is_featured' => $request->boolean('is_featured'),
        ]);

        return back()->with('ok', 'Status terapis diperbarui.');
    }
}
