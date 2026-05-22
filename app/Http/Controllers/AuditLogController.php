<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Display a listing of audit logs.
     */
    public function index(Request $request)
    {
        $this->authorize('view-audit-logs');

        $query = AuditLog::with('user')->latest();

        // Filter by model
        if ($request->has('model') && $request->model) {
            $query->where('model', $request->model);
        }

        // Filter by action
        if ($request->has('action') && $request->action) {
            $query->where('action', $request->action);
        }

        // Search by user
        if ($request->has('search') && $request->search) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', '%' . request('search') . '%')
                    ->orWhere('email', 'like', '%' . request('search') . '%');
            });
        }

        $auditLogs = $query->paginate(20);
        $models = AuditLog::select('model')->distinct()->orderBy('model')->pluck('model');

        return view('audit-logs.index', compact('auditLogs', 'models'));
    }

    /**
     * Display a specific audit log.
     */
    public function show(AuditLog $auditLog)
    {
        $this->authorize('view-audit-logs');
        return view('audit-logs.show', compact('auditLog'));
    }

    /**
     * Get audit logs for a specific model.
     */
    public function modelHistory(Request $request, string $model)
    {
        $this->authorize('view-audit-logs');

        $query = AuditLog::where('model', $model)->with('user')->latest();

        if ($request->has('model_id') && $request->model_id) {
            $query->where('model_id', $request->model_id);
        }

        $auditLogs = $query->paginate(15);

        return view('audit-logs.model-history', compact('auditLogs', 'model'));
    }
}
