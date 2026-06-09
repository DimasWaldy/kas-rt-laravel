<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayTagihanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $paymentMethod = $this->input('payment_method');

        return [
            'tagihan_id' => ['required', 'integer'],
            'payment_method' => ['required', 'in:transfer,offline'],
            'bukti' => [
                $paymentMethod === 'transfer' ? 'required' : 'nullable',
                'file',
                'mimetypes:image/jpeg,image/png,application/pdf',
                'max:2048',
            ],
            'note' => [
                $paymentMethod === 'offline' ? 'required' : 'nullable',
                'string',
                'min:5',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'tagihan_id.required' => 'ID tagihan wajib ditentukan.',
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
            'payment_method.in' => 'Metode pembayaran harus berupa transfer atau offline.',
            'bukti.required' => 'Bukti pembayaran wajib diunggah untuk metode transfer.',
            'bukti.file' => 'Berkas bukti pembayaran tidak valid.',
            'bukti.mimetypes' => 'Format bukti pembayaran harus berupa gambar JPG/PNG atau PDF.',
            'bukti.max' => 'Ukuran berkas bukti pembayaran maksimal adalah 2 MB (2048 KB).',
            'note.required' => 'Catatan wajib diisi untuk pembayaran offline (misal: diserahkan ke siapa & tanggal).',
            'note.min' => 'Catatan pembayaran offline minimal harus terdiri dari 5 karakter.',
            'note.max' => 'Catatan pembayaran maksimal berisi 255 karakter.',
        ];
    }
}
