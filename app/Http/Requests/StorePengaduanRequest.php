<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePengaduanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'string', 'in:Keamanan,Kebersihan,Infrastruktur,Sosial,Lainnya'],
            'deskripsi' => ['required', 'string'],
            'foto' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'judul.required' => 'Judul pengaduan wajib diisi.',
            'kategori.required' => 'Kategori wajib dipilih.',
            'deskripsi.required' => 'Deskripsi aduan wajib ditulis.',
            'foto.image' => 'Berkas bukti harus berupa gambar.',
            'foto.mimes' => 'Format gambar harus jpeg, png, atau jpg.',
            'foto.max' => 'Ukuran foto maksimal adalah 2MB.',
        ];
    }
}
