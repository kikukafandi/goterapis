<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GenderController extends Controller
{
    /** Sekali isi, tidak bisa diubah sendiri — menghindari akal-akalan menembus batas sesama jenis. */
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate(['gender' => ['required', 'in:pria,wanita']]);
        $user = $request->user();

        if ($user->gender === null) {
            $user->update(['gender' => $data['gender']]);
        }

        return back()->with('success', 'Jenis kelamin tersimpan.');
    }
}
