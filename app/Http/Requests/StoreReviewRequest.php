<?php

namespace App\Http\Requests;

use App\Models\Review;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreReviewRequest extends FormRequest
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
            'rating' => ['required', 'integer', 'in:1,2,3,4,5'],
            'comment' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => '評価は​必須です。',
            'rating.integer' => '評価は​整数で​入力してください。',
            'rating.in' => '評価は1〜5の整数で入力してください。',
            'comment.required' => 'コメントは​必須です。​',
            'comment.string' => 'コメントは​文字列で​入力してください。​',
            'comment.max' => 'コメントは1000文字以下で入力してください。',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $exists = Review::where('user_id', $this->user()->id)
                ->where('book_id', $this->route('book')->id)
                ->exists();

            if ($exists) {
                $validator->errors()->add('rating', 'この書籍には既にレビューしています。');
            }
        });
    }
}
