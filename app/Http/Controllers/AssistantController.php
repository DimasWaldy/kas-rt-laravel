<?php

namespace App\Http\Controllers;

use App\Services\SmartRwAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssistantController extends Controller
{
    /**
     * Proses pesan warga pada widget Customer Service Smart RW.
     */
    public function chat(Request $request, SmartRwAssistantService $assistant): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:500'],
            'history' => ['nullable', 'array', 'max:6'],
            'history.*.role' => ['required_with:history', 'string', 'in:user,model'],
            'history.*.text' => ['required_with:history', 'string', 'max:500'],
        ]);

        $result = $assistant->reply(
            $validated['message'],
            $validated['history'] ?? [],
            $request->user()
        );

        return response()->json($result);
    }
}
