<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()->notifications()->latest()->limit(20)->get()->map(fn ($item) => [
            'id' => $item->id, 'title' => $item->data['title'], 'message' => $item->data['message'],
            'path' => $item->data['path'] ?? null, 'read_at' => $item->read_at?->toISOString(),
            'date' => $item->data['date'] ?? null,
            'created_at' => $item->created_at->toISOString(),
        ])]);
    }

    public function read(Request $request, string $notification): JsonResponse
    {
        $item = $request->user()->notifications()->findOrFail($notification);
        $item->markAsRead();

        return response()->json(['message' => 'اعلان خوانده شد.']);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['message' => 'همه اعلان‌ها خوانده شدند.']);
    }
}
