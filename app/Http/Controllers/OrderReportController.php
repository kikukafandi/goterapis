<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderReportController extends Controller
{
    public function store(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->hasParticipant($request->user()) && $request->user()->role !== 'admin', 403);

        $data = $request->validate([
            'reason' => ['required', 'string', 'in:pelecehan_seksual,perilaku_tidak_pantas'],
            'detail' => ['required', 'string', 'min:20', 'max:5000'],
            'source' => ['required', 'in:order,chat'],
        ]);

        DB::transaction(function () use ($request, $order, $data) {
            $order = Order::with(['user', 'therapistProfile.user', 'service', 'messages.sender', 'earning'])
                ->lockForUpdate()->findOrFail($order->id);
            $withinWindow = $order->status === 'in_progress'
                || ($order->status === 'completed' && $order->completed_at?->addHours((int) config('goterapis.report_window_hours'))->isFuture());
            if (! $withinWindow) {
                throw ValidationException::withMessages(['detail' => 'Laporan hanya dapat dikirim saat layanan berjalan atau maksimal 24 jam setelah selesai.']);
            }

            $committed = $order->therapistProfile->withdrawals()->whereIn('status', ['requested', 'approved'])->sum('amount');
            $otherAvailable = $order->therapistProfile->earnings()
                ->whereKeyNot($order->earning?->id)
                ->where('available_at', '<=', now())
                ->whereHas('order', fn ($query) => $query->where('status', 'completed'))
                ->sum('amount');
            if ($order->earning && $committed > $otherAvailable) {
                throw ValidationException::withMessages(['detail' => 'Laporan tidak dapat membuka sengketa karena dana terapis sudah masuk proses pencairan. Hubungi admin untuk bantuan.']);
            }

            $reportedUser = $order->user_id === $request->user()->id ? $order->therapistProfile->user : $order->user;
            $orderSnapshot = $order->only(['id', 'code', 'user_id', 'therapist_profile_id', 'service_id', 'status', 'model', 'scheduled_at', 'started_at', 'completed_at', 'created_at']);
            $orderSnapshot['therapist_user_id'] = $order->therapistProfile->user_id;

            Report::firstOrCreate([
                'reporter_id' => $request->user()->id,
                'reportable_type' => Order::class,
                'reportable_id' => $order->id,
                'reason' => $data['reason'],
            ], [
                'reported_user_id' => $reportedUser->id,
                'detail' => $data['detail'],
                'evidence' => ['source' => $data['source'], 'captured_at' => now()->toISOString(), 'order' => $orderSnapshot, 'chat' => $order->messages->map->only(['id', 'sender_id', 'body', 'created_at'])->values()->all()],
            ]);

            $order->changeStatus('disputed', 'Dana ditahan sementara selama laporan ditinjau admin.', from: ['in_progress', 'completed']);
        });

        return back()->with('success', 'Laporan diterima dan akan ditinjau secara rahasia.');
    }
}
