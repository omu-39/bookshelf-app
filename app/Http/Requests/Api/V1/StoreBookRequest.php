<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => ['required', 'digits:13', 'unique:books,isbn'],
            'published_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:512'],
            'image_url' => ['nullable', 'url'],
            'genres' => ['required', 'array'],
            'genres.*' => ['exists:genres,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'ユーザーIDは必須です。',
            'user_id.integer' => 'ユーザーIDの形式が正しくありません。',
            'user_id.exists' => '指定された​ユーザーは​見つかりません。',
            'title.required' => 'タイトルは必須です。',
            'title.string' => 'タイトルは​文字列で​入力してください。',
            'title.max' => 'タイトルは255文字以下で入力してください。',
            'author.required' => '著者名は​必須です。',
            'author.string' => '著者名は​文字列で​入力してください。​',
            'author.max' => '著者名は255文字以下で入力してください。',
            'isbn.required' => 'ISBNは必須です。',
            'isbn.digits' => ' ISBNは13桁で入力してください。',
            'isbn.unique' => 'そのISBNは既に使用されています。',
            'published_date.required' => '出版日は​必須です。',
            'published_date.date' => '出版日は​有効な​日付形式で​入力してください。',
            'description.string' => '説明は​文字列で​入力してください。​',
            'description.max' => '説明は512文字以下で入力してください。',
            'image_url.url' => '画像URLは有効なURL形式で入力してください。',
            'genres.required' => 'ジャンルは1つ以上選択してください。',
            'genres.array' => 'ジャンルは​配列で​入力してください。​',
            'genres.*.exists' => '選択された​ジャンルは​存在しません。',
        ];
    }
}
