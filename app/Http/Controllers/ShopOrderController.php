<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ShopOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ShopOrderController extends Controller
{
    public function __construct(private PaymentGateway $gateway) {}

    public function index(Request $request): View
    {
        return view('shop.orders.index', ['orders' => ShopOrder::whereBelongsTo($request->user())->latest()->paginate(15)]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $items = CartItem::whereBelongsTo($request->user())->with('product')->get();

        return $items->isEmpty() ? redirect()->route('shop.cart')->with('error', 'Keranjang masih kosong.') : view('shop.checkout', compact('items'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:1000'],
            'city' => ['required', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:10'],
        ]);

        $result = DB::transaction(function () use ($request, $data) {
            $cart = CartItem::whereBelongsTo($request->user())->lockForUpdate()->get();
            if ($cart->isEmpty()) {
                return null;
            }
            $products = Product::published()->whereKey($cart->pluck('product_id'))->lockForUpdate()->get()->keyBy('id');
            foreach ($cart as $item) {
                if (! isset($products[$item->product_id]) || $products[$item->product_id]->stock < $item->quantity) {
                    return false;
                }
            }

            $subtotal = $cart->sum(fn (CartItem $item) => $products[$item->product_id]->price * $item->quantity);
            $order = ShopOrder::create([...$data, 'code' => 'GT-SHOP-'.Str::upper(Str::random(10)), 'user_id' => $request->user()->id, 'subtotal' => $subtotal]);
            foreach ($cart as $item) {
                $product = $products[$item->product_id];
                $product->decrement('stock', $item->quantity);
                $order->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'price' => $product->price, 'quantity' => $item->quantity, 'subtotal' => $product->price * $item->quantity]);
            }
            CartItem::whereKey($cart->pluck('id'))->delete();

            return $order;
        });

        if ($result === false) {
            return back()->withInput()->with('error', 'Stok berubah dan tidak lagi mencukupi. Periksa keranjang Anda.');
        }
        if ($result === null) {
            return redirect()->route('shop.cart')->with('error', 'Keranjang masih kosong.');
        }

        return redirect()->route('shop.orders.show', $result)->with('success', 'Pesanan dibuat. Admin akan menetapkan ongkos kirim.');
    }

    public function show(Request $request, ShopOrder $shopOrder): View
    {
        abort_unless($shopOrder->user_id === $request->user()->id, 404);

        return view('shop.orders.show', ['order' => $shopOrder->load(['items', 'payment'])]);
    }

    public function pay(Request $request, ShopOrder $shopOrder): RedirectResponse
    {
        abort_unless($shopOrder->user_id === $request->user()->id, 404);
        $result = DB::transaction(function () use ($shopOrder) {
            $order = ShopOrder::whereKey($shopOrder)->lockForUpdate()->firstOrFail();
            if ($order->status !== 'pending_payment' || $order->total === null) {
                return false;
            }
            $redirect = $this->gateway->pay($order);
            if ($redirect === null) {
                $order->update(['status' => 'paid', 'paid_at' => now()]);
            }

            return $redirect;
        });

        if ($result === false) {
            return back()->with('error', 'Pesanan ini belum dapat dibayar.');
        }

        return $result ? redirect()->away($result) : back()->with('success', 'Pembayaran berhasil. Pesanan akan diproses.');
    }
}
