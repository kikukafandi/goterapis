<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserBan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $users = User::with('activeBan')
            ->when($search, fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users', 'search'));
    }

    public function ban(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'duration' => ['required', Rule::in(['1', '7', '30', 'permanent'])],
            'reason' => ['required', 'string', 'max:2000'],
        ]);
        $this->ensureBannable($request, $user);

        DB::transaction(function () use ($request, $user, $data) {
            $user = User::lockForUpdate()->findOrFail($user->id);
            $this->ensureBannable($request, $user);
            UserBan::create([
                'user_id' => $user->id,
                'actor_id' => $request->user()->id,
                'reason' => $data['reason'],
                'expires_at' => $data['duration'] === 'permanent' ? null : now()->addDays((int) $data['duration']),
            ]);
            $user->therapistProfile()->update(['is_available' => false, 'is_featured' => false]);
        });

        return back()->with('ok', 'Pengguna berhasil diblokir.');
    }

    public function unban(Request $request, User $user): RedirectResponse
    {
        $this->ensureBannable($request, $user);
        $user->activeBan()->update(['unbanned_at' => now(), 'unbanned_by' => $request->user()->id]);

        return back()->with('ok', 'Blokir pengguna dicabut.');
    }

    private function ensureBannable(Request $request, User $user): void
    {
        if ($user->id === $request->user()->id || $user->role === 'admin') {
            throw ValidationException::withMessages(['user' => 'Admin tidak dapat memblokir diri sendiri atau admin lain.']);
        }
    }
}
