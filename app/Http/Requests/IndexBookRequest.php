<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:255'],
            'genre' => ['nullable', 'exists:genres,id'],
            'sort' => ['nullable', 'in:newest, oldest, rating, title']
        ];
    }

    public function messages(): array
    {
        return [
            'keyword.string' => '検索キーワードは​文字列で​入力してください。',
            'keyword.max' => '検索キーワードは255文字以内で入力してください。',
            'genre.exists' => '選択されたジャンルは存在しません。',
            'sort.in' => 'ソート条件は指定できる値の中から選んでください。'
        ];
    }
}
