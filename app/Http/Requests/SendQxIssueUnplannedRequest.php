<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendQxIssueUnplannedRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        
        return [
            

            'part'      => 'required|string',
            'site'      => 'required|string',
            'location'  => 'required|string',
            'lotserial' => 'required|string',
            'warehouse' => 'required|string',
            'level'     => 'required|string',
            'bin'       => 'required|string',
            'qty'       => 'required|string',
            'oriloc'       => 'required|string',
            

            
        ];
    }
     
}
