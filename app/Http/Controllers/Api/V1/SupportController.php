<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SupportController extends Controller
{
    public function tickets(Request $request)
    {
        return $this->index($request);
    }

    public function storeTicket(Request $request)
    {
        return $this->store($request);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $merchant = $request->activeMerchant();
        $merchantId = $merchant ? $merchant->id : $user->id;

        if (!$this->tableExists()) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $tickets = SupportTicket::where('merchant_id', $merchantId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tickets,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'category' => 'required|string|in:payment,account,technical,general,bug,feature',
            'priority' => 'required|string|in:low,medium,high,urgent',
        ]);

        $user = $request->user();
        $merchant = $request->activeMerchant();
        $merchantId = $merchant ? $merchant->id : $user->id;

        if (!$this->tableExists()) {
            return response()->json([
                'success' => false,
                'message' => 'Support tickets table not found. Please run migration.',
            ], 500);
        }

        $ticket = SupportTicket::create([
            'merchant_id' => $merchantId,
            'user_id' => $user->id,
            'ticket_number' => 'TICKET-' . strtoupper(Str::random(8)),
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'status' => 'open',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Support ticket created successfully',
            'data' => $ticket,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $ticket = $this->findMerchantTicket($request, $id);

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $ticket,
        ]);
    }

    public function reply(Request $request, $id)
    {
        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        $ticket = $this->findMerchantTicket($request, $id);

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found',
            ], 404);
        }

        $ticket->update([
            'message' => $ticket->message . "\n\n--- REPLY ---\n" . $validated['message'],
            'status' => 'in_progress',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reply added successfully',
            'data' => $ticket,
        ]);
    }

    public function close(Request $request, $id)
    {
        $ticket = $this->findMerchantTicket($request, $id);

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found',
            ], 404);
        }

        $ticket->update([
            'status' => 'closed',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ticket closed successfully',
            'data' => $ticket,
        ]);
    }

    public function stats(Request $request)
    {
        $user = $request->user();
        $merchant = $request->activeMerchant();
        $merchantId = $merchant ? $merchant->id : $user->id;

        if (!$this->tableExists()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'total' => 0,
                    'open' => 0,
                    'in_progress' => 0,
                    'closed' => 0,
                    'resolved' => 0,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total' => SupportTicket::where('merchant_id', $merchantId)->count(),
                'open' => SupportTicket::where('merchant_id', $merchantId)->where('status', 'open')->count(),
                'in_progress' => SupportTicket::where('merchant_id', $merchantId)->where('status', 'in_progress')->count(),
                'closed' => SupportTicket::where('merchant_id', $merchantId)->where('status', 'closed')->count(),
                'resolved' => SupportTicket::where('merchant_id', $merchantId)->where('status', 'resolved')->count(),
            ],
        ]);
    }

    private function findMerchantTicket(Request $request, $id)
    {
        $user = $request->user();
        $merchant = $request->activeMerchant();
        $merchantId = $merchant ? $merchant->id : $user->id;

        return SupportTicket::where('merchant_id', $merchantId)
            ->where('id', $id)
            ->first();
    }

    private function tableExists(): bool
    {
        return !empty(DB::select("SHOW TABLES LIKE 'support_tickets'"));
    }
}