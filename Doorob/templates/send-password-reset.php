<?php
include 'config.php'; 

// Get email from the POST request
$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'];

// Check if the email exists in the database
$sql_check = "SELECT Email FROM users WHERE Email = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("s", $email);
$stmt_check->execute();
$stmt_check->store_result();

$response = [];

if ($stmt_check->num_rows > 0) {
    // Email exists, proceed with generating token and sending the email
    $token = bin2hex(random_bytes(16));
    $token_hash = hash("sha256", $token);
    $expiry = date("Y-m-d H:i:s", time() + 60 * 10);

    $sql_update = "UPDATE users
                   SET reset_token_hash = ?,
                       reset_token_expires_at = ?
                   WHERE Email = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("sss", $token_hash, $expiry, $email);
    $stmt_update->execute();

    if ($stmt_update->affected_rows > 0) {
        $mail = require __DIR__ . "/mailer.php";

        $mail->setFrom('noreply@example.com', 'Doroob'); // This will be the "From" address
        $mail->addAddress($email);
        $mail->Subject = "Password Reset Request for Your Doroob Account";
        $mail->Body = <<<END
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Password Reset</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    margin: 0;
                    padding: 0;
                }
                .email-container {
                    background-color: #ffffff;
                    border-radius: 8px;
                    padding: 20px;
                    max-width: 600px;
                    margin: 20px auto;
                    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                }
                .email-header {
                    text-align: left;
                    color: #333;
                }
                .email-content {
                    font-size: 16px;
                    color: #333;
                    line-height: 1.5;
                }
                .email-content p {
                    margin-bottom: 20px;
                }
                .reset-link {
                    border: 1px solid #506984;
                    background-color: #4e7aa6;
                    color: white !important;
                    padding: 10px 20px;
                    text-decoration: none !important;
                    border-radius: 4px;
                    font-weight: bold;
                    display: inline-block;
                }
                .reset-link:hover {
                background-color: #105f9c;
                }
                .reset-link:active {
                background-color: #388e3c; /* Color when link is clicked */
                }
                .reset-link:visited {
                color: white !important; /* Keep color white when visited */
                }
                .email-footer {
                    text-align: center;
                    font-size: 12px;
                    color: #888;
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="email-header">
                    <h2>Password Reset Request</h2>
                </div>
                <div class="email-content">
                    <p>Hi there,</p>
                    <p>We received a request to reset your password for your Doroob account. If you didn't make this request, please ignore this email. If you did, you can reset your password by clicking the link below:</p>
                    <p><a href="http://localhost:3000/Doorob/templates/reset-password.php?token=$token" class="reset-link">Reset Your Password</a></p>
                    <p>This link will expire in 10 minutes for security purposes. If you don't reset your password in time, you'll need to request a new link.</p>
                </div>
                <div class="email-footer">
                    <p>If you have any questions, feel free to contact us at support@doroob.com</p>
                    <p>Thank you for using Doroob!</p>
                </div>
            </div>
        </body>
        </html>
        END;
        

        try {
            $mail->send();
            $response['success'] = true;
        } catch (Exception $e) {
            $response['success'] = false;
            $response['error'] = "Mailer error: {$mail->ErrorInfo}";
        }
    } else {
        $response['success'] = false;
        $response['error'] = 'Failed to update reset token.';
    }
} else {
    // Email does not exist in the database
    $response['success'] = false;
    $response['error'] = 'The entered email does not exist.';
}

// Send response as JSON
echo json_encode($response);
?>
