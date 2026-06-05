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
            'no_kk' => ['sometimes', 'required', 'digits:16'],
            'is_kepala_keluarga' => ['nullable', 'boolean'],
            'is_penanggung_jawab_rumah' => ['nullable', 'boolean'],
            'jumlah_anggota_keluarga' => ['sometimes', 'required', 'integer', 'min:1', 'max:20'],
            'phone' => ['sometimes', 'required', 'regex:/^[0-9]{10,13}$/'],
            'rt' => ['sometimes', 'required', 'regex:/^[0-9]{1,3}$/'],
            'rw' => ['sometimes', 'required', 'regex:/^[0-9]{1,3}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'no_kk.digits' => 'Nomor KK harus berisi 16 digit angka.',
            'phone.regex' => 'Nomor HP harus berisi angka saja, minimal 10 digit dan maksimal 13 digit.',
            'rt.regex' => 'RT harus berisi angka saja, maksimal 3 digit.',
            'rw.regex' => 'RW harus berisi angka saja, maksimal 3 digit.',
        ];
    }
}
