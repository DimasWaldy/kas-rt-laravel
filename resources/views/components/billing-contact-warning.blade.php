@props(['rumah', 'showAction' => false])
@if($rumah && ($issue = $rumah->billingIssue()))
    <section role="status" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-200">
        <h2 class="font-bold">Rumah belum siap menerima tagihan</h2>
        <p class="mt-2">{{ $issue }}</p>
        <p class="mt-2">Pengurus perlu menetapkan PJ iuran yang sesuai. Setelah itu, bendahara dapat generate ulang tagihan untuk periode yang belum dibuat.</p>
        @if($showAction && auth()->user()->canManageWarga())
            <a href="{{ route('admin.rumah.edit', $rumah) }}" class="mt-3 inline-block rounded-xl bg-amber-800 px-4 py-2 font-bold text-white">Tetapkan PJ iuran</a>
        @elseif(! auth()->user()->canManageWarga())
            <p class="mt-2">Hubungi pengurus bila penanggung jawab iuran rumah Anda belum ditetapkan.</p>
        @endif
    </section>
@endif
