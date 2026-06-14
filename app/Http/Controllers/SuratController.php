<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSuratRequest;
use App\Models\Surat;
use App\Models\SuratAttachment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SuratController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Surat::with(['user', 'rt'])->latest('submitted_at');

        if ($user->role_name === 'warga') {
            $query->where('user_id', $user->id);
        } else {
            $query->visibleTo($user);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('surat.index', [
            'surats' => $query->paginate(12)->withQueryString(),
            'status' => $request->string('status')->toString(),
            'types' => Surat::TYPES,
        ]);
    }

    public function create()
    {
        return redirect()->route('surat.index')->with('info', 'Gunakan tombol Ajukan Surat.');
    }

    public function store(StoreSuratRequest $request)
    {
        $validated = $request->validated();
        $type = Surat::TYPES[$validated['type']];

        $surat = DB::transaction(function () use ($request, $validated, $type) {
            $surat = Surat::create([
                'user_id' => $request->user()->id,
                'rt_id' => $request->user()->rt_id,
                'type' => $validated['type'],
                'subject' => $validated['subject'],
                'purpose' => $validated['purpose'],
                'content' => $validated['content'] ?? null,
                'requires_rw' => $type['requires_rw'],
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            foreach ($request->file('attachments', []) as $file) {
                $surat->attachments()->create([
                    'file_path' => $file->store("surat/{$surat->id}", 'local'),
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'file_size' => $file->getSize(),
                ]);
            }

            return $surat;
        });

        return redirect()->route('surat.show', $surat)->with('success', 'Pengajuan surat berhasil dikirim ke pengurus RT.');
    }

    public function show(Surat $surat)
    {
        $this->authorizeView($surat, Auth::user());
        $surat->load(['user', 'rt.rw', 'attachments', 'verifierRt', 'approverRt', 'verifierRw', 'approverRw', 'rejector']);

        return view('surat.show', [
            'surat' => $surat,
            'actions' => $this->availableActions($surat, Auth::user()),
        ]);
    }

    public function attachment(Surat $surat, SuratAttachment $attachment)
    {
        $this->authorizeView($surat, Auth::user());
        abort_unless($attachment->surat_id === $surat->id, 404);
        abort_unless(Storage::disk('local')->exists($attachment->file_path), 404);

        return Storage::disk('local')->download($attachment->file_path, $attachment->file_name);
    }

    public function verifyRt(Surat $surat)
    {
        $this->performAction($surat, 'verify_rt');
        $surat->update([
            'status' => 'verified_rt',
            'verified_rt_by' => Auth::id(),
            'verified_rt_at' => now(),
        ]);

        return back()->with('success', 'Pengajuan sudah diverifikasi oleh Sekretaris RT.');
    }

    public function approveRt(Surat $surat)
    {
        $this->performAction($surat, 'approve_rt');

        $surat->update([
            'status' => $surat->requires_rw ? 'approved_rt' : 'done',
            'approved_rt_by' => Auth::id(),
            'approved_rt_at' => now(),
        ]);

        if (! $surat->requires_rw) {
            $this->finalize($surat);
        }

        return back()->with('success', $surat->requires_rw
            ? 'Surat disetujui Ketua RT dan diteruskan ke pengurus RW.'
            : 'Surat disetujui Ketua RT dan siap dicetak.');
    }

    public function verifyRw(Surat $surat)
    {
        $this->performAction($surat, 'verify_rw');
        $surat->update([
            'status' => 'verified_rw',
            'verified_rw_by' => Auth::id(),
            'verified_rw_at' => now(),
        ]);

        return back()->with('success', 'Surat sudah diverifikasi oleh Sekretaris RW.');
    }

    public function approveRw(Surat $surat)
    {
        $this->performAction($surat, 'approve_rw');
        $surat->update([
            'status' => 'done',
            'approved_rw_by' => Auth::id(),
            'approved_rw_at' => now(),
        ]);
        $this->finalize($surat);

        return back()->with('success', 'Surat disetujui Ketua RW dan siap dicetak.');
    }

    public function reject(Request $request, Surat $surat)
    {
        $this->performAction($surat, 'reject');
        $validated = $request->validate([
            'rejected_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $surat->update([
            'status' => 'rejected',
            'rejected_by' => Auth::id(),
            'rejected_at' => now(),
            'rejected_reason' => $validated['rejected_reason'],
        ]);

        return back()->with('success', 'Pengajuan surat ditolak dengan alasan yang tercatat.');
    }

    public function print(Surat $surat)
    {
        $this->authorizeView($surat, Auth::user());
        abort_unless($surat->isFinal(), 404);

        return view('surat.print', ['surat' => $surat->load(['user', 'rt.rw', 'approverRt', 'approverRw'])]);
    }

    public function verifyPublic(string $code)
    {
        $surat = Surat::with(['user', 'rt.rw'])
            ->where('verification_code', $code)
            ->where('status', 'done')
            ->firstOrFail();

        return view('surat.verify', compact('surat'));
    }

    private function authorizeView(Surat $surat, User $user): void
    {
        if ($user->role_name === 'warga') {
            abort_unless($surat->user_id === $user->id, 404);
            return;
        }

        abort_unless($user->canViewSurat() && $surat->isVisibleTo($user), 404);
    }

    private function availableActions(Surat $surat, User $user): array
    {
        $global = $user->isGlobalOperator();
        $sameRt = $surat->isVisibleTo($user);

        return [
            'verify_rt' => $sameRt && $surat->status === 'submitted'
                && ($global || in_array($user->role_name, ['sekretaris', 'sekretaris_rt'], true)),
            'approve_rt' => $sameRt && $surat->status === 'verified_rt'
                && ($global || $user->role_name === 'ketua_rt'),
            'verify_rw' => $surat->requires_rw && $surat->status === 'approved_rt'
                && ($global || $user->role_name === 'sekretaris_rw'),
            'approve_rw' => $surat->requires_rw && $surat->status === 'verified_rw'
                && ($global || $user->role_name === 'ketua_rw'),
            'reject' => ! in_array($surat->status, ['done', 'rejected'], true)
                && $this->canActAtCurrentStage($surat, $user),
        ];
    }

    private function canActAtCurrentStage(Surat $surat, User $user): bool
    {
        $actions = match ($surat->status) {
            'submitted' => ['verify_rt'],
            'verified_rt' => ['approve_rt'],
            'approved_rt' => ['verify_rw'],
            'verified_rw' => ['approve_rw'],
            default => [],
        };

        $available = $this->availableActionsWithoutReject($surat, $user);

        return collect($actions)->contains(fn ($action) => $available[$action] ?? false);
    }

    private function availableActionsWithoutReject(Surat $surat, User $user): array
    {
        $global = $user->isGlobalOperator();
        $sameRt = $surat->isVisibleTo($user);

        return [
            'verify_rt' => $sameRt && $surat->status === 'submitted' && ($global || in_array($user->role_name, ['sekretaris', 'sekretaris_rt'], true)),
            'approve_rt' => $sameRt && $surat->status === 'verified_rt' && ($global || $user->role_name === 'ketua_rt'),
            'verify_rw' => $surat->requires_rw && $surat->status === 'approved_rt' && ($global || $user->role_name === 'sekretaris_rw'),
            'approve_rw' => $surat->requires_rw && $surat->status === 'verified_rw' && ($global || $user->role_name === 'ketua_rw'),
        ];
    }

    private function performAction(Surat $surat, string $action): void
    {
        $this->authorizeView($surat, Auth::user());
        abort_unless($this->availableActions($surat, Auth::user())[$action] ?? false, 403);
    }

    private function finalize(Surat $surat): void
    {
        DB::transaction(function () use ($surat) {
            $locked = Surat::query()->lockForUpdate()->findOrFail($surat->id);
            if ($locked->surat_number) {
                return;
            }

            $rtCode = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $locked->rt?->name ?? 'RT'));

            $locked->update([
                'surat_number' => sprintf('%05d/SRW/%s/%02d/%d', $locked->id, $rtCode, now()->month, now()->year),
                'verification_code' => Str::upper(Str::random(24)),
            ]);
        });

        $surat->refresh();
    }
}
