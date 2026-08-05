<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

// デフォルトのログイン機能には上述のFortifyLoginRequestクラスが引数として渡されているため、異なるクラスを呼び出すとエラーが出力される。
// そのため、デフォルトにある上述のFortifyLoginRequestクラスを継承する必要がある

class LoginRequest extends FortifyLoginRequest
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
    public function rules()
    {
        return [
            'email' => ['required'],
            'password' => ['required'],
        ];
    }

    public function messages()
    {
        return [
            'email.required' => 'メールアドレスを入力してください',
            'password.required' => 'パスワードを入力してください',
        ];
    }
}
