@extends('layouts.app')

@section('title', 'Ajukan Pinjaman')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <p class="text-sm font-semibold text-indigo-700">Koperasi Smart RW</p>
        <h1 class="text-2xl font-black text-slate-900">Ajukan Pinjaman</h1>
        <p class="mt-1 text-sm text-slate-500">Simulasikan total pengembalian sebelum mengirim pengajuan ke bendahara.</p>
    </div>

    @if(session('error'))
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ session('error') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1fr_22rem]" x-data="{ amount: Number(@js(old('amount', 0))), tenor: Number(@js(old('tenor_months', 1))), simpananWajib: Number(@js($simpananWajibTerverifikasi)), format(value) { return new Intl.NumberFormat('id-ID').format(value || 0) }, get fee() { return Math.round(this.amount * 0.02 * this.tenor) }, get total() { return this.amount + this.fee }, get highLoanNeedsSaving() { return this.amount >= 1000000 && this.simpananWajib < 50000 } }">
        <form method="POST" action="{{ route('koperasi.store-pinjam') }}" class="space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-xs">
            @csrf
            <div class="rounded-2xl border border-indigo-100 bg-indigo-50 p-4 text-sm text-indigo-900">
                <p class="font-black">Ketentuan pinjaman</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li>Wajib memiliki e-KTP fisik yang telah terverifikasi melalui Tap NFC Smartphone atau Reader RT.</li>
                    <li>Jasa pinjaman 2% flat per bulan.</li>
                    <li>Hanya boleh punya satu pinjaman aktif atau menunggu persetujuan.</li>
                    <li>Pinjaman Rp 1.000.000 atau lebih wajib punya simpanan wajib terverifikasi minimal Rp 50.000.</li>
                    <li>Pencairan menunggu persetujuan bendahara/pengurus.</li>
                </ul>
            </div>

            @if($hasVerifiedKtp)
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                            <i class="fa-solid fa-id-card-clip text-base"></i>
                        </div>
                        <div>
                            <p class="font-bold">e-KTP Fisik Terverifikasi (Syarat Pinjaman Terpenuhi)</p>
                            <p class="mt-0.5 text-xs text-emerald-700">
                                @php
                                    $verifiedAt = $rfidCard->ktp_verified_at ?? $rfidCard->koperasi_verified_at;
                                @endphp
                                Terverifikasi resmi via tap kartu pada {{ $verifiedAt?->translatedFormat('d M Y H:i') ?? '-' }} WIB.
                            </p>
                        </div>
                    </div>
                </div>
            @else
                <div class="rounded-2xl border border-amber-300 bg-amber-50 p-5 text-sm text-amber-950 shadow-xs">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-amber-200/60 text-amber-800">
                            <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                        </div>
                        <div class="space-y-2">
                            <div>
                                <h3 class="font-black text-amber-950">Wajib Verifikasi e-KTP Sebelum Mengajukan Pinjaman</h3>
                                <p class="mt-1 text-xs leading-relaxed text-amber-900">
                                    Demi keamanan dan akuntabilitas dana kas koperasi warga, setiap pengajuan pinjaman wajib memverifikasi kepemilikan fisik e-KTP melalui <strong>Tap NFC Smartphone</strong> atau <strong>Mesin Reader RT</strong>.
                                </p>
                            </div>
                            <div class="pt-1">
                                <a href="{{ route('warga.rfid.show') }}" class="inline-flex items-center gap-2 rounded-xl bg-amber-700 px-4 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-amber-800">
                                    <i class="fa-solid fa-wifi"></i>
                                    <span>Verifikasi e-KTP Sekarang (NFC HP / Reader RT)</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                <p class="font-black">Simpanan wajib terverifikasi Anda</p>
                <p class="mt-1 text-2xl font-black">Rp {{ number_format($simpananWajibTerverifikasi, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs font-semibold text-emerald-700">Syarat pinjaman Rp 1.000.000 atau lebih: minimal Rp 50.000.</p>
            </div>

            <div>
                <label for="amount" class="text-sm font-bold text-slate-700">Nominal Pinjaman</label>
                <input id="amount" name="amount" type="number" min="50000" required x-model.number="amount" class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Minimal 50000">
                <p x-show="highLoanNeedsSaving" x-cloak class="mt-2 rounded-xl bg-red-50 px-3 py-2 text-xs font-semibold text-red-700">
                    Pinjaman Rp 1.000.000 atau lebih butuh simpanan wajib terverifikasi minimal Rp 50.000.
                </p>
                @error('amount')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="tenor_months" class="text-sm font-bold text-slate-700">Tenor</label>
                <select id="tenor_months" name="tenor_months" required x-model.number="tenor" class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @for($i = 1; $i <= 24; $i++)
                        <option value="{{ $i }}">{{ $i }} bulan</option>
                    @endfor
                </select>
                @error('tenor_months')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('koperasi.index') }}" class="rounded-xl bg-slate-100 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-200">Batal</a>
                @if($hasVerifiedKtp)
                    <button type="submit" class="rounded-xl bg-indigo-700 px-5 py-3 text-sm font-bold text-white hover:bg-indigo-800">Ajukan Pinjaman</button>
                @else
                    <button type="button" disabled class="cursor-not-allowed rounded-xl bg-slate-200 px-5 py-3 text-sm font-bold text-slate-500" title="Verifikasi e-KTP terlebih dahulu">
                        <i class="fa-solid fa-lock mr-1.5"></i> Wajib Tap e-KTP Terlebih Dahulu (Pinjaman Terkunci)
                    </button>
                @endif
            </div>
        </form>

        <aside class="rounded-3xl bg-linear-to-br/srgb from-indigo-700 to-blue-600 p-6 text-white shadow-lg shadow-indigo-700/20">
            <p class="text-sm font-bold text-indigo-100">Estimasi Pengembalian</p>
            <div class="mt-6 space-y-4">
                <div class="rounded-2xl bg-white/15 p-4">
                    <p class="text-xs font-bold text-indigo-100">Pokok</p>
                    <p class="mt-1 text-2xl font-black">Rp <span x-text="format(amount)"></span></p>
                </div>
                <div class="rounded-2xl bg-white/15 p-4">
                    <p class="text-xs font-bold text-indigo-100">Jasa 2% x tenor</p>
                    <p class="mt-1 text-2xl font-black">Rp <span x-text="format(fee)"></span></p>
                </div>
                <div class="rounded-2xl bg-white p-4 text-indigo-900">
                    <p class="text-xs font-bold text-indigo-500">Total Bayar</p>
                    <p class="mt-1 text-3xl font-black">Rp <span x-text="format(total)"></span></p>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection
