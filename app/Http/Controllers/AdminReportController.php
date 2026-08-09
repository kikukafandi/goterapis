<?php

namespace App\Http\Controllers;

use App\Jobs\RefundLatePayment;
use App\Models\Order;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminReportController extends Controller
{
    public function index(Request $request): View
    {
        $status = in_array($request->status, ['open', 'reviewing', 'resolved', 'dismissed'], true) ? $request->status : null;
        $reports = Report::with(['reporter', 'reportedUser'])
            ->when($status, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.reports.index', compact('reports', 'status'));
    }

    public function show(Report $report): View
    {
        $report->load(['reporter', 'reportedUser', 'reviewer', 'reportable']);

        return view('admin.reports.show', compact('report'));
    }

    public function update(Request $request, Report $report): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:open,reviewing,resolved,dismissed'],
            'resolution' => ['nullable', 'required_if:status,resolved,dismissed', 'in:release,refund'],
            'admin_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $refund = DB::transaction(function () use ($request, $report, $data) {
            $report = Report::lockForUpdate()->findOrFail($report->id);
            if (in_array($report->status, ['resolved', 'dismissed'], true)) {
                return null;
            }

            $order = $report->reportable instanceof Order ? Order::with(['earning', 'payment'])->lockForUpdate()->find($report->reportable_id) : null;
            if (in_array($data['status'], ['resolved', 'dismissed'], true) && $order?->status !== 'disputed') {
                throw ValidationException::withMessages(['status' => 'Pesanan laporan ini tidak lagi berada dalam sengketa.']);
            }

            if ($data['status'] === 'resolved' || $data['status'] === 'dismissed') {
                if ($data['resolution'] === 'release') {
                    $order->changeStatus('completed', 'Sengketa selesai dan dana dilepaskan kepada terapis.', ['completed_at' => $order->completed_at ?? now()], ['disputed']);
                } else {
                    $order->earning?->delete();
                    $order->payment?->update(['refund_amount' => $order->total, 'refund_requested_at' => $order->payment->refund_requested_at ?? now(), 'refund_failed_at' => null, 'refund_error' => null]);
                }
            }

            $report->update(['status' => $data['status'], 'admin_note' => $data['admin_note'] ?? null, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);

            return ($data['resolution'] ?? null) === 'refund' ? $order : null;
        });

        if ($refund) {
            RefundLatePayment::dispatch($refund->id, $refund->total)->afterCommit();
        }

        return back()->with('ok', 'Laporan diperbarui.');
    }
}
