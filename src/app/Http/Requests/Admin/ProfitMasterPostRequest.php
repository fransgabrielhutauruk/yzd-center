<?php

namespace App\Http\Requests\Admin;

use App\Models\ProfitMasterPost;
use App\Models\ProfitMasterPostImage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ProfitMasterPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch') || $this->input('_method') === 'PUT';

        return [
            'caption'             => ['required', 'string', 'max:20000'],
            'images'              => [$isUpdate ? 'nullable' : 'required', 'array', $isUpdate ? 'min:0' : 'min:1'],
            'images.*'            => ['file', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'deleted_image_ids'   => ['nullable', 'array'],
            'deleted_image_ids.*' => ['integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'caption.required' => 'Caption atau teks materi promosi wajib diisi.',
            'caption.max'      => 'Caption maksimal 20.000 karakter.',
            'images.required'  => 'Minimal 1 foto bukti profit wajib diunggah.',
            'images.min'       => 'Minimal 1 foto bukti profit wajib diunggah.',
            'images.*.file'    => 'Berkas yang diunggah harus berupa file yang valid.',
            'images.*.mimes'   => 'Format foto harus berupa JPG, JPEG, PNG, atau WEBP.',
            'images.*.max'     => 'Ukuran masing-masing foto maksimal 10 MB.',
        ];
    }

    /**
     * Validasi tambahan untuk memastikan post memiliki minimal 1 gambar setelah update.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $isUpdate = $this->isMethod('put') || $this->isMethod('patch') || $this->input('_method') === 'PUT';

            if ($isUpdate) {
                $post = $this->route('post');
                if (!$post instanceof ProfitMasterPost) {
                    $postId = $this->route('post') ?? $this->route('id');
                    $post = ProfitMasterPost::find($postId);
                }

                if ($post) {
                    $existingCount = ProfitMasterPostImage::where('post_id', $post->id)->count();
                    $deletedIds = $this->input('deleted_image_ids', []);
                    $deletedCount = is_array($deletedIds) ? count($deletedIds) : 0;
                    $newCount = is_array($this->file('images')) ? count($this->file('images')) : 0;

                    $remainingCount = ($existingCount - $deletedCount) + $newCount;

                    if ($remainingCount < 1) {
                        $v->errors()->add('images', 'Postingan wajib memiliki minimal 1 foto bukti profit.');
                    }
                }
            }
        });
    }
}
