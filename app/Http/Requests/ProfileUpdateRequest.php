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
        $wargaId = $this->user()->warga?->id;

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
            'is_penanggung_jawab_rumah' => ['nullable', 'boolean'],
            'phone' => ['sometimes', 'required', 'regex:/^[0-9]{10,13}$/'],
            'nik' => [
                'nullable',
                'digits:16',
                Rule::unique('wargas', 'nik')->ignore($wargaId),
            ],
            'status_dalam_kk' => ['nullable', 'in:kepala_keluarga,anggota'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Nomor HP harus berisi angka saja, minimal 10 digit dan maksimal 13 digit.',
            'nik.digits' => 'NIK harus berisi 16 digit angka.',
            'nik.unique' => 'NIK tersebut sudah terdaftar untuk warga lain.',
        ];
    }
}
