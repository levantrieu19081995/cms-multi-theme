<?php

namespace App\Http\Requests\LinkWebsite;

use App\DataProviders\OptionDataProvider;
use App\Models\Advertisement;
use Illuminate\Foundation\Http\FormRequest;
use Nwidart\Modules\Facades\Module;

class CreateLinkRequest extends FormRequest
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
            'title' => 'required|max:255',
            // 'redirect_url' => 'url',
         
        ];
    }
 
    public function attributes(): array
    {
        return [
          'title' => __('Tên website liên kết'),
        ];
    }
}