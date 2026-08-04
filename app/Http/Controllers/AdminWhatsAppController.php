<?php

namespace App\Http\Controllers;

use App\Support\WhatsAppGateway;
use Illuminate\Contracts\View\View;

class AdminWhatsAppController extends Controller
{
    public function __invoke(WhatsAppGateway $gateway): View
    {
        return view('admin.whatsapp', ['gateway' => $gateway->status()]);
    }
}
