<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RukemClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('submit-rukem') ?? false;
    }

    public function rules(): array
    {
        return [
            'deceased_name' => ['required', 'string', 'max:255'],
            'nik_deceased' => ['nullable', 'string', 'digits:16'],
            'relationship' => ['required', 'string', 'in:orang_tua,pasangan,anak,saudara,famili_lain,tetangga'],
            'death_date' => ['required', 'date', 'before_or_equal:today'],
            'funeral_date' => ['nullable', 'date'],
            'cemetery_location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'deceased_name.required' => 'Nama lengkap almarhum/almarhumah wajib diisi.',
            'relationship.required' => 'Hubungan keluarga/pelapor wajib dipilih.',
            'death_date.required' => 'Tanggal wafat wajib diisi.',
            'death_date.before_or_equal' => 'Tanggal wafat tidak boleh di masa mendatang.',
            'proof.max' => 'Ukuran berkas surat keterangan duka maksimal 5MB.',
        ];
    }
}
