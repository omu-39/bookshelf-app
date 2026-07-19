<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReadingPlanRequest extends FormRequest
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
            'book_id' => ['required', 'exists:books,id', 'unique:reading_plans,book_id'],
            'target_date' => ['required', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'book_id.required' => '書籍は必須です。',
            'book_id.exists' => '登録済みの書籍から選択してください。',
            'book_id.unique' => 'その書籍の読書計画は登録済みです。',
            'target_date.required' => '期日は必須です。',
            'target_date.date' => '期日は​有効な​日付形式で​入力してください。',
            'target_date.after' => '期日は翌日以降を入力してください。'
        ];
    }
}
