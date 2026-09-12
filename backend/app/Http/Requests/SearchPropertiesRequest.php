<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SearchPropertiesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'string', 'max:255'],
            'city' => ['sometimes', 'integer', 'exists:cities_master,id'],
            'locality' => ['sometimes', 'integer', 'exists:localities_master,id'],
            'type' => ['sometimes', 'integer', 'exists:property_types_master,id'],
            'listing_type' => ['sometimes', 'string', 'in:sale,rent'],
            'min_price' => ['sometimes', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'numeric', 'min:0'],
            'bedrooms' => ['sometimes', 'integer', 'min:0'],
            'lat' => ['sometimes', 'numeric', 'between:-90,90', 'required_with:lng,radius_km'],
            'lng' => ['sometimes', 'numeric', 'between:-180,180', 'required_with:lat,radius_km'],
            'radius_km' => ['sometimes', 'numeric', 'min:0.1', 'max:200'],
            'sort' => ['sometimes', 'string', 'in:recent,price_asc,price_desc,featured'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
