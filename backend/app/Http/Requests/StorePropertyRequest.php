<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePropertyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['seller', 'agent', 'admin']) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'property_type_id' => ['required', 'integer', 'exists:property_types_master,id'],
            'listing_type' => ['required', 'string', 'in:sale,rent'],
            'price' => ['required', 'numeric', 'min:0'],
            'price_negotiable' => ['sometimes', 'boolean'],
            'area_sqft' => ['nullable', 'numeric', 'min:0'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:50'],
            'bathrooms' => ['nullable', 'integer', 'min:0', 'max:50'],
            'floor_no' => ['nullable', 'integer', 'min:0'],
            'total_floors' => ['nullable', 'integer', 'min:0'],
            'furnishing_status' => ['nullable', 'string', 'max:50'],
            'city_id' => ['required', 'integer', 'exists:cities_master,id'],
            'locality_id' => ['required', 'integer', 'exists:localities_master,id'],
            'address' => ['required', 'string', 'max:500'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'rera_registration_no' => ['nullable', 'string', 'max:100'],
            'is_draft' => ['sometimes', 'boolean'],
            'amenity_ids' => ['sometimes', 'array'],
            'amenity_ids.*' => ['integer', 'exists:amenities_master,id'],
        ];
    }
}
