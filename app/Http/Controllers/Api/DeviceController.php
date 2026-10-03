<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_token' => ['required', 'string', 'max:512'],
            'platform' => ['required', 'string', 'in:android,ios'],
        ]);

        $user = $request->user();

        $device = UserDevice::where('user_id', $user->id)
            ->where('device_token', $validated['device_token'])
            ->first();

        if ($device) {
            $device->platform = $validated['platform'];
            $device->is_active = true;
            $device->last_seen_at = now();
            $device->save();

            return response()->json([
                'message' => 'Device updated successfully.',
                'created' => false,
                'device' => $device,
            ]);
        }

        $device = UserDevice::create([
            'user_id' => $user->id,
            'device_token' => $validated['device_token'],
            'platform' => $validated['platform'],
            'is_active' => true,
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'message' => 'Device registered successfully.',
            'created' => true,
            'device' => $device,
        ], 201);
    }
}