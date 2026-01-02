<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendQxCompIssueRequest extends FormRequest
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
            'wonbr'      => 'required|string',
            'location'   => 'required|string',
            'lot'        => 'required|string',
            'effdate'    => 'required|string',
            'part'       => 'required|string',
            'qty'        => 'required|string',
            'site'       => 'required|string',
            'lotserial'  => 'required|string',
        ];
    }
     /**
     * Normalize request data
     */
    // protected function prepareForValidation()
    // {
    //     $this->merge([
    //         'part' => $this->part !== null && $this->part !== ''
    //             ? explode(';', $this->part)
    //             : [],

    //         'qty' => $this->qty !== null && $this->qty !== ''
    //             ? explode(';', $this->qty)
    //             : [],

    //         'site' => $this->site !== null && $this->site !== ''
    //             ? explode(';', $this->site)
    //             : [],

    //         'lotserial' => $this->lotserial !== null && $this->lotserial !== ''
    //             ? explode(';', $this->lotserial)
    //             : [],
    //     ]);
    // }
}
