<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to describe various validation errors.
    | These language lines may be easily changed to provide custom messages
    | for your application.
    |
    */

    'accepted'             => ':attributeを承認してください。',
    'active_url'           => ':attributeは、有効なURLではありません。',
    'after'                => ':attributeには、:dateより後の日付を指定してください。',
    'after_or_equal'       => ':attributeには、:date以降の日付を指定してください。',
    'alpha'                => ':attributeには、アルファベットのみを使用してください。',
    'alpha_dash'           => ':attributeには、英数字、ハイフン、アンダースコアのみを使用してください。',
    'alpha_num'            => ':attributeには、英数字のみを使用してください。',
    'array'                => ':attributeには、配列を指定してください。',
    'before'               => ':attributeには、:dateより前の日付を指定してください。',
    'before_or_equal'      => ':attributeには、:date以前の日付を指定してください。',
    'between'              => [
        'numeric' => ':attributeには、:minから:maxまでの数字を指定してください。',
        'file'    => ':attributeには、:minから:max KBまでのファイルを指定してください。',
        'string'  => ':attributeは、:minから:max文字で入力してください。',
        'array'   => ':attributeの項目は、:minから:max個までです。',
    ],
    'boolean'              => ':attributeには、trueかfalseを指定してください。',
    'confirmed'            => ':attributeと:attribute確認が一致しません。',
    'date'                 => ':attributeは、有効な日付ではありません。',
    'date_equals'          => ':attributeは、:dateと等しい日付でなければなりません。',
    'date_format'          => ':attributeは、":format"書式と一致していません。',
    'different'            => ':attributeには、:otherとは異なる値を指定してください。',
    'digits'               => ':attributeは、:digits桁で入力してください。',
    'digits_between'       => ':attributeは、:minから:max桁で入力してください。',
    'dimensions'           => ':attributeの画像サイズが有効ではありません。',
    'distinct'             => ':attributeには、重複しない値を指定してください。',
    'email'                => ':attributeには、有効なメールアドレス形式を指定してください。',
    'ends_with'            => ':attributeは、次のいずれかで終わる必要があります: :values。',
    'exists'               => '選択された:attributeは、有効ではありません。',
    'file'                 => ':attributeには、ファイルを指定してください。',
    'filled'               => ':attributeは、必須です。',
    'gt'                   => [
        'numeric' => ':attributeには、:valueより大きな値を指定してください。',
        'file'    => ':attributeには、:value KBより大きなファイルを指定してください。',
        'string'  => ':attributeは、:value文字より長く入力してください。',
        'array'   => ':attributeの項目は、:value個より多く指定してください。',
    ],
    'gte'                  => [
        'numeric' => ':attributeには、:value以上の値を指定してください。',
        'file'    => ':attributeには、:value KB以上のファイルを指定してください。',
        'string'  => ':attributeは、:value文字以上で入力してください。',
        'array'   => ':attributeの項目は、:value個以上指定してください。',
    ],
    'image'                => ':attributeには、画像を指定してください。',
    'in'                   => '選択された:attributeは、有効ではありません。',
    'in_array'             => ':attributeには、:otherの値を指定してください。',
    'integer'              => ':attributeには、整数を指定してください。',
    'ip'                   => ':attributeには、有効なIPアドレスを指定してください。',
    'ipv4'                 => ':attributeには、有効なIPv4アドレスを指定してください。',
    'ipv6'                 => ':attributeには、有効なIPv6アドレスを指定してください。',
    'json'                 => ':attributeには、有効なJSON文字列を指定してください。',
    'lt'                   => [
        'numeric' => ':attributeには、:valueより小さな値を指定してください。',
        'file'    => ':attributeには、:value KBより小さなファイルを指定してください。',
        'string'  => ':attributeは、:value文字より短く入力してください。',
        'array'   => ':attributeの項目は、:value個より少なく指定してください。',
    ],
    'lte'                  => [
        'numeric' => ':attributeには、:value以下の値を指定してください。',
        'file'    => ':attributeには、:value KB以下のファイルを指定してください。',
        'string'  => ':attributeは、:value文字以下で入力してください。',
        'array'   => ':attributeの項目は、:value個以下指定してください。',
    ],
    'max'                  => [
        'numeric' => ':attributeには、:max以下の数字を指定してください。',
        'file'    => ':attributeには、:max KB以下のファイルを指定してください。',
        'string'  => ':attributeは、:max文字以下で入力してください。',
        'array'   => ':attributeの項目は、:max個以下で入力してください。',
    ],
    'mimes'                => ':attributeには、:valuesタイプのファイルを指定してください。',
    'mimetypes'            => ':attributeには、:valuesタイプのファイルを指定してください。',
    'min'                  => [
        'numeric' => ':attributeには、:min以上の数字を指定してください。',
        'file'    => ':attributeには、:min KB以上のファイルを指定してください。',
        'string'  => ':attributeは、:min文字以上で入力してください。',
        'array'   => ':attributeの項目は、:min個以上で入力してください。',
    ],
    'not_in'               => '選択された:attributeは、有効ではありません。',
    'not_regex'            => ':attributeの形式は、有効ではありません。',
    'numeric'              => ':attributeには、数字を指定してください。',
    'present'              => ':attributeは、存在しなければなりません。',
    'regex'                => ':attributeの形式は、有効ではありません。',
    'required'             => ':attributeは、必須項目です。',
    'required_if'          => ':otherが:valueの場合、:attributeは必須項目です。',
    'required_unless'      => ':otherが:valuesでない場合、:attributeは必須項目です。',
    'required_with'        => ':valuesが指定されている場合、:attributeは必須項目です。',
    'required_with_all'    => ':valuesが全て指定されている場合、:attributeは必須項目です。',
    'required_without'     => ':valuesが指定されていない場合、:attributeは必須項目です。',
    'required_without_all' => ':valuesが全て指定されていない場合、:attributeは必須項目です。',
    'same'                 => ':attributeと:otherは、一致している必要があります。',
    'size'                 => [
        'numeric' => ':attributeには、:sizeを指定してください。',
        'file'    => ':attributeには、:size KBのファイルを指定してください。',
        'string'  => ':attributeは、:size文字で入力してください。',
        'array'   => ':attributeの項目は、:size個指定してください。',
    ],
    'starts_with'          => ':attributeは、次のいずれかで始まる必要があります: :values。',
    'string'               => ':attributeには、文字を指定してください。',
    'timezone'             => ':attributeには、有効なゾーンを指定してください。',
    'unique'               => '指定の:attributeは、すでに存在しています。',
    'uploaded'             => ':attributeのアップロードに失敗しました。',
    'url'                  => ':attributeは、有効なURLではありません。',
    'uuid'                 => ':attributeは、有効なUUIDでなければなりません。',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'email' => [
            'required' => 'メールアドレスを入力してください',
            'exists' => 'ログイン情報が登録されていません',
        ],
        'password' => [
            'required' => 'パスワードを入力してください',
            'min' => 'パスワードは8文字以上で入力してください',
            'confirmed' => 'パスワードと一致しません'
        ],
        'name' => [
            'required' => 'お名前を入力してください',
        ],
        'start_work' => [
            'required' => '出勤打刻がされていません',
            'in' => '本日分の出勤打刻はすでに完了しています',
        ],
        'end_work' => [
            'required' => '退勤打刻がされていません',
            'in' => '本日の勤務を終了しています',
        ],
        'start_break' => [
            'required' => '休憩開始打刻がされていません',
        ],
        'end_break' => [
            'required' => '休憩終了打刻がされていません',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [
        'name' => 'お名前',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'start_work' => '出勤',
        'end_work' => '退勤',
    ],
];
