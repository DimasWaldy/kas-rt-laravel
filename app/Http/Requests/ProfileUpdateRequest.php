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
            // These assignments control access to other residents' data.
            // Only the scoped management workflow may change them.
            'rt_id' => ['sometimes', $this->unchangedAssignment('rt_id')],
            'rumah_id' => ['sometimes', $this->unchangedAssignment('rumah_id')],
            'rumah_kode' => ['prohibited'],
            'rumah_alamat' => ['prohibited'],
            'is_penanggung_jawab_rumah' => ['sometimes', 'boolean', $this->unchangedAssignment('is_penanggung_jawab_rumah')],
            'phone' => ['sometimes', 'required', 'regex:/^[0-9]{10,13}$/'],
            'nik' => [
                'nullable',
                'digits:16',
                Rule::unique('wargas', 'nik')->ignore($wargaId),
            ],
            'no_kk' => [
                'sometimes', 'nullable',
                'digits:16',
                $this->unchangedFamilyAssignment('no_kk'),
            ],
            'status_dalam_kk' => ['sometimes', 'nullable', 'in:kepala_keluarga,anggota', $this->unchangedFamilyAssignment('status_dalam_kk')],
            'kartu_keluarga_id' => ['prohibited'],
            'nama_kepala_keluarga' => ['prohibited'],
            'is_kepala_keluarga' => ['prohibited'],
            'ganti_kepala_keluarga' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'rumah_kode.prohibited' => 'Pembuatan rumah harus melalui pengurus.',
            'rumah_alamat.prohibited' => 'Perubahan alamat rumah harus melalui pengurus.',
            'phone.regex' => 'Nomor HP harus berisi angka saja, minimal 10 digit dan maksimal 13 digit.',
            'nik.digits' => 'NIK harus berisi 16 digit angka.',
            'nik.unique' => 'NIK tersebut sudah terdaftar untuk warga lain.',
            'no_kk.digits' => 'Nomor KK harus berisi 16 digit angka.',
            'kartu_keluarga_id.prohibited' => 'Penetapan KK harus melalui pengurus.',
            'nama_kepala_keluarga.prohibited' => 'Perubahan kepala keluarga harus melalui pengurus.',
            'is_kepala_keluarga.prohibited' => 'Penetapan kepala keluarga harus melalui pengurus.',
            'ganti_kepala_keluarga.prohibited' => 'Penggantian kepala keluarga harus melalui pengurus.',
        ];
    }

    private function unchangedAssignment(string $field): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($field) {
            $current = $this->user()->getRawOriginal($field);
            if ((string) $value !== (string) $current) {
                $fail('Perubahan domisili dan penanggung jawab iuran harus melalui pengurus.');
            }
        };
    }

    private function unchangedFamilyAssignment(string $field): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($field) {
            $warga = $this->user()->warga;
            $current = $field === 'no_kk' ? $warga?->kartuKeluarga?->no_kk : $warga?->status_dalam_kk;
            if ((string) $value !== (string) $current) {
                $fail('Penetapan atau perubahan KK dan kepala keluarga harus melalui pengurus.');
            }
        };
    }
}
