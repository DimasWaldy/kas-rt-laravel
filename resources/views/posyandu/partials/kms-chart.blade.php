@php
    $width = 900; $height = 430; $left = 58; $right = 20; $top = 24; $bottom = 48;
    $plotWidth = $width - $left - $right; $plotHeight = $height - $top - $bottom;
    $maxWeight = max(25, ceil(($kmsStandards->max('sd3') ?? 23) + 1));
    $x = fn ($month) => $left + ((float) $month / 60) * $plotWidth;
    $y = fn ($weight) => $top + $plotHeight - ((float) $weight / $maxWeight) * $plotHeight;
    $curve = fn ($column) => $kmsStandards->map(fn ($row) => number_format($x($row->usia_bulan), 2, '.', '').','.number_format($y($row->{$column}), 2, '.', ''))->implode(' ');
    $measurements = $balita->pemeriksaans->filter(fn ($item) => $item->usia_bulan <= 60);
@endphp

<div class="overflow-x-auto">
    <svg viewBox="0 0 {{ $width }} {{ $height }}" class="min-w-[720px] w-full" role="img" aria-label="Grafik KMS berat badan menurut umur {{ $balita->nama }}">
        <rect x="{{ $left }}" y="{{ $top }}" width="{{ $plotWidth }}" height="{{ $plotHeight }}" fill="#fff" stroke="#e2e8f0" />
        @for($month = 0; $month <= 60; $month += 6)
            <line x1="{{ $x($month) }}" y1="{{ $top }}" x2="{{ $x($month) }}" y2="{{ $top + $plotHeight }}" stroke="#e2e8f0" stroke-width="1" />
            <text x="{{ $x($month) }}" y="{{ $height - 20 }}" text-anchor="middle" font-size="11" fill="#64748b">{{ $month }}</text>
        @endfor
        @for($weight = 0; $weight <= $maxWeight; $weight += 5)
            <line x1="{{ $left }}" y1="{{ $y($weight) }}" x2="{{ $left + $plotWidth }}" y2="{{ $y($weight) }}" stroke="#e2e8f0" stroke-width="1" />
            <text x="{{ $left - 10 }}" y="{{ $y($weight) + 4 }}" text-anchor="end" font-size="11" fill="#64748b">{{ $weight }}</text>
        @endfor
        <polyline points="{{ $curve('sd3neg') }}" fill="none" stroke="#dc2626" stroke-width="2" stroke-dasharray="6 4" />
        <polyline points="{{ $curve('sd2neg') }}" fill="none" stroke="#f59e0b" stroke-width="2.5" />
        <polyline points="{{ $curve('sd0') }}" fill="none" stroke="#16a34a" stroke-width="3" />
        <polyline points="{{ $curve('sd1') }}" fill="none" stroke="#2563eb" stroke-width="2" />
        @if($measurements->isNotEmpty())
            <polyline points="{{ $measurements->map(fn ($item) => number_format($x($item->usia_bulan), 2, '.', '').','.number_format($y($item->berat_kg), 2, '.', ''))->implode(' ') }}" fill="none" stroke="#111827" stroke-width="2" />
            @foreach($measurements as $item)
                <circle cx="{{ $x($item->usia_bulan) }}" cy="{{ $y($item->berat_kg) }}" r="5" fill="#111827" stroke="#fff" stroke-width="2"><title>{{ $item->tanggal_pemeriksaan->format('d-m-Y') }}: {{ $item->berat_kg }} kg, Z {{ $item->z_score_bb_u }}</title></circle>
            @endforeach
        @endif
        <text x="{{ $left + $plotWidth / 2 }}" y="{{ $height - 3 }}" text-anchor="middle" font-size="12" font-weight="700" fill="#475569">Usia (bulan)</text>
        <text x="16" y="{{ $top + $plotHeight / 2 }}" text-anchor="middle" font-size="12" font-weight="700" fill="#475569" transform="rotate(-90 16 {{ $top + $plotHeight / 2 }})">Berat (kg)</text>
    </svg>
</div>
<div class="mt-3 flex flex-wrap gap-4 text-xs font-semibold text-slate-600">
    <span><i class="mr-1 inline-block h-0.5 w-5 bg-red-600 align-middle"></i> -3 SD</span>
    <span><i class="mr-1 inline-block h-0.5 w-5 bg-amber-500 align-middle"></i> -2 SD</span>
    <span><i class="mr-1 inline-block h-0.5 w-5 bg-green-600 align-middle"></i> Median WHO</span>
    <span><i class="mr-1 inline-block h-0.5 w-5 bg-blue-600 align-middle"></i> +1 SD</span>
    <span><i class="mr-1 inline-block h-2 w-2 rounded-full bg-slate-900 align-middle"></i> Hasil timbang</span>
</div>
