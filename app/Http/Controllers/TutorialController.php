<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class TutorialController extends Controller
{
    public function __invoke(Request $request): View
    {
        $request->user()->update(['tutorial_seen_at' => now()]);

        return view('tutorial');
    }
}
