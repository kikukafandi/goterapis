<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ShopCartController extends Controller
{
    public function index(Request $request): View
    {
        return view('shop.cart', ['items' => CartItem::whereBelongsTo($request->user())->with('product')->get()]);
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        abort_unless(Product::published()->whereKey($product)->exists(), 404);
        $quantity = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']])['quantity'];
        $added = DB::transaction(function () use ($request, $product, $quantity) {
            $product = Product::published()->whereKey($product)->lockForUpdate()->first();
            if ($product === null) {
                return null;
            }
            $item = CartItem::where('user_id', $request->user()->id)->where('product_id', $product->id)->lockForUpdate()->first();
            $total = ($item?->quantity ?? 0) + $quantity;
            if ($total > 99 || $total > $product->stock) {
                return false;
            }
            CartItem::updateOrCreate(['user_id' => $request->user()->id, 'product_id' => $product->id], ['quantity' => $total]);

            return true;
        });

        abort_if($added === null, 404);

        return $added ? redirect()->route('shop.cart')->with('success', 'Produk ditambahkan ke keranjang.') : back()->with('error', 'Jumlah keranjang melebihi stok produk.');
    }

    public function update(Request $request, CartItem $item): RedirectResponse
    {
        abort_unless($item->user_id === $request->user()->id, 404);
        $quantity = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']])['quantity'];
        if ($item->product->stock < $quantity) {
            return back()->with('error', 'Stok produk tidak mencukupi.');
        }
        $item->update(['quantity' => $quantity]);

        return back()->with('success', 'Keranjang diperbarui.');
    }

    public function destroy(Request $request, CartItem $item): RedirectResponse
    {
        abort_unless($item->user_id === $request->user()->id, 404);
        $item->delete();

        return back()->with('success', 'Produk dihapus dari keranjang.');
    }
}
