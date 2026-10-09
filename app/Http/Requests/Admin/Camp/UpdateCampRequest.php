<?php

namespace App\Http\Requests\Admin\Camp;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCampRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
     protected function prepareForValidation(): void
     {
         $this->merge([
             'publish_ranking_for_evaluators'    => filter_var($this->publish_ranking_for_evaluators ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
             'hide_evaluator_name_from_referees'  => filter_var($this->hide_evaluator_name_from_referees ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
             'hide_ranking_numbers_from_referees' => filter_var($this->hide_ranking_numbers_from_referees ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
             'publish_ranking_for_referees'      => filter_var($this->publish_ranking_for_referees ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
         ]);
     }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'director_id'                        => ['required', 'exists:users,id'],
            'sports_type_id'                     => ['required', 'exists:sports_types,id'],
            'camp_name'                          => ['required', 'string', 'max:255'],
            'location'                           => ['required', 'string', 'max:255'],
            'address'                            => ['nullable', 'string', 'max:255'],
            'latitude'                           => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'                          => ['nullable', 'numeric', 'between:-180,180'],
            'timezone'                           => ['nullable', 'string', 'max:100'],
            'start_date'                         => ['required', 'date'],
            'end_date'                           => ['required', 'date', 'after_or_equal:start_date'],
            'camp_details'                       => ['nullable', 'string'],
            'price'                              => ['required', 'numeric', 'min:0'],
            'camp_logo'                          => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:5120'],
            'status'                             => ['nullable', 'in:active,inactive'],
            'publish_ranking_for_evaluators'     => ['nullable', 'boolean'],
            'hide_evaluator_name_from_referees'   => ['nullable', 'boolean'],
            'hide_ranking_numbers_from_referees'  => ['nullable', 'boolean'],
            'publish_ranking_for_referees'       => ['nullable', 'boolean'],
        ];
    }
}
