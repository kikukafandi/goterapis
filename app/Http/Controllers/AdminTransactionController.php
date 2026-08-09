<?php

namespace App\Http\Controllers;

use App\Jobs\RefundLatePayment;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminTransactionController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->validate(['status' => ['nullable', Rule::in(['pending', 'paid', 'failed', 'expired', 'refunded'])]])['status'] ?? null;
        $payments = Payment::with(['order.user', 'order.therapistProfile.user'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.transactions.index', compact('payments', 'status'));
    }

    public function show(Payment $payment): View
    {
        $payment->load(['order.user', 'order.therapistProfile.user', 'order.service']);

        return view('admin.transactions.show', compact('payment'));
    }

    public function retryRefund(Payment $payment): RedirectResponse
    {
        abort_unless($payment->canRetryRefund(), 422, 'Pengembalian dana belum dapat dicoba ulang.');

        $payment->update(['refund_failed_at' => null, 'refund_error' => null]);
        RefundLatePayment::dispatch($payment->order_id, $payment->refund_amount)->afterCommit();

        return back()->with('success', 'Pengembalian dana dimasukkan kembali ke antrean.');
    }
}
