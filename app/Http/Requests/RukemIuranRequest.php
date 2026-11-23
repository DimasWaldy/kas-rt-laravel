<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RukemIuranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('submit-rukem') ?? false;
    }

    public function rules(): array
    {
        return [
            'period_month' => ['required', 'integer', 'between:1,12'],
            'period_year' => ['required', 'integer', 'min:2020', 'max:2035'],
            'amount' => ['required', 'numeric', 'min:1000'],
            'proof' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'period_month.required' => 'Bulan periode iuran wajib dipilih.',
            'period_year.required' => 'Tahun periode iuran wajib diisi.',
            'amount.required' => 'Nominal iuran wajib diisi.',
            'amount.min' => 'Nominal iuran minimal Rp 1.000.',
            'proof.image' => 'Bukti pembayaran harus berupa gambar.',
            'proof.max' => 'Ukuran bukti transfer maksimal 3MB.',
        ];
    }
}
