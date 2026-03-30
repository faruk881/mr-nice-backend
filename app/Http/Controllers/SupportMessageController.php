<?php

namespace App\Http\Controllers;

use App\Models\SupportMessage;
use Illuminate\Http\Request;

class SupportMessageController extends Controller
{
    public function index(Request $request) {
        $perPage = $request->query('per_page', 10);
        $query = SupportMessage::paginate($perPage);
        return apiSuccess('Messages retrieved successfully', $query);
    }

    public function store(Request $request) {
        $user_id = auth()->user()?->id;
        $email = $request->input('email');
        $topic = $request->input('topic');
        $order_number = $request->input('order_number');
        $message = $request->input('message');

        $SupportMessage = SupportMessage::create([
            'user_id' => $user_id,
            'email' => $email,
            'topic' => $topic,
            'order_number' => $order_number,
            'message' => $message,
        ]);

        return apiSuccess('Message sent successfully', $SupportMessage);
    }

    public function update(Request $request, $id) {
        $request->validate([
            'status' => 'required|in:in_progress,resolved',
        ]);

        $status = $request->input('status');

        $supportMessage = SupportMessage::where('id',$id)->first();
        if (!$supportMessage) {
            return apiError('Message not found', 404, ['code'=>'MESSAGE_NOT_FOUND']);
        }

        $supportMessage->status = $status;
        $supportMessage->save();

        return apiSuccess('Message updated successfully', $supportMessage);

    }
}
