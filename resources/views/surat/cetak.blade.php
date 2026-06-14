@php
    $rt = $surat->rt;
    $rw = $rt?->rw;
    $pemohon = $surat->user;
    $alamatPemohon = $pemohon?->rumah?->alamat ?? 'Alamat pemohon belum diatur';
    $namaRt = $rt?->name ?? 'RT belum diatur';
    $namaRw = $rw?->name ?? 'RW belum diatur';
    $kota = $rw?->kota ?? 'Kota belum diatur';
    $tanggalApproval = $surat->approved_rw_at ?? $surat->approved_rt_at;
    $tanggalSurat = $tanggalApproval?->translatedFormat('d F Y') ?? 'Tanggal belum tersedia';

    $ketuaRt = $surat->approverRt?->name
        ?? \App\Models\User::query()
            ->where('rt_id', $surat->rt_id)
            ->whereHas('role', fn ($query) => $query->where('name', 'ketua_rt'))
            ->value('name')
        ?? '[Belum ditentukan]';

    $ketuaRw = $surat->approverRw?->name
        ?? \App\Models\User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'ketua_rw'))
            ->value('name')
        ?? '[Belum ditentukan]';

    $verificationUrl = route('surat.verify-public', $surat->verification_code);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $surat->surat_number ?? 'Surat Smart RW' }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #eef2f7;
        }

        body {
            color: #000;
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            line-height: 1.5;
        }

        .surat-container {
            display: flex;
            width: 21cm;
            min-height: 29.7cm;
            margin: 20px auto;
            padding: 1.6cm 2.2cm 1.5cm;
            flex-direction: column;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10;
            padding: 12px 18px;
            border: 0;
            border-radius: 8px;
            background: #047857;
            color: #fff;
            font: 700 14px Arial, sans-serif;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.18);
        }

        .kop-surat {
            display: grid;
            grid-template-columns: 95px 1fr 95px;
            align-items: center;
            min-height: 105px;
            padding-bottom: 9px;
            border-bottom: 3px solid #000;
            text-align: center;
        }

        .kop-surat::after {
            grid-column: 1 / -1;
            height: 1px;
            margin-top: 3px;
            background: #000;
            content: "";
        }

        .logo-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 82px;
        }

        .logo-placeholder {
            display: flex;
            width: 72px;
            height: 80px;
            align-items: center;
            justify-content: center;
            border: 1px solid #000;
            font: 9pt Arial, sans-serif;
            color: #555;
        }

        .kop-text h1,
        .kop-text h2,
        .kop-text p {
            margin: 0;
        }

        .kop-text h1 {
            font-size: 15pt;
            line-height: 1.25;
        }

        .kop-text h2 {
            margin-top: 2px;
            font-size: 12pt;
            line-height: 1.3;
        }

        .kop-text p {
            margin-top: 3px;
            font-size: 10.5pt;
            line-height: 1.25;
        }

        .surat-heading {
            margin: 25px 0 18px;
        }

        .surat-heading table {
            width: 100%;
            border-collapse: collapse;
        }

        .surat-heading td {
            padding: 1px 0;
            vertical-align: top;
        }

        .surat-heading .label {
            width: 70px;
        }

        .surat-heading .separator {
            width: 14px;
        }

        .recipient {
            margin: 22px 0 25px;
        }

        .recipient p,
        .letter-body p {
            margin: 0 0 14px;
        }

        .letter-body {
            text-align: justify;
        }

        .identity-table {
            width: 92%;
            margin: 10px auto 17px;
            border-collapse: collapse;
        }

        .identity-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        .identity-table .label {
            width: 115px;
        }

        .identity-table .separator {
            width: 15px;
        }

        .signatures {
            display: flex;
            min-height: 175px;
            margin-top: 40px;
            justify-content: space-between;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .signature-column {
            width: 43%;
            text-align: center;
        }

        .signature-column.rt-only {
            margin-left: auto;
        }

        .signature-column p {
            margin: 0;
        }

        .signature-name {
            margin-top: 62px !important;
            font-weight: bold;
            text-decoration: underline;
        }

        .document-footer {
            margin-top: auto;
            padding-top: 9px;
            border-top: 1px solid #000;
            text-align: center;
            font: 8.5pt/1.4 Arial, sans-serif;
            overflow-wrap: anywhere;
        }

        .document-footer p {
            margin: 1px 0;
        }

        .page-break {
            page-break-before: always;
        }

        @page {
            size: A4;
            margin: 0;
        }

        @media print {
            nav,
            header:not(.kop-surat),
            .sidebar,
            .no-print,
            button,
            .btn {
                display: none !important;
            }

            html,
            body {
                width: 21cm;
                min-height: 29.7cm;
                background: #fff;
            }

            body {
                color: #000;
                font-family: "Times New Roman", Times, serif;
                font-size: 12pt;
            }

            .surat-container {
                width: 21cm;
                min-height: 29.7cm;
                margin: 0 auto;
                padding: 1.6cm 2.2cm 1.5cm;
                box-shadow: none;
            }

            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>
<body>
    <button type="button" onclick="window.print()" class="print-button no-print">
        Cetak / Simpan PDF
    </button>

    <main class="surat-container">
        <header class="kop-surat">
            <div class="logo-wrapper">
                @if(file_exists(public_path('images/logo-bandung.png')))
                    <img src="{{ asset('images/logo-bandung.png') }}" alt="Logo {{ $kota }}" style="height: 80px; max-width: 82px; object-fit: contain;">
                @else
                    <div class="logo-placeholder">Logo Kota</div>
                @endif
            </div>

            <div class="kop-text">
                <h1>RUKUN TETANGGA {{ strtoupper($namaRt) }} / RUKUN WARGA {{ strtoupper($namaRw) }}</h1>
                <h2>KELURAHAN {{ strtoupper($rw?->kelurahan ?? 'Belum diatur') }} - KECAMATAN {{ strtoupper($rw?->kecamatan ?? 'Belum diatur') }}</h2>
                <p>{{ $rw?->address ?? 'Alamat sekretariat belum diatur' }}</p>
            </div>

            <div class="logo-wrapper" aria-hidden="true"></div>
        </header>

        <section class="surat-heading">
            <table>
                <tr>
                    <td class="label">Nomor</td>
                    <td class="separator">:</td>
                    <td>{{ $surat->surat_number ?? 'Nomor surat belum diterbitkan' }}</td>
                </tr>
                <tr>
                    <td class="label">Perihal</td>
                    <td class="separator">:</td>
                    <td>{{ $surat->type_label }}</td>
                </tr>
            </table>
        </section>

        <section class="recipient">
            <p>Kepada Yth,</p>
            <p>Bapak/Ibu/Sdr. <strong>{{ $pemohon?->name ?? 'Nama pemohon belum tersedia' }}</strong><br>di Tempat</p>
        </section>

        <section class="letter-body">
            <p>Dengan hormat,</p>

            @switch($surat->type)
                @case('domisili')
                    <p>Yang bertanda tangan di bawah ini, pengurus {{ $namaRt }} {{ $namaRw }}, menerangkan bahwa:</p>
                    <table class="identity-table">
                        <tr><td class="label">Nama</td><td class="separator">:</td><td>{{ $pemohon?->name ?? '-' }}</td></tr>
                        <tr><td class="label">NIK</td><td class="separator">:</td><td>{{ $pemohon?->nik ?? '-' }}</td></tr>
                        <tr><td class="label">Alamat</td><td class="separator">:</td><td>{{ $alamatPemohon }}</td></tr>
                        <tr><td class="label">Wilayah</td><td class="separator">:</td><td>{{ $namaRt }} / {{ $namaRw }}</td></tr>
                    </table>
                    <p>adalah benar warga yang berdomisili di wilayah kami.</p>
                    <p>Surat keterangan domisili ini dibuat untuk keperluan: <strong>{{ $surat->purpose }}</strong>.</p>
                    @break

                @case('pengantar')
                    <p>Yang bertanda tangan di bawah ini, pengurus {{ $namaRt }} {{ $namaRw }}, menerangkan bahwa nama tersebut di atas adalah benar warga kami dan bermaksud mengurus keperluan: <strong>{{ $surat->purpose }}</strong>.</p>
                    <p>Kami mohon kepada pihak yang berwenang untuk dapat memberikan bantuan seperlunya.</p>
                    @break

                @case('keterangan_usaha')
                    <p>Yang bertanda tangan di bawah ini menerangkan bahwa:</p>
                    <table class="identity-table">
                        <tr><td class="label">Nama</td><td class="separator">:</td><td>{{ $pemohon?->name ?? '-' }}</td></tr>
                        <tr><td class="label">Alamat</td><td class="separator">:</td><td>{{ $alamatPemohon }}</td></tr>
                        <tr><td class="label">Wilayah</td><td class="separator">:</td><td>{{ $namaRt }} / {{ $namaRw }}</td></tr>
                    </table>
                    <p>adalah benar warga kami yang menjalankan usaha:</p>
                    <p><strong>{{ $surat->content ?: 'Keterangan usaha belum dicantumkan' }}</strong></p>
                    <p>di alamat tersebut di atas. Surat ini dibuat untuk keperluan: <strong>{{ $surat->purpose }}</strong>.</p>
                    @break

                @case('keterangan_tidak_mampu')
                    <p>Yang bertanda tangan di bawah ini menerangkan bahwa warga tersebut di atas adalah benar termasuk dalam kategori warga tidak mampu berdasarkan data yang ada di wilayah kami.</p>
                    <p>Surat ini dibuat untuk keperluan: <strong>{{ $surat->purpose }}</strong>.</p>
                    @break

                @default
                    <p>Yang bertanda tangan di bawah ini, pengurus {{ $namaRt }} {{ $namaRw }}, menerangkan bahwa nama tersebut di atas adalah benar warga kami. Surat ini diterbitkan untuk keperluan: <strong>{{ $surat->purpose }}</strong>.</p>
            @endswitch

            @if($surat->content && $surat->type !== 'keterangan_usaha')
                <p>Keterangan tambahan: {{ $surat->content }}</p>
            @endif

            <p>Demikian surat keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.</p>
        </section>

        <section class="signatures">
            @if($surat->requires_rw)
                <div class="signature-column">
                    <p>{{ $kota }}, {{ $tanggalSurat }}</p>
                    <p>Ketua {{ $namaRw }}</p>
                    <p class="signature-name">{{ $ketuaRw }}</p>
                    <p>Ketua {{ $namaRw }}</p>
                </div>
            @endif

            <div class="signature-column {{ $surat->requires_rw ? '' : 'rt-only' }}">
                <p>{{ $kota }}, {{ $tanggalSurat }}</p>
                <p>Ketua {{ $namaRt }}</p>
                <p class="signature-name">{{ $ketuaRt }}</p>
                <p>Ketua {{ $namaRt }}</p>
            </div>
        </section>

        <footer class="document-footer">
            <p>Dokumen ini diterbitkan melalui Smart RW.</p>
            <p>Verifikasi keaslian: {{ $verificationUrl }}</p>
            <p>Kode: {{ $surat->verification_code ?? 'Kode verifikasi belum tersedia' }}</p>
        </footer>
    </main>
</body>
</html>
