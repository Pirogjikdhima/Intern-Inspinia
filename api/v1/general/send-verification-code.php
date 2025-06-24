<?php
require_once "../dependencies/dependencies.php";
use PHPMailer\PHPMailer\PHPMailer;
function sendVerificationLink(): void
{
    if (!postRequest() || getRequest()) {
        sendError("Method not allowed.", 405);
    }

    $email = validateInput(trim($_POST['email'] ?? null), null, FILTER_VALIDATE_EMAIL, "Invalid email.");

    $conn = connectDB();
    $user_exists = checkUserExistByEmailAndReturnUser($email, $conn);

    if ($user_exists['status'] === false) {
        sendError($user_exists['message'], 404);
    }

    $user = $user_exists['user'];
    $user_id = $user['id'];

    $previous_token_exists = checkIfPreviousTokenExistAndIsExpired($user_id, $conn);
    if ($previous_token_exists['status'] === true) {
        if ($previous_token_exists['code'] === 429) {
            sendError($previous_token_exists['message'], 429);
        }
    }

    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+2 minutes'));
    $now = date('Y-m-d H:i:s');

    if ($previous_token_exists['status'] === false) {
        $token_inserted = insertTokenFromDB($user_id, $token, $expires, $now, $conn);
        if ($token_inserted['status'] === false) {
            sendError($token_inserted['message'], 500);
        }
    } else {
        $token_inserted = updateTokenFromDB($user_id, $token, $expires, $now, $conn);
        if ($token_inserted['status'] === false) {
            sendError($token_inserted['message'], 500);
        }
    }
    disconnectDB($conn);
    $combined_token = $token . $user_id;
    $resetLink = "http://localhost/Intern/set-new-password.php?token=" . urlencode($combined_token);
    $mail = new PHPMailer(true);
    $sent_email = sendEmail($email, $resetLink, $mail);
    if ($sent_email['status'] === false) {
        sendError($sent_email['message'], 500);
    }
    sendSuccess($sent_email['message'], 200);
}
sendVerificationLink();
