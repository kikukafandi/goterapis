<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageSent;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ChatMessageController extends Controller
{
    public function index(Request $request): View
    {
        abort_if($request->user()->role === 'admin', 403);

        $user = $request->user();
        $orders = Order::query()
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id);
                if ($user->therapistProfile !== null) {
                    $query->orWhere('therapist_profile_id', $user->therapistProfile->id);
                }
            })
            ->with(['user', 'therapistProfile.user', 'service', 'latestMessage'])
            ->withCount(['messages as unread_messages_count' => fn ($query) => $query->whereNull('read_at')->where('sender_id', '!=', $user->id)])
            ->orderByRaw('COALESCE((select max(created_at) from chat_messages where chat_messages.order_id = orders.id), orders.created_at) DESC')
            ->paginate(10);

        return view('chat.index', compact('orders'));
    }

    public function store(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->hasParticipant($request->user()), 403);

        $validator = Validator::make($request->all(), ['body' => ['required', 'string', 'max:1000']]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Isi pesan tidak valid.', 'errors' => $validator->errors()], 422);
        }

        $message = $order->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $validator->validated()['body'],
        ])->load('sender');

        broadcast(new ChatMessageSent($message))->toOthers();

        return response()->json([
            ...$message->only(['id', 'body', 'sender_id', 'created_at']),
            'sender_name' => $message->sender->name,
        ], 201);
    }
}
