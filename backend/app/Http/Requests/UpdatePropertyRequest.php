<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePropertyRequest extends FormRequest
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
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'property_type_id' => ['sometimes', 'integer', 'exists:property_types_master,id'],
            'listing_type' => ['sometimes', 'string', 'in:sale,rent'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'price_negotiable' => ['sometimes', 'boolean'],
            'area_sqft' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'bedrooms' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:50'],
            'bathrooms' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:50'],
            'floor_no' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'total_floors' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'furnishing_status' => ['sometimes', 'nullable', 'string', 'max:50'],
            'city_id' => ['sometimes', 'integer', 'exists:cities_master,id'],
            'locality_id' => ['sometimes', 'nullable', 'integer', 'exists:localities_master,id'],
            'locality_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address' => ['sometimes', 'string', 'max:500'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
            'rera_registration_no' => ['sometimes', 'nullable', 'string', 'max:100'],
            'amenity_ids' => ['sometimes', 'array'],
            'amenity_ids.*' => ['integer', 'exists:amenities_master,id'],
        ];
    }
}
