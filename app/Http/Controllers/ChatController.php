<?php
namespace App\Http\Controllers;

use App\Models\Chat;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $chats = Chat::where('sender_id', $user->id)
            ->orWhere('receiver_id', $user->id)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($chats);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $this->validate($request, [
            'receiver_id' => 'required|integer|exists:users,id',
            'message' => 'required|string'
        ]);

        $chat = Chat::create([
            'sender_id' => $user->id,
            'receiver_id' => $validated['receiver_id'],
            'message' => $validated['message'],
        ]);

        return response()->json($chat, 201);
    }
}
