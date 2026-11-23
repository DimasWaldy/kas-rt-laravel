<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RukemBantuanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('approve-rukem') ?? false;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:10000'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_phone' => ['nullable', 'string', 'max:20'],
            'given_at' => ['required', 'date'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Nominal santunan duka wajib diisi.',
            'amount.min' => 'Nominal santunan minimal Rp 10.000.',
            'recipient_name.required' => 'Nama penerima / ahli waris wajib diisi.',
            'given_at.required' => 'Tanggal penyerahan santunan wajib diisi.',
            'proof.max' => 'Ukuran bukti kwitansi / foto serah terima maksimal 5MB.',
        ];
    }
}
