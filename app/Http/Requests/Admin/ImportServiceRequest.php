<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;

class ImportServiceRequest extends AdminRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,xlsx,txt', 'extensions:csv,xlsx', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.mimes' => 'Upload a CSV or Excel (.xlsx) file.',
            'file.extensions' => 'Upload a CSV or Excel (.xlsx) file.',
            'file.max' => 'The file must be 5 MB or smaller.',
        ];
    }
}
