@extends('layouts.app')

@section('title', 'Periksa Domisili Warga')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <a href="{{ route('verifikasi-warga.index') }}" class="text-sm font-bold text-emerald-700">← Kembali ke Verifikasi Warga</a>
    <header><h1 class="text-2xl font-black text-slate-800">Periksa domisili warga</h1><p class="mt-2 text-sm text-slate-500">Pastikan warga benar tinggal di rumah yang diajukan sebelum menyetujui.</p></header>
    @if($errors->any())
        <ul role="alert" class="list-inside list-disc rounded-xl bg-rose-50 p-4 text-sm text-rose-700">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    @endif
    <section class="rounded-3xl bg-white p-6 shadow-xs">
        <h2 class="font-bold text-slate-800">{{ $warga->nama_lengkap }}</h2>
        <dl class="mt-5 space-y-4 text-sm text-slate-700">
            <div><dt class="font-bold">Wilayah akun</dt><dd>{{ $warga->user->rt?->name }} / {{ $warga->user->rt?->rw?->name }}</dd></div>
            <div><dt class="font-bold">Nomor HP</dt><dd>{{ $warga->user->phone }}</dd></div>
            <div><dt class="font-bold">Rumah yang diajukan</dt><dd class="whitespace-pre-line">{{ $warga->rumahDiajukan?->label ?? $warga->alamat_domisili }}</dd></div>
            <div><dt class="font-bold">Jenis pengajuan</dt><dd>{{ $warga->mode_domisili === 'existing' ? 'Memilih rumah yang terdaftar' : 'Mengajukan alamat rumah' }}</dd></div>
            @if($warga->rumahDiajukan)
                <div><dt class="font-bold">Penanggung jawab iuran rumah</dt><dd>{{ $warga->rumahDiajukan->penanggungJawab?->name ?? 'Belum ditetapkan' }}</dd></div>
            @endif
        </dl>
        <p class="mt-5 rounded-xl bg-amber-50 p-4 text-sm text-amber-900">Persetujuan mengizinkan warga mengakses data rumah sesuai haknya. Cocokkan alamat dan hubungan dengan penghuni rumah. Penanggung jawab iuran dan data KK tetap ditetapkan melalui pengelolaan masing-masing.</p>
        <form method="POST" action="{{ route('verifikasi-warga.domisili.setujui', $warga) }}" class="mt-5">
            @csrf
            @method('PATCH')
            <button class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">Setujui domisili</button>
        </form>
    </section>
    <section class="rounded-3xl bg-white p-6 shadow-xs">
        <h2 class="font-bold text-slate-800">Alamat belum sesuai?</h2>
        <p class="mt-2 text-sm text-slate-500">Kembalikan pengajuan dengan petunjuk yang jelas. Warga tetap dapat memperbaiki domisili melalui akunnya.</p>
        <form method="POST" action="{{ route('verifikasi-warga.domisili.perbaiki', $warga) }}" class="mt-5 space-y-3">
            @csrf
            @method('PATCH')
            <label for="catatan-domisili" class="block text-sm font-bold text-slate-700">Catatan perbaikan</label>
            <textarea id="catatan-domisili" name="catatan_domisili" rows="3" minlength="10" maxlength="1000" required class="w-full rounded-xl border-slate-300" placeholder="Jelaskan alamat atau pilihan rumah yang perlu diperbaiki">{{ old('catatan_domisili') }}</textarea>
            <button class="rounded-xl border border-amber-300 px-5 py-3 text-sm font-bold text-amber-900 hover:bg-amber-50">Kembalikan untuk diperbaiki</button>
        </form>
    </section>
</div>
@endsection
