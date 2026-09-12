<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AttachMediaRequest extends FormRequest
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
        $property = $this->route('property');
        $type = $this->route('type');
        $expectedPrefix = "properties/{$property->id}/{$type}s/";

        return [
            'path' => ['required', 'string', function (string $attribute, $value, $fail) use ($expectedPrefix) {
                if (! str_starts_with($value, $expectedPrefix)) {
                    $fail('The path does not belong to this property/type.');
                }
            }],
            'is_primary' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
