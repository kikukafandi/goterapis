<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ShopOrder;
use App\Models\User;
use App\Support\Payment\SimulatedGateway;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_checkout_account_cart_and_stock_is_decremented(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['price' => 45_000, 'stock' => 3]);
        CartItem::create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 2]);

        $this->actingAs($user)->post(route('shop.orders.store'), [
            'recipient_name' => 'Budi', 'phone' => '081234567890', 'address' => 'Jalan Mawar 1', 'city' => 'Solo', 'postal_code' => '57100',
        ])->assertRedirect();

        $order = ShopOrder::firstOrFail();
        $this->assertSame('waiting_shipping', $order->status);
        $this->assertSame(90_000, $order->subtotal);
        $this->assertSame(1, $product->fresh()->stock);
        $this->assertSame(0, CartItem::count());
        $this->assertSame('Budi', $order->recipient_name);
    }

    public function test_checkout_rejects_insufficient_stock_without_partial_changes(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 1]);
        CartItem::create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 2]);

        $this->actingAs($user)->from(route('shop.checkout'))->post(route('shop.orders.store'), [
            'recipient_name' => 'Budi', 'phone' => '0812', 'address' => 'Jalan Mawar', 'city' => 'Solo',
        ])->assertRedirect(route('shop.checkout'));

        $this->assertSame(0, ShopOrder::count());
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_admin_sets_shipping_before_customer_pays_then_processes_and_ships(): void
    {
        config(['goterapis.gateway' => 'simulasi']);
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $order = ShopOrder::create(['code' => 'GT-SHOP-TEST', 'user_id' => $user->id, 'recipient_name' => 'Budi', 'phone' => '0812', 'address' => 'Jalan Mawar', 'city' => 'Solo', 'subtotal' => 100_000]);

        $this->actingAs($user)->post(route('shop.orders.pay', $order))->assertSessionHas('error');
        $this->actingAs($admin)->patch(route('admin.shop-orders.shipping', $order), ['shipping_cost' => 20_000])->assertSessionHas('success');
        $this->actingAs($user)->post(route('shop.orders.pay', $order))->assertSessionHas('success');
        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame(120_000, $order->payment->amount);

        $this->actingAs($admin)->patch(route('admin.shop-orders.process', $order))->assertSessionHas('success');
        $this->actingAs($admin)->patch(route('admin.shop-orders.ship', $order), ['courier' => 'JNE', 'tracking_number' => 'RESI123'])->assertSessionHas('success');
        $order->refresh();
        $this->assertSame('shipped', $order->status);
        $this->assertSame('RESI123', $order->tracking_number);
    }

    public function test_cart_add_validates_cumulative_quantity_atomically(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 3]);

        $this->actingAs($user)->post(route('shop.cart.store', $product), ['quantity' => 2])->assertSessionHas('success');
        $this->actingAs($user)->post(route('shop.cart.store', $product), ['quantity' => 2])->assertSessionHas('error');

        $this->assertSame(2, CartItem::firstOrFail()->quantity);
    }

    public function test_checkout_rejects_product_unpublished_after_it_was_added(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 3]);
        CartItem::create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 1]);
        $product->update(['status' => 'draft', 'published_at' => null]);

        $this->actingAs($user)->post(route('shop.orders.store'), [
            'recipient_name' => 'Budi', 'phone' => '0812', 'address' => 'Jalan Mawar', 'city' => 'Solo',
        ])->assertSessionHas('error');

        $this->assertSame(0, ShopOrder::count());
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_admin_cannot_set_shipping_twice(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = ShopOrder::create(['code' => 'GT-SHOP-ONGKIR', 'user_id' => User::factory()->create()->id, 'recipient_name' => 'Budi', 'phone' => '0812', 'address' => 'Jalan Mawar', 'city' => 'Solo', 'subtotal' => 100_000]);

        $this->actingAs($admin)->patch(route('admin.shop-orders.shipping', $order), ['shipping_cost' => 20_000])->assertSessionHas('success');
        $this->actingAs($admin)->patch(route('admin.shop-orders.shipping', $order), ['shipping_cost' => 1])->assertSessionHas('error');

        $order->refresh();
        $this->assertSame(20_000, $order->shipping_cost);
        $this->assertSame(120_000, $order->total);
    }

    public function test_payment_owner_and_uniqueness_are_enforced(): void
    {
        $user = User::factory()->create();
        $shopOrder = ShopOrder::create(['code' => 'GT-SHOP-PAY', 'user_id' => $user->id, 'recipient_name' => 'Budi', 'phone' => '0812', 'address' => 'Jalan Mawar', 'city' => 'Solo', 'subtotal' => 100_000]);
        $shopOrder->payment()->create(['gateway' => 'midtrans', 'amount' => 100_000, 'status' => 'pending']);

        $this->expectException(QueryException::class);
        $shopOrder->payment()->create(['gateway' => 'midtrans', 'amount' => 100_000, 'status' => 'pending']);
    }

    public function test_payment_rejects_ambiguous_owner_in_application(): void
    {
        $shopOrder = ShopOrder::create(['code' => 'GT-SHOP-OWNER', 'user_id' => User::factory()->create()->id, 'recipient_name' => 'Budi', 'phone' => '0812', 'address' => 'Jalan Mawar', 'city' => 'Solo', 'subtotal' => 100_000]);
        $order = new Order;
        $order->id = 1;

        $this->expectException(\LogicException::class);
        Payment::create(['order_id' => $order->id, 'shop_order_id' => $shopOrder->id, 'gateway' => 'midtrans', 'amount' => 100_000, 'status' => 'pending']);
    }

    public function test_simulated_gateway_is_idempotent(): void
    {
        $order = ShopOrder::create(['code' => 'GT-SHOP-SIM', 'user_id' => User::factory()->create()->id, 'recipient_name' => 'Budi', 'phone' => '0812', 'address' => 'Jalan Mawar', 'city' => 'Solo', 'subtotal' => 100_000, 'shipping_cost' => 20_000, 'total' => 120_000, 'status' => 'pending_payment']);
        $gateway = new SimulatedGateway;

        $gateway->pay($order);
        $gateway->pay($order);

        $this->assertSame(1, $order->payment()->count());
        $this->assertSame('paid', $order->payment->status);
    }

    public function test_capture_requires_accepted_fraud_status(): void
    {
        config(['services.midtrans.server_key' => 'server-key']);
        $order = ShopOrder::create(['code' => 'GT-SHOP-CAPTURE', 'user_id' => User::factory()->create()->id, 'recipient_name' => 'Budi', 'phone' => '0812', 'address' => 'Jalan Mawar', 'city' => 'Solo', 'subtotal' => 100_000, 'shipping_cost' => 20_000, 'total' => 120_000, 'status' => 'pending_payment']);
        $order->payment()->create(['gateway' => 'midtrans', 'gateway_ref' => $order->code, 'amount' => 120_000, 'status' => 'pending']);
        $payload = ['order_id' => $order->code, 'status_code' => '200', 'gross_amount' => '120000.00', 'transaction_status' => 'capture', 'fraud_status' => 'challenge'];
        $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].'server-key');

        $this->postJson(route('midtrans.webhook'), $payload)->assertOk();
        $this->assertSame('pending_payment', $order->fresh()->status);

        $payload['fraud_status'] = 'accept';
        $this->postJson(route('midtrans.webhook'), $payload)->assertOk();
        $this->assertSame('paid', $order->fresh()->status);
    }

    public function test_shop_webhook_is_idempotent_and_stale_notification_keeps_paid(): void
    {
        config(['services.midtrans.server_key' => 'server-key']);
        $order = ShopOrder::create(['code' => 'GT-SHOP-WEBHOOK', 'user_id' => User::factory()->create()->id, 'recipient_name' => 'Budi', 'phone' => '0812', 'address' => 'Jalan Mawar', 'city' => 'Solo', 'subtotal' => 100_000, 'shipping_cost' => 20_000, 'total' => 120_000, 'status' => 'pending_payment']);
        $order->payment()->create(['gateway' => 'midtrans', 'gateway_ref' => $order->code, 'amount' => 120_000, 'status' => 'pending']);
        $payload = ['order_id' => $order->code, 'status_code' => '200', 'gross_amount' => '120000.00', 'transaction_status' => 'settlement', 'fraud_status' => 'accept', 'payment_type' => 'qris'];
        $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].'server-key');

        $this->postJson(route('midtrans.webhook'), $payload)->assertOk();
        $this->postJson(route('midtrans.webhook'), $payload)->assertOk();
        $payload['transaction_status'] = 'expire';
        $payload['status_code'] = '407';
        $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].'server-key');
        $this->postJson(route('midtrans.webhook'), $payload)->assertOk();

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame('paid', $order->payment->fresh()->status);
        $this->assertSame(1, $order->payment()->count());
    }

    public function test_shop_webhook_requires_existing_payment_intent(): void
    {
        config(['services.midtrans.server_key' => 'server-key']);
        $order = ShopOrder::create(['code' => 'GT-SHOP-NOINTENT', 'user_id' => User::factory()->create()->id, 'recipient_name' => 'Budi', 'phone' => '0812', 'address' => 'Jalan Mawar', 'city' => 'Solo', 'subtotal' => 100_000, 'shipping_cost' => 20_000, 'total' => 120_000, 'status' => 'pending_payment']);
        $payload = ['order_id' => $order->code, 'status_code' => '200', 'gross_amount' => '120000.00', 'transaction_status' => 'settlement'];
        $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].'server-key');

        $this->postJson(route('midtrans.webhook'), $payload)->assertNotFound();
        $this->assertSame('pending_payment', $order->fresh()->status);
    }

    public function test_customer_cannot_view_another_customers_shop_order(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = ShopOrder::create(['code' => 'GT-SHOP-PRIVATE', 'user_id' => $owner->id, 'recipient_name' => 'Budi', 'phone' => '0812', 'address' => 'Jalan Mawar', 'city' => 'Solo', 'subtotal' => 100_000]);

        $this->actingAs($other)->get(route('shop.orders.show', $order))->assertNotFound();
    }
}
