<?php

$apiKey = getenv('BREVO_API_KEY');

$data = [
    "sender" => [
        "name" => getenv("BREVO_FROM_NAME"),
        "email" => getenv("BREVO_FROM_EMAIL")
    ],
    "to" => [
        [
            "email" => "stlaf.it02@gmail.com",
            "name" => "Approver"
        ]
    ],
    "subject" => "Brevo API Test",
    "htmlContent" => "
        <h2>Congratulations!</h2>
        <p>Your Brevo Email API is working.</p>
    "
];

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.brevo.com/v3/smtp/email",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "accept: application/json",
        "api-key: $apiKey",
        "content-type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode($data)
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo curl_error($ch);
} else {
    echo $response;
}

curl_close($ch);