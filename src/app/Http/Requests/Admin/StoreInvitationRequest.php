<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvitationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'label'          => ['required', 'string', 'max:100'],
            'target_role'    => ['required', 'string', 'in:investor,affiliator'],
            'kuota_maksimal' => ['required', 'integer', 'min:1', 'max:10000'],
            'expired_hours'  => ['nullable', 'integer', 'min:0', 'max:8760'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'label.required'          => 'Label atau nama acara tautan wajib diisi.',
            'target_role.required'    => 'Target peran pendaftar wajib dipilih.',
            'target_role.in'          => 'Target peran harus berupa Investor atau Affiliator.',
            'kuota_maksimal.required' => 'Batas kuota pendaftar wajib diisi.',
            'kuota_maksimal.min'      => 'Batas kuota minimal 1 pendaftar.',
        ];
    }
}
