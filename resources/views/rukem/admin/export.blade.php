<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Dana Kas Rukun Kematian (Rukem) Tahun {{ $year }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 24px;
            font-size: 12px;
            line-height: 1.5;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .header h2 {
            margin: 4px 0 0 0;
            font-size: 14px;
            font-weight: normal;
        }
        .header p {
            margin: 2px 0 0 0;
            font-size: 11px;
            color: #475569;
        }
        .summary-box {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
        }
        .summary-card {
            flex: 1;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 12px;
            background-color: #f8fafc;
        }
        .summary-card .label {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
        }
        .summary-card .value {
            font-size: 16px;
            font-weight: bold;
            margin-top: 4px;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 20px;
            margin-bottom: 8px;
            border-left: 4px solid #059669;
            padding-left: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background-color: #f1f5f9;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }
        .signature-block {
            width: 200px;
            text-align: center;
        }
        .signature-space {
            height: 60px;
        }
        .print-btn {
            margin-bottom: 20px;
            padding: 8px 16px;
            background-color: #059669;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: right;">
        <button class="print-btn" onclick="window.print()">
            🖨️ Cetak / Simpan PDF
        </button>
    </div>

    <div class="header">
        <h1>PENGURUS RUKUN KEMATIAN (RUKEM) RT / RW</h1>
        <h2>SISTEM INFORMASI SMART RW LINGKUNGAN MANDIRI</h2>
        <p>Laporan Rekapitulasi Kas Sosial Duka & Penyaluran Santunan — Periode Tahun {{ $year }}</p>
    </div>

    <div class="summary-box">
        <div class="summary-card">
            <div class="label">Total Iuran Terverifikasi</div>
            <div class="value" style="color: #0369a1;">Rp {{ number_format($totalIuran, 0, ',', '.') }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Total Santunan Disalurkan</div>
            <div class="value" style="color: #e11d48;">Rp {{ number_format($totalSantunan, 0, ',', '.') }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Sisa Saldo Kas Rukem</div>
            <div class="value" style="color: #059669;">Rp {{ number_format($saldo, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="section-title">1. Daftar Santunan Kematian Disalurkan ({{ $bantuans->count() }} Penerima)</div>
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 30px;">No</th>
                <th>Tanggal Salur</th>
                <th>Penerima / Ahli Waris</th>
                <th>Almarhum / Almarhumah</th>
                <th>Wilayah RT</th>
                <th>Petugas Penyerah</th>
                <th class="text-right">Nominal Santunan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bantuans as $idx => $b)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($b->given_at)->translatedFormat('d M Y') }}</td>
                    <td>{{ $b->recipient_name }}</td>
                    <td>Alm. {{ $b->claim?->deceased_name }}</td>
                    <td>{{ $b->claim?->user?->rt ? 'RT ' . $b->claim->user->rt->name : '-' }}</td>
                    <td>{{ $b->giver?->name }}</td>
                    <td class="text-right">Rp {{ number_format($b->amount, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="color: #94a3b8;">Tidak ada santunan disalurkan pada tahun ini.</td>
                </tr>
            @endforelse
        </tbody>
        @if($bantuans->count() > 0)
            <tfoot>
                <tr>
                    <th colspan="6" class="text-right">TOTAL SANTUNAN DISALURKAN:</th>
                    <th class="text-right">Rp {{ number_format($totalSantunan, 0, ',', '.') }}</th>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="section-title">2. Berita Duka Tercatat di Lingkungan ({{ $claims->count() }} Laporan)</div>
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 30px;">No</th>
                <th>Nama Almarhum</th>
                <th>Pelapor / Hubungan</th>
                <th>Tanggal Wafat</th>
                <th>Lokasi Pemakaman</th>
                <th>Status Laporan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($claims as $idx => $c)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td><strong>{{ $c->deceased_name }}</strong></td>
                    <td>{{ $c->user?->name }} ({{ ucfirst(str_replace('_', ' ', $c->relationship)) }})</td>
                    <td>{{ \Carbon\Carbon::parse($c->death_date)->translatedFormat('d M Y') }}</td>
                    <td>{{ $c->cemetery_location ?? '-' }}</td>
                    <td>{{ ucfirst($c->status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="color: #94a3b8;">Tidak ada catatan duka pada tahun ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature-section">
        <div class="signature-block">
            <p>Mengetahui,<br><strong>Ketua RT / RW</strong></p>
            <div class="signature-space"></div>
            <p>( ........................................ )</p>
        </div>

        <div class="signature-block">
            <p>Dicetak Pada: {{ now()->translatedFormat('d F Y') }}<br><strong>Bendahara Kas Rukem</strong></p>
            <div class="signature-space"></div>
            <p>( {{ auth()->user()->name }} )</p>
        </div>
    </div>
</body>
</html>
