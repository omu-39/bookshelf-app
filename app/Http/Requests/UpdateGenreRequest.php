<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGenreRequest extends FormRequest
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
            'name' => ['required', 'string', Rule::unique('genres', 'name')->ignore($this->genre)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'ジャンル名は​必須です。',
            'name.string' => 'ジャンル名は​文字列で​入力してください。​',
            'name.unique' => 'その​ジャンル名は​既に​使用されています。​',
        ];
    }
}
