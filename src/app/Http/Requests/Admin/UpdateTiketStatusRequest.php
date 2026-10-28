<?php

namespace App\Http\Requests\Admin;

use App\Models\TiketBantuan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTiketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status'  => ['required', 'string', Rule::in(array_keys(TiketBantuan::STATUS_LABELS))],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Status baru wajib ditentukan.',
            'status.in'       => 'Status tiket tidak valid.',
            'catatan.max'     => 'Catatan perubahan status maksimal 1.000 karakter.',
        ];
    }
}
