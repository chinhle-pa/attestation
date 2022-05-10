<?php

/*
 * You can place your custom package configuration in here.
 */
return [
    'CHALLENGE_EXPIRE_MINUTES' => 2,
    'CHALLENGE_LENGTH' => 20,
    'iOS' => [
        'PACKGE_NAME' => 'com.pacificcross.claim',
        'DEVELOPER_ID' => 'QG3KSBH6C8',
        'PUBLIC_KEY_URL' => __DIR__.'/../cert/Apple_App_Attestation_Root_CA.pem'
    ],
    'Android' => [
        'PACKGE_NAME' => 'com.pacificcross.app',
        'ALLOWED_WINDOW_MILLIS' => 12*30*24*2*60*60*1000,
        'AUTH_CONFIG_URL' => __DIR__.'/../cert/e-sure-711f6562a7de.json'
    ]
];