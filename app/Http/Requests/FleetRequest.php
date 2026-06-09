<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FleetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'depot_id' => ['required', 'integer'],
            'fleet_size' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'depot_id.required' => 'An operating centre depot ID must be provided.',
            'depot_id.integer' => 'The depot ID must be a valid numeric identifier.',
            'fleet_size.required' => 'You must specify the new vehicle fleet size.',
            'fleet_size.integer' => 'The requested vehicle size fleet must be a whole number.',
        ];
    }
}
