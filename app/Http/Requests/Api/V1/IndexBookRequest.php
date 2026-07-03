<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexBookRequest extends FormRequest
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
            'keyword' => ['nullable', 'string', 'max:255'],
            'genres' => ['nullable', 'array'],
            'genres.*' => ['string', 'exists:genres,name'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }

    public function messages(): array
    {
        return [
            'keyword.string' => '検索キーワードは​文字列で​入力してください。',
            'keyword.max' => '検索キーワードは255文字以内で入力してください。',
            'genres.array' => 'ジャンルは配列で入力してください',
            'genres.*.exists' => '選択されたジャンルは存在しません。',
            'page.integer' => 'ページ​番号が​正しく​ありません。',
            'page.min' => 'ページ番号は1以上の数値を指定してください。',
            'per_page.integer' => '表示件数の​指定が​正しく​ありません。',
            'per_page.between' => '表示件数は1〜100件の間で指定してください。',
        ];
    }
}
