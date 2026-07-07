<?php

function sendRequestNotification($conn, $department, $subject, $details)
{
    // Get approver based on department
    $stmt = $conn->prepare("
        SELECT name, email
        FROM users
        WHERE department = ?
        AND role = 'Approver'
        LIMIT 1
    ");

    $stmt->bind_param("s", $department);
    $stmt->execute();

    $result = $stmt->get_result();

    if (!$approver = $result->fetch_assoc()) {
        error_log("No approver found for department: $department");
        return false;
    }

    $apiKey = getenv("BREVO_API_KEY");

    $body = "";

    foreach ($details as $label => $value) {
        $body .= "<tr>
                    <td style='padding:8px;font-weight:bold;'>$label</td>
                    <td style='padding:8px;'>$value</td>
                  </tr>";
    }

    $payload = [
        "sender" => [
            "name" => getenv("BREVO_FROM_NAME"),
            "email" => getenv("BREVO_FROM_EMAIL")
        ],
        "to" => [
            [
                "email" => $approver["email"],
                "name" => $approver["name"]
            ]
        ],
        "subject" => $subject,
        "htmlContent" => "
        <html>
        <body style='font-family:Arial'>

            <h2>$subject</h2>

            <p>Hello {$approver['name']},</p>

            <p>A new request has been submitted.</p>

            <table border='1' cellpadding='0' cellspacing='0'
                   style='border-collapse:collapse'>

                $body

            </table>

            <br>

            <p>Please login to the STLAF Leave Management System to review the request.</p>
            
            <br>
            <p> https://stlaf-leave.vercel.app/ </p>

        </body>
        </html>
        "
    ];

    $ch = curl_init("https://api.brevo.com/v3/smtp/email");

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "accept: application/json",
            "api-key: $apiKey",
            "content-type: application/json"
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log(curl_error($ch));
        curl_close($ch);
        return false;
    }

    curl_close($ch);

    return true;
}