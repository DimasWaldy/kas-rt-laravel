<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penerimaan Iuran Warga - {{ $periodeNama }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #0f172a;
            margin: 0;
            padding: 24px;
            font-size: 11px;
            line-height: 1.5;
            background-color: #ffffff;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 17px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header h2 {
            margin: 4px 0 0 0;
            font-size: 13px;
            font-weight: normal;
            color: #334155;
        }
        .header p {
            margin: 2px 0 0 0;
            font-size: 10px;
            color: #64748b;
        }
        .summary-box {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
        }
        .summary-card {
            flex: 1;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px 12px;
            background-color: #f8fafc;
        }
        .summary-card .label {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.5px;
        }
        .summary-card .value {
            font-size: 15px;
            font-weight: bold;
            margin-top: 4px;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 18px;
            margin-bottom: 8px;
            border-left: 3px solid #0d9488;
            padding-left: 8px;
            color: #0f172a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 10.5px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 5px 7px;
            text-align: left;
        }
        th {
            background-color: #f1f5f9;
            font-weight: bold;
            color: #1e293b;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-lunas {
            background-color: #dcfce7;
            color: #166534;
        }
        .badge-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        .badge-unpaid {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .signature-section {
            margin-top: 36px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }
        .signature-block {
            width: 200px;
            text-align: center;
        }
        .signature-space {
            height: 55px;
        }
        .print-btn {
            margin-bottom: 20px;
            padding: 8px 16px;
            background-color: #0d9488;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 12px;
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
        <h1>LAPORAN REKAPITULASI PENERIMAAN IURAN WARGA</h1>
        <h2>SISTEM INFORMASI SMART RW — {{ strtoupper($rtLabel) }}</h2>
        <p>Periode Bulan: {{ $periodeNama }} • Status Filter: {{ strtoupper($statusFilter) }}</p>
    </div>

    <div class="summary-box">
        <div class="summary-card">
            <div class="label">Total Target Iuran</div>
            <div class="value" style="color: #334155;">Rp {{ number_format($totalNominal, 0, ',', '.') }}</div>
            <div style="font-size: 9px; color: #64748b; margin-top: 2px;">{{ $totalTagihan }} Tagihan Terbit</div>
        </div>
        <div class="summary-card">
            <div class="label">Total Iuran Terbayar (Lunas)</div>
            <div class="value" style="color: #059669;">Rp {{ number_format($totalLunas, 0, ',', '.') }}</div>
            <div style="font-size: 9px; color: #059669; margin-top: 2px;">{{ $countLunas }} Warga Melunasi</div>
        </div>
        <div class="summary-card">
            <div class="label">Sisa Tunggakan Belum Bayar</div>
            <div class="value" style="color: #e11d48;">Rp {{ number_format($totalTunggakan, 0, ',', '.') }}</div>
            <div style="font-size: 9px; color: #e11d48; margin-top: 2px;">{{ $countBelumLunas }} Warga Menunggak</div>
        </div>
        <div class="summary-card">
            <div class="label">Tingkat Kepatuhan Warga</div>
            <div class="value" style="color: #0d9488;">{{ $persentaseLunas }}%</div>
            <div style="font-size: 9px; color: #64748b; margin-top: 2px;">Rasio Pembayaran Lunas</div>
        </div>
    </div>

    <div class="section-title">Daftar Rincian Pembayaran Iuran Warga ({{ $tagihans->count() }} Data)</div>
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 25px;">No</th>
                <th style="width: 85px;">Kode Rumah</th>
                <th>Nama Warga / Kepala Keluarga</th>
                <th style="width: 55px;" class="text-center">RT</th>
                <th>Komponen Iuran</th>
                <th class="text-right" style="width: 90px;">Nominal</th>
                <th class="text-center" style="width: 80px;">Status</th>
                <th style="width: 80px;">Tgl Bayar</th>
                <th style="width: 70px;">Metode</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tagihans as $idx => $t)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td><strong>{{ $t->rumah?->kode_rumah ?? '-' }}</strong></td>
                    <td>
                        {{ $t->user?->name ?? 'Belum Ditautkan' }}
                        @if($t->rumah?->alamat)
                            <div style="font-size: 9px; color: #64748b;">{{ $t->rumah->alamat }}</div>
                        @endif
                    </td>
                    <td class="text-center">{{ $t->rt?->name ?? '-' }}</td>
                    <td>{{ $t->judul }}</td>
                    <td class="text-right" style="font-weight: bold;">
                        Rp {{ number_format($t->total, 0, ',', '.') }}
                    </td>
                    <td class="text-center">
                        @if($t->status === 'lunas')
                            <span class="badge badge-lunas">LUNAS</span>
                        @elseif(in_array($t->status, ['pending_transfer', 'pending_offline']))
                            <span class="badge badge-pending">PENDING</span>
                        @else
                            <span class="badge badge-unpaid">BELUM BAYAR</span>
                        @endif
                    </td>
                    <td>{{ $t->paid_at ? $t->paid_at->translatedFormat('d M Y') : '-' }}</td>
                    <td style="text-transform: uppercase;">{{ $t->payment_method ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="color: #94a3b8; padding: 12px;">
                        Tidak ada catatan tagihan iuran pada periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background-color: #f1f5f9; font-weight: bold;">
                <td colspan="5" class="text-right">REKAPITULASI TOTAL:</td>
                <td class="text-right">Rp {{ number_format($totalNominal, 0, ',', '.') }}</td>
                <td colspan="3" class="text-center" style="color: #059669;">
                    Terbayar: Rp {{ number_format($totalLunas, 0, ',', '.') }}
                </td>
            </tr>
        </tfoot>
    </table>

    <div class="signature-section">
        <div class="signature-block">
            <p>Mengetahui,<br><strong>Ketua RT / RW</strong></p>
            <div class="signature-space"></div>
            <p>( ........................................ )</p>
        </div>

        <div class="signature-block">
            <p>Dicetak pada: {{ now()->translatedFormat('d F Y') }}<br><strong>Bendahara RT</strong></p>
            <div class="signature-space"></div>
            <p>( {{ auth()->user()->name }} )</p>
        </div>
    </div>
</body>
</html>
