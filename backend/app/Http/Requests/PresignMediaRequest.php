<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PresignMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('property')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isImage = $this->route('type') === 'image';

        return [
            'extension' => ['required', 'string', Rule::in($isImage ? ['jpg', 'jpeg', 'png'] : ['mp4', 'mov'])],
            'content_type' => ['required', 'string', Rule::in($isImage ? ['image/jpeg', 'image/png'] : ['video/mp4', 'video/quicktime'])],
        ];
    }
}
