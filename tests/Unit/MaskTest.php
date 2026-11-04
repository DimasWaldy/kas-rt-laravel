<?php

use App\Support\Mask;

test('mask nik membatasi digit yang ditampilkan', function (?string $nik, string $expected) {
    expect(Mask::nik($nik))->toBe($expected);
})->with([
    'null' => [null, '-'],
    'kosong' => ['', '-'],
    'spasi' => ['   ', '-'],
    '16 digit' => ['3273010101900001', '3273********0001'],
    'awalan nol' => ['0012010101900001', '0012********0001'],
    'pendek' => ['12345', '********45'],
    'satu digit' => ['7', '********7'],
    'panjang' => ['123456789012345678', '********78'],
    'bukan 16 digit numerik' => ['abcdefghijklmnop', '********op'],
]);
