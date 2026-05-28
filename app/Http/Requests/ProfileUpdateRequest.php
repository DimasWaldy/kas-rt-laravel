<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'rumah_id' => ['nullable', 'integer', 'exists:rumahs,id'],
            'rumah_kode' => ['nullable', 'string', 'max:50'],
            'rumah_alamat' => ['nullable', 'string', 'max:500'],
            'no_kk' => ['sometimes', 'required', 'string', 'max:50'],
            'is_kepala_keluarga' => ['nullable', 'boolean'],
            'is_penanggung_jawab_rumah' => ['nullable', 'boolean'],
            'jumlah_anggota_keluarga' => ['sometimes', 'required', 'integer', 'min:1', 'max:20'],
            'phone' => ['sometimes', 'required', 'string', 'max:20'],
            'rt' => ['sometimes', 'required', 'string', 'max:10'],
            'rw' => ['sometimes', 'required', 'string', 'max:10'],
        ];
    }
}
