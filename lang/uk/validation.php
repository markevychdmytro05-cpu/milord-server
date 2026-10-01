<?php

/*
 * Українські повідомлення валідації. Ключі, яких тут немає, беруться з fallback-мови (en) фреймворку.
 */
return [
    'required' => 'Поле «:attribute» є обов’язковим.',
    'string' => 'Поле «:attribute» має бути текстом.',
    'email' => 'Поле «:attribute» має містити коректну email-адресу.',
    'url' => 'Поле «:attribute» має містити коректне посилання.',
    'integer' => 'Поле «:attribute» має бути цілим числом.',
    'numeric' => 'Поле «:attribute» має бути числом.',
    'boolean' => 'Поле «:attribute» має бути так або ні.',
    'array' => 'Поле «:attribute» має бути списком.',
    'date' => 'Поле «:attribute» має бути коректною датою.',
    'regex' => 'Поле «:attribute» має некоректний формат.',
    'unique' => 'Таке значення поля «:attribute» уже існує.',
    'exists' => 'Обране значення поля «:attribute» некоректне.',
    'in' => 'Обране значення поля «:attribute» некоректне.',
    'between' => [
        'numeric' => 'Поле «:attribute» має бути від :min до :max.',
        'string' => 'Довжина поля «:attribute» має бути від :min до :max символів.',
    ],
    'min' => [
        'numeric' => 'Поле «:attribute» має бути не менше :min.',
        'string' => 'Поле «:attribute» має містити щонайменше :min символів.',
        'array' => 'Поле «:attribute» має містити щонайменше :min елементів.',
    ],
    'max' => [
        'numeric' => 'Поле «:attribute» має бути не більше :max.',
        'string' => 'Поле «:attribute» має містити не більше :max символів.',
        'array' => 'Поле «:attribute» має містити не більше :max елементів.',
    ],

    'custom' => [
        'name' => ['required' => 'Вкажіть імʼя.'],
        'contact' => [
            'required' => 'Вкажіть, як з вами зв’язатися.',
            'min' => 'Контакт занадто короткий.',
        ],
    ],

    'attributes' => [
        'name' => 'імʼя',
        'contact' => 'контакт',
        'message' => 'повідомлення',
    ],
];
