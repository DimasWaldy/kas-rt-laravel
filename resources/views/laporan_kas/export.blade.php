<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kas RT - Periode {{ $periodeLabel }}</title>
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
            border-left: 3px solid #059669;
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
        .badge-masuk {
            background-color: #dcfce7;
            color: #166534;
        }
        .badge-keluar {
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
            background-color: #059669;
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
        <h1>LAPORAN KAS UMUM & KEUANGAN RT</h1>
        <h2>SISTEM INFORMASI SMART RW — {{ strtoupper($rtLabel) }}</h2>
        <p>Buku Kas Pembukuan Periode: {{ $periodeLabel }} @if($kategoriFilter) • Kategori: {{ $kategoriFilter }} @endif</p>
    </div>

    <div class="summary-box">
        <div class="summary-card">
            <div class="label">Saldo Awal</div>
            <div class="value" style="color: #475569;">Rp {{ number_format($saldoAwal, 0, ',', '.') }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Total Kas Masuk</div>
            <div class="value" style="color: #059669;">Rp {{ number_format($totalMasuk, 0, ',', '.') }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Total Kas Keluar</div>
            <div class="value" style="color: #e11d48;">Rp {{ number_format($totalKeluar, 0, ',', '.') }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Saldo Akhir</div>
            <div class="value" style="color: {{ $saldoAkhir >= 0 ? '#0f172a' : '#e11d48' }};">
                Rp {{ number_format($saldoAkhir, 0, ',', '.') }}
            </div>
        </div>
    </div>

    @if($ringkasanKategori->count() > 0)
        <div class="section-title">1. Ringkasan Penerimaan & Pengeluaran Berdasarkan Kategori</div>
        <table>
            <thead>
                <tr>
                    <th class="text-center" style="width: 30px;">No</th>
                    <th>Nama Kategori</th>
                    <th class="text-center" style="width: 80px;">Transaksi</th>
                    <th class="text-right" style="width: 130px;">Kas Masuk</th>
                    <th class="text-right" style="width: 130px;">Kas Keluar</th>
                    <th class="text-right" style="width: 130px;">Surplus / Defisit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ringkasanKategori as $idx => $kat)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td><strong>{{ $kat['kategori'] }}</strong></td>
                        <td class="text-center">{{ $kat['transaksi'] }} kali</td>
                        <td class="text-right" style="color: #059669;">
                            {{ $kat['masuk'] ? 'Rp ' . number_format($kat['masuk'], 0, ',', '.') : '-' }}
                        </td>
                        <td class="text-right" style="color: #e11d48;">
                            {{ $kat['keluar'] ? 'Rp ' . number_format($kat['keluar'], 0, ',', '.') : '-' }}
                        </td>
                        <td class="text-right" style="font-weight: bold; color: {{ $kat['saldo'] >= 0 ? '#0f172a' : '#e11d48' }};">
                            Rp {{ number_format($kat['saldo'], 0, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background-color: #f8fafc; font-weight: bold;">
                    <td colspan="3" class="text-right">TOTAL MUTASI PERIODE:</td>
                    <td class="text-right" style="color: #059669;">Rp {{ number_format($totalMasuk, 0, ',', '.') }}</td>
                    <td class="text-right" style="color: #e11d48;">Rp {{ number_format($totalKeluar, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($saldoPeriode, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    <div class="section-title">2. Buku Mutasi Kas Kronologis ({{ $riwayatWithBalance->count() }} Transaksi)</div>
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 25px;">No</th>
                <th style="width: 75px;">Tanggal</th>
                <th class="text-center" style="width: 55px;">Tipe</th>
                <th style="width: 120px;">Kategori</th>
                <th>Uraian Keterangan</th>
                <th style="width: 80px;">Pencatat</th>
                <th class="text-right" style="width: 90px;">Debit (Masuk)</th>
                <th class="text-right" style="width: 90px;">Kredit (Keluar)</th>
                <th class="text-right" style="width: 95px;">Saldo Berjalan</th>
            </tr>
        </thead>
        <tbody>
            <tr style="background-color: #f8fafc; font-weight: bold;">
                <td class="text-center">-</td>
                <td>{{ $tanggalMulai->translatedFormat('d M Y') }}</td>
                <td class="text-center"><span class="badge" style="background:#e2e8f0; color:#334155;">SALDO</span></td>
                <td>Saldo Awal Periode</td>
                <td>Saldo Kas Sebelum Periode Berjalan</td>
                <td class="text-center">-</td>
                <td class="text-right">-</td>
                <td class="text-right">-</td>
                <td class="text-right">Rp {{ number_format($saldoAwal, 0, ',', '.') }}</td>
            </tr>
            @forelse($riwayatWithBalance as $idx => $trx)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($trx['tanggal'])->translatedFormat('d M Y') }}</td>
                    <td class="text-center">
                        <span class="badge {{ $trx['tipe'] === 'Masuk' ? 'badge-masuk' : 'badge-keluar' }}">
                            {{ strtoupper($trx['tipe']) }}
                        </span>
                    </td>
                    <td>{{ $trx['kategori'] }}</td>
                    <td>{{ $trx['keterangan'] }}</td>
                    <td>{{ $trx['user'] }}</td>
                    <td class="text-right" style="color: #059669; font-weight: bold;">
                        {{ $trx['debit'] ? 'Rp ' . number_format($trx['debit'], 0, ',', '.') : '-' }}
                    </td>
                    <td class="text-right" style="color: #e11d48; font-weight: bold;">
                        {{ $trx['kredit'] ? 'Rp ' . number_format($trx['kredit'], 0, ',', '.') : '-' }}
                    </td>
                    <td class="text-right" style="font-weight: bold;">
                        Rp {{ number_format($trx['saldo_berjalan'], 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="color: #94a3b8; padding: 12px;">
                        Tidak ada transaksi mutasi kas tercatat pada rentang periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background-color: #f1f5f9; font-weight: bold;">
                <td colspan="6" class="text-right">SALDO AKHIR PERIODE:</td>
                <td class="text-right" style="color: #059669;">Rp {{ number_format($totalMasuk, 0, ',', '.') }}</td>
                <td class="text-right" style="color: #e11d48;">Rp {{ number_format($totalKeluar, 0, ',', '.') }}</td>
                <td class="text-right" style="font-size: 11.5px; color: {{ $saldoAkhir >= 0 ? '#059669' : '#e11d48' }};">
                    Rp {{ number_format($saldoAkhir, 0, ',', '.') }}
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
            <p>Dicetak pada: {{ now()->translatedFormat('d F Y') }}<br><strong>Bendahara Kas RT</strong></p>
            <div class="signature-space"></div>
            <p>( {{ auth()->user()->name }} )</p>
        </div>
    </div>
</body>
</html>
