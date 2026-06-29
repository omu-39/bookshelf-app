<?php

// lang/ja/validation.php

return [
    'required' => ':attributeは必須です。',
    'email' => ':attributeはメール形式で入力してください。',
    'confirmed' => 'パスワードと一致しません。',
    'min' => [
        'string' => ':attributeは:min文字以上で入力してください。',
        'numeric' => ':attributeは:min以上で指定してください。',
    ],

    'attributes' => [
        'name' => '名前',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'password_confirmation' => '確認用パスワード',
    ],
];
