<?php
namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications);
    }

    public function markRead(Request $request, $id)
    {
        $user = $request->user();

        $notif = Notification::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$notif) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notif->update(['is_read' => true]);

        return response()->json(['message' => 'Notification marked as read']);
    }
}
