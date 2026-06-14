<?php

namespace App\Http\Requests;

use App\Models\Surat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSuratRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->hasPermission('submit-surat') ?? false)
            && filled($this->user()?->rt_id);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_keys(Surat::TYPES))],
            'subject' => ['required', 'string', 'max:255'],
            'purpose' => ['required', 'string', 'min:5', 'max:2000'],
            'content' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:3'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ];
    }
}
