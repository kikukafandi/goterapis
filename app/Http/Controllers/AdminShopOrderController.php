<?php

namespace App\Http\Controllers;

use App\Models\ShopOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminShopOrderController extends Controller
{
    public function index(): View
    {
        return view('admin.shop-orders.index', ['orders' => ShopOrder::with('user')->latest()->paginate(20)]);
    }

    public function show(ShopOrder $shopOrder): View
    {
        return view('admin.shop-orders.show', ['order' => $shopOrder->load(['user', 'items', 'payment'])]);
    }

    public function shipping(Request $request, ShopOrder $shopOrder): RedirectResponse
    {
        $cost = $request->validate(['shipping_cost' => ['required', 'integer', 'min:0', 'max:10000000']])['shipping_cost'];
        $changed = DB::transaction(fn () => ShopOrder::whereKey($shopOrder)->where('status', 'waiting_shipping')->lockForUpdate()->update([
            'shipping_cost' => $cost,
            'total' => $shopOrder->subtotal + $cost,
            'status' => 'pending_payment',
            'updated_at' => now(),
        ]));

        return $changed ? back()->with('success', 'Ongkos kirim ditetapkan. Pelanggan sudah dapat membayar.') : back()->with('error', 'Ongkos kirim tidak dapat diubah pada status ini.');
    }

    public function process(ShopOrder $shopOrder): RedirectResponse
    {
        $changed = DB::transaction(fn () => ShopOrder::whereKey($shopOrder)->where('status', 'paid')->lockForUpdate()->update(['status' => 'processing', 'updated_at' => now()]));

        return $changed ? back()->with('success', 'Pesanan sedang diproses.') : back()->with('error', 'Hanya pesanan lunas yang dapat diproses.');
    }

    public function ship(Request $request, ShopOrder $shopOrder): RedirectResponse
    {
        $data = $request->validate(['courier' => ['required', 'string', 'max:100'], 'tracking_number' => ['required', 'string', 'max:100']]);
        $changed = DB::transaction(fn () => ShopOrder::whereKey($shopOrder)->where('status', 'processing')->lockForUpdate()->update([...$data, 'status' => 'shipped', 'shipped_at' => now(), 'updated_at' => now()]));

        return $changed ? back()->with('success', 'Pesanan ditandai sudah dikirim.') : back()->with('error', 'Pesanan harus diproses sebelum dikirim.');
    }
}
