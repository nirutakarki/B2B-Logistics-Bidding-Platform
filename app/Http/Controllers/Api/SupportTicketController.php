<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSupportTicketRequest;
use App\Http\Requests\UpdateSupportTicketRequest;
use App\Http\Requests\ResolveSupportTicketRequest;
use App\Models\SupportTicket;
use App\Shared\Enums\SupportTicketStatus;
use App\Shared\Enums\SupportTicketPriority;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    /**
     * List support tickets
     * - Users see their own tickets
     * - Admins see all tickets
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $query = SupportTicket::with(['raisedBy.business', 'assignedTo']);
        
        // Non-admins only see their own tickets
        if (!$user->hasRole('platform_admin')) {
            $query->where('raised_by_user_id', $user->id);
        }
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }
        
        $tickets = $query->orderBy('created_at', 'desc')->get()->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'raised_by' => [
                    'id' => $ticket->raisedBy->id,
                    'name' => $ticket->raisedBy->name,
                    'business' => $ticket->raisedBy->business ? [
                        'id' => $ticket->raisedBy->business->id,
                        'name' => $ticket->raisedBy->business->name,
                    ] : null,
                ],
                'assigned_to' => $ticket->assignedTo ? [
                    'id' => $ticket->assignedTo->id,
                    'name' => $ticket->assignedTo->name,
                ] : null,
                'created_at' => $ticket->created_at,
                'updated_at' => $ticket->updated_at,
            ];
        });
        
        return response()->json([
            'tickets' => $tickets,
            'total' => $tickets->count(),
        ]);
    }

    /**
     * Create a new support ticket
     */
    public function store(CreateSupportTicketRequest $request): JsonResponse
    {
        $ticket = SupportTicket::create([
            'raised_by_user_id' => $request->user()->id,
            'subject' => $request->subject,
            'description' => $request->description,
            'priority' => $request->priority,
            'status' => SupportTicketStatus::Open,
        ]);
        
        $ticket->load(['raisedBy.business']);
        
        return response()->json([
            'message' => 'Support ticket created successfully',
            'ticket' => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'raised_by' => [
                    'id' => $ticket->raisedBy->id,
                    'name' => $ticket->raisedBy->name,
                    'business' => $ticket->raisedBy->business ? [
                        'id' => $ticket->raisedBy->business->id,
                        'name' => $ticket->raisedBy->business->name,
                    ] : null,
                ],
                'created_at' => $ticket->created_at,
            ],
        ], 201);
    }

    /**
     * View a specific ticket
     */
    public function show(Request $request, SupportTicket $ticket): JsonResponse
    {
        $user = $request->user();
        
        // User can view if they created it OR they are admin
        $canView = $ticket->raised_by_user_id === $user->id || 
                   $user->hasRole('platform_admin');
        
        if (!$canView) {
            return response()->json([
                'message' => 'You do not have permission to view this ticket',
            ], 403);
        }
        
        $ticket->load(['raisedBy.business', 'assignedTo']);
        
        return response()->json([
            'ticket' => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'raised_by' => [
                    'id' => $ticket->raisedBy->id,
                    'name' => $ticket->raisedBy->name,
                    'email' => $ticket->raisedBy->email,
                    'business' => $ticket->raisedBy->business ? [
                        'id' => $ticket->raisedBy->business->id,
                        'name' => $ticket->raisedBy->business->name,
                        'type' => $ticket->raisedBy->business->type,
                    ] : null,
                ],
                'assigned_to' => $ticket->assignedTo ? [
                    'id' => $ticket->assignedTo->id,
                    'name' => $ticket->assignedTo->name,
                    'email' => $ticket->assignedTo->email,
                ] : null,
                'created_at' => $ticket->created_at,
                'updated_at' => $ticket->updated_at,
            ],
        ]);
    }

    /**
     * Update ticket (by ticket creator)
     */
    public function update(UpdateSupportTicketRequest $request, SupportTicket $ticket): JsonResponse
    {
        // Only allow updates if ticket is still open
        if ($ticket->status !== SupportTicketStatus::Open) {
            return response()->json([
                'message' => 'Only open tickets can be updated',
            ], 400);
        }
        
        $ticket->update($request->validated());
        
        return response()->json([
            'message' => 'Ticket updated successfully',
            'ticket' => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'priority' => $ticket->priority,
                'updated_at' => $ticket->updated_at,
            ],
        ]);
    }

    /**
     * Assign ticket to admin (admin only)
     */
    public function assign(Request $request, SupportTicket $ticket): JsonResponse
    {
        $request->validate([
            'admin_user_id' => ['required', 'exists:users,id'],
        ]);
        
        $ticket->update([
            'assigned_to_user_id' => $request->admin_user_id,
            'status' => SupportTicketStatus::InProgress,
        ]);
        
        $ticket->load('assignedTo');
        
        return response()->json([
            'message' => 'Ticket assigned successfully',
            'ticket' => [
                'id' => $ticket->id,
                'status' => $ticket->status,
                'assigned_to' => [
                    'id' => $ticket->assignedTo->id,
                    'name' => $ticket->assignedTo->name,
                ],
            ],
        ]);
    }

    /**
     * Resolve ticket (admin only)
     */
    public function resolve(ResolveSupportTicketRequest $request, SupportTicket $ticket): JsonResponse
    {
        if ($ticket->status === SupportTicketStatus::Resolved || 
            $ticket->status === SupportTicketStatus::Closed) {
            return response()->json([
                'message' => 'Ticket is already resolved or closed',
            ], 400);
        }
        
        $ticket->update([
            'status' => SupportTicketStatus::Resolved,
        ]);
        
        // Here you could store resolution_notes in a separate table or add a field
        // For now, we'll just change the status
        
        return response()->json([
            'message' => 'Ticket resolved successfully',
            'ticket' => [
                'id' => $ticket->id,
                'status' => $ticket->status,
                'updated_at' => $ticket->updated_at,
            ],
        ]);
    }

    /**
     * Close ticket (admin or ticket creator)
     */
    public function close(Request $request, SupportTicket $ticket): JsonResponse
    {
        $user = $request->user();
        
        // User can close if they created it OR they are admin
        $canClose = $ticket->raised_by_user_id === $user->id || 
                    $user->hasRole('platform_admin');
        
        if (!$canClose) {
            return response()->json([
                'message' => 'You do not have permission to close this ticket',
            ], 403);
        }
        
        if ($ticket->status === SupportTicketStatus::Closed) {
            return response()->json([
                'message' => 'Ticket is already closed',
            ], 400);
        }
        
        $ticket->update([
            'status' => SupportTicketStatus::Closed,
        ]);
        
        return response()->json([
            'message' => 'Ticket closed successfully',
            'ticket' => [
                'id' => $ticket->id,
                'status' => $ticket->status,
                'updated_at' => $ticket->updated_at,
            ],
        ]);
    }

    /**
     * Reopen a closed ticket (ticket creator only)
     */
    public function reopen(Request $request, SupportTicket $ticket): JsonResponse
    {
        $user = $request->user();
        
        if ($ticket->raised_by_user_id !== $user->id) {
            return response()->json([
                'message' => 'Only the ticket creator can reopen this ticket',
            ], 403);
        }
        
        if ($ticket->status !== SupportTicketStatus::Closed && 
            $ticket->status !== SupportTicketStatus::Resolved) {
            return response()->json([
                'message' => 'Only closed or resolved tickets can be reopened',
            ], 400);
        }
        
        $ticket->update([
            'status' => SupportTicketStatus::Open,
        ]);
        
        return response()->json([
            'message' => 'Ticket reopened successfully',
            'ticket' => [
                'id' => $ticket->id,
                'status' => $ticket->status,
                'updated_at' => $ticket->updated_at,
            ],
        ]);
    }

    /**
     * Get statistics (admin only)
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total' => SupportTicket::count(),
            'by_status' => [
                'open' => SupportTicket::where('status', SupportTicketStatus::Open)->count(),
                'in_progress' => SupportTicket::where('status', SupportTicketStatus::InProgress)->count(),
                'resolved' => SupportTicket::where('status', SupportTicketStatus::Resolved)->count(),
                'closed' => SupportTicket::where('status', SupportTicketStatus::Closed)->count(),
            ],
            'by_priority' => [
                'low' => SupportTicket::where('priority', SupportTicketPriority::Low)->count(),
                'medium' => SupportTicket::where('priority', SupportTicketPriority::Medium)->count(),
                'high' => SupportTicket::where('priority', SupportTicketPriority::High)->count(),
                'urgent' => SupportTicket::where('priority', SupportTicketPriority::Urgent)->count(),
            ],
            'unassigned' => SupportTicket::whereNull('assigned_to_user_id')
                ->whereIn('status', [SupportTicketStatus::Open, SupportTicketStatus::InProgress])
                ->count(),
        ];
        
        return response()->json($stats);
    }
}
