<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Application;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    /**
     * Lấy danh sách conversation của user hiện tại
     */
    public function getConversations(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $conversations = Conversation::where('id_user1_FK', $user->id)
                ->orWhere('id_user2_FK', $user->id)
                ->with(['user1:id,name,email', 'user2:id,name,email'])
                ->with(['messages' => function ($query) {
                    $query->latest()->limit(1);
                }])
                ->orderBy('updated_at', 'desc')
                ->get();

            // Format response
            $formattedConversations = $conversations->map(function ($conversation) use ($user) {
                $otherUser = $conversation->id_user1_FK == $user->id
                    ? $conversation->user2
                    : $conversation->user1;

                // Kiểm tra null để tránh lỗi
                if (!$otherUser) {
                    return null; // Bỏ qua conversation có user bị xóa
                }

                $lastMessage = $conversation->messages->first();

                // Đếm tin nhắn chưa đọc (tin nhắn từ user khác)
                $unreadCount = Message::where('id_conversa_FK', $conversation->id_conversa)
                    ->where('id_user_FK', '!=', $user->id)
                    ->count();

                return [
                    'id' => $conversation->id_conversa,
                    'other_user' => [
                        'id' => $otherUser->id,
                        'name' => $otherUser->name ?? 'Người dùng đã xóa',
                        'email' => $otherUser->email ?? '',
                    ],
                    'last_message' => $lastMessage ? [
                        'content' => $lastMessage->content,
                        'send_at' => $lastMessage->send_at,
                        'sender_id' => $lastMessage->id_user_FK,
                    ] : null,
                    'unread_count' => $unreadCount,
                    'updated_at' => $conversation->updated_at,
                ];
            })->filter(); // Loại bỏ các null values

            return response()->json([
                'success' => true,
                'conversations' => $formattedConversations
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getConversations: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);
            return response()->json([
                'error' => 'Server error',
                'message' => 'Có lỗi xảy ra khi tải danh sách cuộc trò chuyện'
            ], 500);
        }
    }

    /**
     * Tạo hoặc lấy conversation giữa candidate và employer dựa trên application
     * Chỉ cho phép khi application status khác "pending"
     */
    public function getOrCreateConversation(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $validator = Validator::make($request->all(), [
                'application_id' => 'required|exists:applications,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'messages' => $validator->errors()
                ], 422);
            }

            // Load application với các relationships cần thiết
            $application = Application::with(['job.company.companyUsers.user', 'user'])
                ->find($request->application_id);

            if (!$application) {
                return response()->json(['error' => 'Application not found'], 404);
            }

            // Kiểm tra status phải khác "pending"
            if ($application->status === 'pending') {
                return response()->json([
                    'error' => 'Cannot start conversation',
                    'message' => 'Chỉ có thể nhắn tin khi nhà tuyển dụng đã xem xét hồ sơ của bạn.'
                ], 403);
            }

            // Xác định users
            $candidateUserId = $application->user_id;
            $currentUserId = $user->id;

            // Kiểm tra user hiện tại có phải là candidate không
            $isCandidate = $currentUserId == $candidateUserId;
            $isEmployer = false;
            $employerUserId = null;

            // Kiểm tra user hiện tại có phải là nhà tuyển dụng (bất kỳ role nào) trong company không
            if ($application->job && $application->job->company && $application->job->company->companyUsers) {
                $companyUsers = $application->job->company->companyUsers;
                foreach ($companyUsers as $companyUser) {
                    if ($companyUser->user_id == $currentUserId && $companyUser->status == 'active') {
                        $isEmployer = true;
                        $employerUserId = $currentUserId; // Employer hiện tại chính là user đang nhắn tin
                        break;
                    }
                }
            }

            // Kiểm tra user hiện tại có liên quan đến application này không
            if (!$isCandidate && !$isEmployer) {
                return response()->json(['error' => 'Unauthorized. You are not related to this application.'], 403);
            }

            // Xác định other user cho conversation
            if ($isCandidate) {
                // Nếu là candidate, cần tìm employer để tạo conversation
                // Ưu tiên Owner/Admin, nếu không có thì lấy employer đầu tiên
                if ($application->job && $application->job->company && $application->job->company->companyUsers) {
                    $employerUser = $application->job->company->companyUsers
                        ->whereIn('role_in_company', ['Owner', 'Admin'])
                        ->where('status', 'active')
                        ->first();

                    if ($employerUser && $employerUser->user) {
                        $employerUserId = $employerUser->user_id;
                    } else {
                        // Nếu không có Owner/Admin, lấy user đầu tiên có status active
                        $employerUser = $application->job->company->companyUsers
                            ->where('status', 'active')
                            ->first();
                        if ($employerUser && $employerUser->user) {
                            $employerUserId = $employerUser->user_id;
                        }
                    }
                }

                if (!$employerUserId) {
                    return response()->json(['error' => 'Employer not found for this application'], 404);
                }

                $otherUserId = $employerUserId;
            } else {
                // Nếu là employer, other user là candidate
                $otherUserId = $candidateUserId;
            }

            // Tìm hoặc tạo conversation
            $conversation = Conversation::where(function ($query) use ($currentUserId, $otherUserId) {
                $query->where('id_user1_FK', $currentUserId)
                    ->where('id_user2_FK', $otherUserId);
            })->orWhere(function ($query) use ($currentUserId, $otherUserId) {
                $query->where('id_user1_FK', $otherUserId)
                    ->where('id_user2_FK', $currentUserId);
            })->first();

            if (!$conversation) {
                $conversation = Conversation::create([
                    'id_user1_FK' => $currentUserId,
                    'id_user2_FK' => $otherUserId,
                ]);
            }

            $otherUser = User::find($otherUserId);

            if (!$otherUser) {
                return response()->json(['error' => 'Other user not found'], 404);
            }

            return response()->json([
                'success' => true,
                'conversation' => [
                    'id' => $conversation->id_conversa,
                    'other_user' => [
                        'id' => $otherUser->id,
                        'name' => $otherUser->name,
                        'email' => $otherUser->email,
                    ],
                    'application_id' => $application->id,
                    'application_status' => $application->status,
                    'created_at' => $conversation->created_at,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getOrCreateConversation: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'application_id' => $request->application_id ?? null,
                'user_id' => Auth::id()
            ]);
            return response()->json([
                'error' => 'Server error',
                'message' => 'Có lỗi xảy ra khi tạo cuộc trò chuyện'
            ], 500);
        }
    }

    /**
     * Lấy tin nhắn trong conversation
     */
    public function getMessages(Request $request, $conversationId)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $conversation = Conversation::find($conversationId);

            if (!$conversation) {
                return response()->json(['error' => 'Conversation not found'], 404);
            }

            // Kiểm tra user có quyền truy cập conversation này không
            if ($conversation->id_user1_FK != $user->id && $conversation->id_user2_FK != $user->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $perPage = $request->get('per_page', 20);
            $messages = Message::where('id_conversa_FK', $conversationId)
                ->with('user:id,name,email')
                ->orderBy('send_at', 'desc')
                ->paginate($perPage);

            // Map messages - paginator items() returns array, wrap in collect() to get collection
            $formattedMessages = collect($messages->items())->map(function ($message) use ($user) {
                // Kiểm tra null để tránh lỗi
                if (!$message->user) {
                    return null; // Bỏ qua message có user bị xóa
                }

                return [
                    'id' => $message->getKey(),
                    'content' => $message->content,
                    'sender' => [
                        'id' => $message->user->id,
                        'name' => $message->user->name ?? 'Người dùng đã xóa',
                        'email' => $message->user->email ?? '',
                    ],
                    'send_at' => $message->send_at,
                    'is_own' => $message->id_user_FK == $user->id,
                ];
            })->filter()->values(); // Loại bỏ các null values và reset keys

            return response()->json([
                'success' => true,
                'messages' => $formattedMessages->all(),
                'pagination' => [
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getMessages: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'conversation_id' => $conversationId,
                'user_id' => Auth::id()
            ]);
            return response()->json([
                'error' => 'Server error',
                'message' => 'Có lỗi xảy ra khi tải tin nhắn'
            ], 500);
        }
    }
//
    /**
     * Gửi tin nhắn
     */
    public function sendMessage(Request $request, $conversationId)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $validator = Validator::make($request->all(), [
                'content' => 'required|string|max:5000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'messages' => $validator->errors()
                ], 422);
            }

            $conversation = Conversation::find($conversationId);

            if (!$conversation) {
                return response()->json(['error' => 'Conversation not found'], 404);
            }

            // Kiểm tra user có quyền gửi tin nhắn trong conversation này không
            if ($conversation->id_user1_FK != $user->id && $conversation->id_user2_FK != $user->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Tạo message - bỏ send_at để database tự động set (useCurrent())
            $message = Message::create([
                'id_conversa_FK' => $conversationId,
                'id_user_FK' => $user->id,
                'content' => $request->content,
                // Không set send_at - để database tự động set từ migration useCurrent()
            ]);

            // Cập nhật updated_at của conversation
            $conversation->touch();

            // Load user relationship
            $message->load('user:id,name,email');

            // Kiểm tra user relationship
            if (!$message->user) {
                return response()->json([
                    'error' => 'User not found',
                    'message' => 'Không tìm thấy thông tin người gửi'
                ], 404);
            }

            // Broadcast event for realtime chat (only if broadcasting is configured)
            try {
                $broadcastDriver = config('broadcasting.default');
                if ($broadcastDriver && $broadcastDriver !== 'null') {
                    event(new MessageSent($message));
                }
            } catch (\Exception $e) {
                // Log error but don't fail the request if broadcasting fails
                Log::warning('Failed to broadcast message: ' . $e->getMessage(), [
                    'conversation_id' => $conversationId,
                    'message_id' => $message->getKey(),
                    'error' => $e->getMessage()
                ]);
                // Continue without broadcasting - message is still saved
            }

            return response()->json([
                'success' => true,
                'message' => [
                    'id' => $message->getKey(),
                    'content' => $message->content,
                    'sender' => [
                        'id' => $message->user->id,
                        'name' => $message->user->name ?? 'Người dùng đã xóa',
                        'email' => $message->user->email ?? '',
                    ],
                    'send_at' => $message->send_at,
                    'is_own' => true,
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error in sendMessage: ' . $e->getMessage(), [
                'conversation_id' => $conversationId ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Internal server error',
                'message' => 'Có lỗi xảy ra khi gửi tin nhắn. Vui lòng thử lại.'
            ], 500);
        }
    }

    /**
     * Kiểm tra xem có thể nhắn tin với application này không
     */
    public function canChatWithApplication(Request $request, $applicationId)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $application = Application::with(['job.company.companyUsers', 'user'])
            ->find($applicationId);

        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        // Kiểm tra user có liên quan đến application này không
        $candidateUserId = $application->user_id;
        $isCandidate = $user->id == $candidateUserId;
        $isEmployer = false;

        if ($application->job && $application->job->company) {
            $companyUsers = $application->job->company->companyUsers;
            foreach ($companyUsers as $companyUser) {
                if ($companyUser->user_id == $user->id && $companyUser->status == 'active') {
                    $isEmployer = true;
                    break;
                }
            }
        }

        if (!$isCandidate && !$isEmployer) {
            return response()->json([
                'can_chat' => false,
                'reason' => 'Unauthorized. You are not related to this application.'
            ], 403);
        }

        // Kiểm tra status
        $canChat = $application->status !== 'pending';

        return response()->json([
            'can_chat' => $canChat,
            'application_status' => $application->status,
            'message' => $canChat
                ? 'Có thể nhắn tin'
                : 'Chỉ có thể nhắn tin khi nhà tuyển dụng đã xem xét hồ sơ của bạn.'
        ]);
    }

    /**
     * Đánh dấu tin nhắn đã đọc (optional - có thể mở rộng sau)
     */
    public function markAsRead(Request $request, $conversationId)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        // Kiểm tra user có quyền truy cập conversation này không
        if ($conversation->id_user1_FK != $user->id && $conversation->id_user2_FK != $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // TODO: Implement read status tracking if needed
        // For now, just return success

        return response()->json([
            'success' => true,
            'message' => 'Messages marked as read'
        ]);
    }
}
