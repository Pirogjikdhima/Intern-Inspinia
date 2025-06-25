<?php

function encryptMessage(string $message, string $cipher = 'aes-256-cbc', ?string &$iv = null): string|false
{

    $key = $_ENV["ENCRYPTION_KEY"];

    $iv_length = openssl_cipher_iv_length($cipher);
    if ($iv === null) {
        $iv = openssl_random_pseudo_bytes($iv_length);
    } elseif (strlen($iv) !== $iv_length) {
        error_log("Error: Initialization vector length mismatch for cipher: $cipher");
        return false;
    }

    $encrypted = openssl_encrypt($message, $cipher, $key, OPENSSL_RAW_DATA, $iv);

    if ($encrypted === false) {
        error_log("OpenSSL encryption failed: " . openssl_error_string());
        return false;
    }

    return base64_encode($iv . $encrypted);
}

function decryptMessage(string $encrypted_message, string $cipher = 'aes-256-cbc'): string|false
{
    $key = $_ENV["ENCRYPTION_KEY"];

    $decoded = base64_decode($encrypted_message);
    if ($decoded === false) {
        error_log("Error: Base64 decoding failed.");
        return false;
    }

    $iv_length = openssl_cipher_iv_length($cipher);
    if (strlen($decoded) < $iv_length) {
        error_log("Error: Encrypted message is too short to contain the IV.");
        return false;
    }

    $iv = substr($decoded, 0, $iv_length);
    $encrypted = substr($decoded, $iv_length);

    $decrypted = openssl_decrypt($encrypted, $cipher, $key, OPENSSL_RAW_DATA, $iv);

    if ($decrypted === false) {
        error_log("OpenSSL decryption failed: " . openssl_error_string());
        return false;
    }

    return $decrypted;
}

function regexTest($pattern, $string): bool
{
    return preg_match($pattern, $string);
}

function validateInput($input, $regex = null, $filter = null, $message = null, $code = 422): string
{
    if (!isset($input)) {
        sendError("Field cannot be empty.", $code);
    }
    if ($regex) {
        if (!regexTest($regex, $input)) {
            sendError($message, $code);
        }
    }
    if ($filter) {
        if (!filter_var($input, $filter)) {
            sendError($message, $code);
        }
    }
    return $input;
}


function validateBirthday($birthday): string
{
    $dob = DateTime::createFromFormat('d/m/Y', $birthday);

    if (!$dob) {
        $dob = DateTime::createFromFormat('m/d/Y', $birthday);
    }

    if (!$dob) {
        $dob = DateTime::createFromFormat('Y-m-d', $birthday);
    }

    $today = new DateTime();

    if (!$dob || $dob > $today || $today->diff($dob)->y < 18) {
        sendError("Invalid birthday. You must be at least 18 years old.", 422);
    }

    return $dob->format('Y-m-d');
}


function checkUserExistByEmailAndReturnUser($email, $conn): array
{
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $response = [
            'status' => false,
            'message' => "User does not exist."
        ];
    } else {
        $user = $result->fetch_assoc();
        $response = [
            'status' => true,
            'user' => $user
        ];
    }
    $stmt->close();
    return $response;
}

function getRoleName($user_id, $conn): string
{
    $stmt = $conn->prepare("
                SELECT r.name 
                FROM roles r 
                INNER JOIN user_roles ur ON ur.role_id = r.id
                INNER JOIN users u ON u.id = ur.user_id
                WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row["name"];
}


function registerUser($username, $firstName, $lastName, $phone, $email, $birthday, $address, $password, $termsAccepted, $conn): array
{
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO users (username, name, last_name, phone, email, birthday, address, password, termsChecked,created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?,NOW())");
    $stmt->bind_param("ssssssssi", $username, $firstName, $lastName, $phone, $email, $birthday, $address, $hashedPassword, $termsAccepted);
    $stmt->execute();
    if ($stmt->affected_rows === 0) {
        $response = ['status' => false, 'message' => "User registration failed."];
    } else {
        $user_id = $conn->insert_id;
        $response = ['status' => true, 'user_id' => $user_id];

    }

    $stmt->close();
    return $response;
}

function insertRole($user_id, $role_id, $conn): bool
{
    $stmt = $conn->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $role_id);
    $stmt->execute();
    if ($stmt->affected_rows === 0) {
        $stmt->close();
        return false;
    }
    return true;
}


function checkPermission($value, $allowedValue): array
{
    if ($value != $allowedValue) {
        $response = [
            'status' => false,
            'message' => "You do not have permission to perform this action."
        ];
    } else {
        $response = [
            'status' => true,
            'message' => "You have permission to perform this action."
        ];
    }
    return $response;
}

function getAllUsersFromDB($conn): array
{
    $stmt = $conn->prepare("SELECT id,username,name,last_name,birthday,email,address FROM users");
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $row['id'] = encryptMessage($row['id']);
            $users[] = $row;
        }
        $response = [
            'status' => true,
            'message' => "Users found.",
            'users' => $users
        ];
    } else {
        $response = [
            'status' => false,
            'message' => "Users not found."
        ];
    }
    $conn->close();
    return $response;
}


function getAgeReportFromDB($conn, $manager_id): array
{
    $stmt = $conn->prepare("
        SELECT 
            CASE
                WHEN TIMESTAMPDIFF(YEAR, birthday, CURDATE()) BETWEEN 18 AND 25 THEN '18-25'
                WHEN TIMESTAMPDIFF(YEAR, birthday, CURDATE()) BETWEEN 26 AND 35 THEN '26-35'
                WHEN TIMESTAMPDIFF(YEAR, birthday, CURDATE()) BETWEEN 36 AND 45 THEN '36-45'
                ELSE '46+'
            END AS age_group,
            COUNT(*) AS user_count
        FROM users
        WHERE manager_id = ?
        GROUP BY age_group
    ");
    $stmt->bind_param("i", $manager_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'age_group' => htmlspecialchars($row['age_group']),
                'user_count' => (int)$row['user_count']
            ];
        }
    } else {
        $data[] = [
            'age_group' => "No users for the manager",
            'user_count' => 0,
        ];
    }
    $stmt->close();
    return [
        'status' => true,
        'message' => "Age report found.",
        'data' => $data
    ];
}


function deleteUserFromDB($conn, $user_id): array
{
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $response = [
            'status' => true,
            'message' => "User deleted successfully."
        ];
    } else {
        $response = [
            'status' => false,
            'message' => "Error deleting user."
        ];
    }
    $stmt->close();
    return $response;
}


function checkUserExistByEmailOrUsername($email, $username, $conn): array
{
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $response = [
            'status' => true,
            'message' => "Username or email already exists."
        ];
    } else {
        $response = [
            'status' => false,
            'message' => "Username or email does not exist."
        ];
    }
    $stmt->close();
    return $response;
}

function addUserFromDB($username, $firstName, $lastName, $phone, $email, $birthday, $address, $password, $termsAccepted, $manager_id, $conn): array
{
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO users (username, name, last_name, phone, email, birthday, address, password, termsChecked,created_at,manager_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?,NOW(),?)");
    $stmt->bind_param("ssssssssis", $username, $firstName, $lastName, $phone, $email, $birthday, $address, $hashedPassword, $termsAccepted, $manager_id);
    $stmt->execute();
    if ($stmt->affected_rows === 0) {
        $response = ['status' => false, 'message' => "User registration failed."];
    } else {
        $user_id = $conn->insert_id;
        $response = ['status' => true, 'user_id' => $user_id];
    }
    $stmt->close();
    return $response;

}

function checkIfPreviousTokenExistAndIsExpired($user_id, $conn): array
{
    $stmt = $conn->prepare("SELECT created_at FROM password_resets WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastTokenTime = strtotime($row['created_at']);
        $currentTime = time();

        if (($currentTime - $lastTokenTime) < 120) {
            $timeToWait = 120 - ($currentTime - $lastTokenTime);
            $response = [
                'status' => true,
                'message' => "Please wait " . $timeToWait . " seconds before requesting another token.",
                'code' => 429
            ];
        } else {
            $response = [
                'status' => true,
                'message' => "Token expired.",
                'code' => 200
            ];
        }
    } else {
        $response = [
            'status' => false,
            'message' => "No previous token found.",
            'code' => 200
        ];
    }

    $stmt->close();
    return $response;
}

function sendEmail($email, $resetLink, $mail): array
{

    try {
        $mail->isSMTP();
        $mail->Host = $_ENV['EMAIL_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['EMAIL_USERNAME'];
        $mail->Password = $_ENV['EMAIL_PASSWORD'];
        $mail->SMTPSecure = $_ENV['EMAIL_SMTPSECURE'];
        $mail->Port = $_ENV['EMAIL_PORT'];

        $mail->setFrom($_ENV['EMAIL_USERNAME'], 'Inspinia Admin');
        $mail->addAddress($email);
        $mail->Subject = 'Password Reset Request';

        $header = '<div style="background: linear-gradient(135deg, #2f4050 0%, #1ab394 100%); color: white; padding: 40px 30px; text-align: center; font-family: \'Open Sans\', Arial, sans-serif; border-radius: 8px 8px 0 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h1 style="margin: 0; font-size: 1.5rem; font-weight: 300; letter-spacing: 1px;">INSPINIA</h1>
        <p style="margin: 10px 0 0 0; font-size: 1rem; opacity: 0.9; font-weight: 300;">Password Reset Request</p>
    </div>';

        $footer = '<div style="background-color: #f8f8f9; color: #676a6c; padding: 25px 30px; text-align: center; font-family: \'Open Sans\', Arial, sans-serif; border-top: 1px solid #e7eaec; border-radius: 0 0 8px 8px;">
        <p style="margin: 0; font-size: 0.9rem; font-weight: 300;">&copy; ' . date('Y') . ' Inspinia Admin Theme. All rights reserved.</p>
        <p style="margin: 8px 0 0 0; font-size: 0.8rem; color: #a7b1c2;">This is an automated message, please do not reply.</p>
    </div>';

        $mail->isHTML(true);
        $mail->Body = "
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <link href='https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600&display=swap' rel='stylesheet'>
</head>
<body style='font-family: \"Open Sans\", Arial, sans-serif; background-color: #f3f3f4; margin: 0; padding: 20px; line-height: 1.6;'>
    <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);'>
        $header
        <div style='padding: 40px 30px; background-color: #ffffff;'>
            <div style='text-align: center; margin-bottom: 30px;'>
                <div style='width: 60px; height: 60px; background: linear-gradient(135deg, #1ab394, #18a689); border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 15px rgba(26, 179, 148, 0.3);'>
                    <span style='color: white; font-size: 24px; font-weight: 600;'>🔐</span>
                </div>
                <h2 style='color: #2f4050; margin: 0 0 10px 0; font-size: 1.4rem; font-weight: 400;'>Reset Your Password</h2>
                <p style='color: #676a6c; margin: 0; font-size: 1rem; font-weight: 300;'>We received a request to reset your password</p>
            </div>
            
            <div style='background-color: #f8f8f9; padding: 25px; border-radius: 6px; border-left: 4px solid #1ab394; margin: 20px 0;'>
                <p style='color: #676a6c; margin: 0 0 20px 0; font-size: 0.95rem; line-height: 1.5;'>
                    Click the button below to reset your password. This link will expire in 2 minutes for security reasons.
                </p>
                <div style='text-align: center;'>
                    <a href='$resetLink' style='display: inline-block; background: linear-gradient(135deg, #1ab394, #18a689); color: white; padding: 12px 30px; text-decoration: none; border-radius: 4px; font-weight: 400; font-size: 0.95rem; box-shadow: 0 2px 8px rgba(26, 179, 148, 0.3); transition: all 0.3s ease;'>
                        Reset Password
                    </a>
                </div>
            </div>
            
            <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e7eaec;'>
                <p style='color: #a7b1c2; margin: 0; font-size: 0.85rem; line-height: 1.4;'>
                    If you didn't request this password reset, please ignore this email.<br>
                    Your password will remain unchanged.
                </p>
            </div>
        </div>
        $footer
    </div>
</body>
</html>
";

        $mail->send();
        $response = ["status" => true, "message" => "Password reset link sent to your email."];
    } catch (Exception $e) {
        $response = ["status" => false, "message" => "Failed to send email. Please try again later.Error: " . $e->getMessage()];
    }
    return $response;
}

function insertTokenFromDB($user_id, $token, $expires_at, $created_at, $conn): array
{
    $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at, created_at) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $token, $expires_at, $created_at);
    $stmt->execute();
    if ($stmt->affected_rows === 0) {
        $response = ['status' => false, 'message' => "Token insertion failed."];
    } else {
        $response = ['status' => true, 'message' => "Token inserted successfully."];
    }
    $stmt->close();
    return $response;
}

function updateTokenFromDB($user_id, $token, $expires_at, $created_at, $conn): array
{
    $stmt = $conn->prepare("UPDATE password_resets SET token = ?, expires_at = ?, created_at = ? WHERE user_id = ?");
    $stmt->bind_param("sssi", $token, $expires_at, $created_at, $user_id);
    $stmt->execute();
    if ($stmt->affected_rows === 0) {
        $response = ['status' => false, 'message' => "Token update failed."];
    } else {
        $response = ['status' => true, 'message' => "Token updated successfully."];
    }
    $stmt->close();
    return $response;
}

function checkUserExistsByIDAndReturnUser($user_id, $conn): array
{
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $resposne = [
            'status' => false,
            'message' => "User not found."
        ];
    } else {
        $user = $result->fetch_assoc();
        $resposne = [
            'status' => true,
            'user' => $user
        ];
    }

    $stmt->close();
    return $resposne;
}

function checkPasswordResetAttemptIsValid($user_id, $token, $conn): array
{
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE user_id = ? AND token = ?");
    $stmt->bind_param("is", $user_id, $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        $response = ["success" => false, "message" => "Invalid request."];
    } else {
        $response = ["success" => true, "message" => "Valid request."];
    }
    $stmt->close();
    return $response;
}

function updatePassword($password, $user_id, $conn): array
{
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE users SET password = ? where id = ?");
    $stmt->bind_param("si", $hashed_password, $user_id);
    $stmt->execute();
    if ($stmt->affected_rows === 0) {
        $response = [
            "status" => false,
            "message" => "Database Error"
        ];
    } else {
        $response = [
            "status" => true,
            "message" => "Password updated successfully."
        ];
    }
    $stmt->close();
    return $response;
}

function deleteResetToken($token, $user_id, $conn): array
{
    $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ? AND token = ?");
    $stmt->bind_param("is", $user_id, $token);
    $stmt->execute();
    if ($stmt->affected_rows === 0) {
        $response = [];
    } else {
        $response = ["success" => true, "redirect" => "login.php"];
    }
    $stmt->close();
    return $response;
}

function getTotalRecords($conn, $table, $search = ''): array
{
    $query = "SELECT COUNT(DISTINCT username) as total FROM $table";
    $params = [];
    $paramTypes = "";

    if (!empty($search) && $table === 'hyrje_dalje_kryesore') {
        $query = "SELECT COUNT(DISTINCT username) as total FROM $table WHERE username LIKE ?";
        $searchParam = "%" . $search . "%";
        $params[] = $searchParam;
        $paramTypes .= "s";
    }

    $stmt = $conn->prepare($query);

    if (!empty($params)) {
        $bindParams = array($paramTypes);
        foreach ($params as $key => $value) {
            $bindParams[] = &$params[$key];
        }

        call_user_func_array(array($stmt, 'bind_param'), $bindParams);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $response = [
            'status' => false,
            'message' => "No records found.",
            'total' => 0
        ];
    } else {
        $row = $result->fetch_assoc();
        $response = [
            'status' => true,
            'total' => (int)$row['total']
        ];
    }

    $stmt->close();
    return $response;
}

function getHyrjeDaljeFromDB($conn, $start, $length, $search = '', $orderColumn = 0, $orderDir = 'ASC'): array
{
    switch ($orderColumn) {
        case 0:
            $orderByColumn = "Emri";
            break;
        case 1:
            $orderByColumn = "VitetEPunes";
            break;
        case 2:
            $orderByColumn = "DitetQeKaPunuar";
            break;
        case 3:
            $orderByColumn = "OreTePunuar";
            break;
        default:
            $orderByColumn = "Emri";
    }

    $orderDirection = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

    $baseQuery = "
        SELECT
            username AS Emri,
            COUNT(DISTINCT YEAR(data_hyrje)) AS VitetEPunes,
            COUNT(DISTINCT data_hyrje) AS DitetQeKaPunuar,
            ROUND(SUM(
                CASE
                    WHEN ora_hyrje < ora_dalje THEN
                        TIMESTAMPDIFF(SECOND, ora_hyrje, ora_dalje)
                    WHEN ora_hyrje = ora_dalje THEN
                        0
                    ELSE
                        TIMESTAMPDIFF(SECOND, ora_dalje, ora_hyrje)
                END
            ) / 3600) AS OreTePunuar
        FROM
            hyrje_dalje_kryesore
    ";

    $whereClause = "";
    $params = [];
    $paramTypes = "";

    if (!empty($search)) {
        $whereClause = " WHERE username LIKE ? ";
        $searchParam = "%" . $search . "%";
        $params[] = $searchParam;
        $paramTypes .= "s";
    }

    $groupByClause = " GROUP BY Emri ";
    $orderByClause = " ORDER BY {$orderByColumn} {$orderDirection} ";
    $limitClause = " LIMIT ?, ? ";

    $fullQuery = $baseQuery . $whereClause . $groupByClause . $orderByClause . $limitClause;

    $stmt = $conn->prepare($fullQuery);

    if (!empty($params)) {
        $params[] = $start;
        $params[] = $length;
        $paramTypes .= "ii";

        $bindParams = array($paramTypes);
        foreach ($params as $key => $value) {
            $bindParams[] = &$value;
        }

        call_user_func_array(array($stmt, 'bind_param'), $bindParams);
    } else {
        $stmt->bind_param("ii", $start, $length);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $row['Emri'] = htmlspecialchars($row['Emri']);
        $row['VitetEPunes'] = htmlspecialchars($row['VitetEPunes']);
        $row['DitetQeKaPunuar'] = htmlspecialchars($row['DitetQeKaPunuar']);
        $row['OreTePunuar'] = htmlspecialchars($row['OreTePunuar']);
        $data[] = $row;
    }

    $stmt->close();
    return $data;
}

function getYearsDataFromDB($conn, $username): array
{
    $stmt = $conn->prepare("
        SELECT 
            YEAR(data_hyrje) AS Viti,
            COUNT(DISTINCT data_hyrje) AS DitetQeKaPunuar,
            ROUND(" . OretQeKaPunuarQuery . ") AS OretQeKaPunuar,
            ROUND(" . OretQeKaPunuarNeOrarPuneQuery . ") AS OreQeKaPunuarNeOrarPune,
            ROUND(" . OreQePunuarJashteOraritQuery . ") AS OreQePunuarJashteOrarit,
            ROUND(" . OretQeNukKaPunuarNeOrarPuneQuery . ") AS OretQeNukKaPunuarNeOrarPune
        FROM
            hyrje_dalje_kryesore
        WHERE
            username = ?
        GROUP BY 
            Viti
        ORDER BY
            Viti DESC
    ");

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $row['Viti'] = htmlspecialchars($row['Viti']);
        $row['DitetQeKaPunuar'] = htmlspecialchars($row['DitetQeKaPunuar']);
        $row['OretQeKaPunuar'] = htmlspecialchars($row['OretQeKaPunuar']);
        $row['OreQeKaPunuarNeOrarPune'] = htmlspecialchars($row['OreQeKaPunuarNeOrarPune']);
        $row['OreQePunuarJashteOrarit'] = htmlspecialchars($row['OreQePunuarJashteOrarit']);
        $row['OretQeNukKaPunuarNeOrarPune'] = htmlspecialchars($row['OretQeNukKaPunuarNeOrarPune']);
        $data[] = $row;
    }

    $stmt->close();
    return $data;
}

function getMonthsDataFromDB($conn, $username, $year): array
{
    $stmt = $conn->prepare("
               SELECT 
                     MONTH(data_hyrje) AS MonthNum,
                     CASE
                            WHEN MONTH(data_hyrje) = 1 THEN 'Janar'
                            WHEN MONTH(data_hyrje) = 2 THEN 'Shkurt'
                            WHEN MONTH(data_hyrje) = 3 THEN 'Mars'
                            WHEN MONTH(data_hyrje) = 4 THEN 'Prill'
                            WHEN MONTH(data_hyrje) = 5 THEN 'Maj'
                            WHEN MONTH(data_hyrje) = 6 THEN 'Qershor'
                            WHEN MONTH(data_hyrje) = 7 THEN 'Korrik'
                            WHEN MONTH(data_hyrje) = 8 THEN 'Gusht'
                            WHEN MONTH(data_hyrje) = 9 THEN 'Shtator'
                            WHEN MONTH(data_hyrje) = 10 THEN 'Tetor'
                            WHEN MONTH(data_hyrje) = 11 THEN 'Nentor'
                            WHEN MONTH(data_hyrje) = 12 THEN 'Dhjetor'
                     END AS Muaji,
                
                     COUNT(DISTINCT data_hyrje) AS DitetQeKaPunuar,
                    
                     ROUND(" . OretQeKaPunuarQuery . ") AS OretQeKaPunuar,
                     ROUND(" . OretQeKaPunuarNeOrarPuneQuery . ") AS OreQeKaPunuarNeOrarPune,
                     ROUND(" . OreQePunuarJashteOraritQuery . ") AS OreQePunuarJashteOrarit,
                     ROUND(" . OretQeNukKaPunuarNeOrarPuneQuery . ") AS OretQeNukKaPunuarNeOrarPune
               FROM
                     hyrje_dalje_kryesore
               WHERE
                     username = ?
                     AND YEAR(data_hyrje) = ?
               GROUP BY 
                     MonthNum, Muaji
               ORDER BY
                     MonthNum ASC
    ");

    $stmt->bind_param("si", $username, $year);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $row['Muaji'] = htmlspecialchars($row['Muaji']);
        $row['DitetQeKaPunuar'] = htmlspecialchars($row['DitetQeKaPunuar']);
        $row['OretQeKaPunuar'] = htmlspecialchars($row['OretQeKaPunuar']);
        $row['OreQeKaPunuarNeOrarPune'] = htmlspecialchars($row['OreQeKaPunuarNeOrarPune']);
        $row['OreQePunuarJashteOrarit'] = htmlspecialchars($row['OreQePunuarJashteOrarit']);
        $row['OretQeNukKaPunuarNeOrarPune'] = htmlspecialchars($row['OretQeNukKaPunuarNeOrarPune']);
        $data[] = $row;
    }

    $stmt->close();
    return $data;
}


function getAllDataHyrjeDaljeData(): array
{
    $conn = connectDB();
    $data = getAllDataFromHyrjeDaljeFromDB($conn);
    disconnectDB($conn);
    return $data;
}

function getAllDataFromHyrjeDaljeFromDB($conn): array
{
    $stmt = $conn->prepare("
               SELECT 
                     DISTINCT username AS Emri,
                     YEAR(data_hyrje)           AS Viti,
                     MONTH(data_hyrje) as Monthnum,
                     CASE
                            WHEN MONTH(data_hyrje) = 1 THEN 'Janar'
                            WHEN MONTH(data_hyrje) = 2 THEN 'Shkurt'
                            WHEN MONTH(data_hyrje) = 3 THEN 'Mars'
                            WHEN MONTH(data_hyrje) = 4 THEN 'Prill'
                            WHEN MONTH(data_hyrje) = 5 THEN 'Maj'
                            WHEN MONTH(data_hyrje) = 6 THEN 'Qershor'
                            WHEN MONTH(data_hyrje) = 7 THEN 'Korrik'
                            WHEN MONTH(data_hyrje) = 8 THEN 'Gusht'
                            WHEN MONTH(data_hyrje) = 9 THEN 'Shtator'
                            WHEN MONTH(data_hyrje) = 10 THEN 'Tetor'
                            WHEN MONTH(data_hyrje) = 11 THEN 'Nentor'
                            WHEN MONTH(data_hyrje) = 12 THEN 'Dhjetor'
                     END AS Muaji,
                
                     COUNT(DISTINCT data_hyrje) AS DitetQeKaPunuar,
                    
                     ROUND(" . OretQeKaPunuarQuery . ") AS OretQeKaPunuar,
                     ROUND(" . OretQeKaPunuarNeOrarPuneQuery . ") AS OreQeKaPunuarNeOrarPune,
                     ROUND(" . OreQePunuarJashteOraritQuery . ") AS OreQePunuarJashteOrarit,
                     ROUND(" . OretQeNukKaPunuarNeOrarPuneQuery . ") AS OretQeNukKaPunuarNeOrarPune
               FROM
                     hyrje_dalje_kryesore
               GROUP BY 
                     Emri, Viti, Muaji
               ORDER BY 
                     Emri, Viti DESC, Monthnum
    ");

    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    $last_emri = '';
    $last_viti = '';
    $year_counts = [];

    while ($row = $result->fetch_assoc()) {
        $emri = htmlspecialchars($row['Emri']);
        $viti = htmlspecialchars($row['Viti']);
        $muaji = htmlspecialchars($row['Muaji']);

        if (!isset($data[$emri])) {
            $data[$emri] = [
                "Totali" => [
                    'DitetQeKaPunuar' => 0,
                    'OreTePunuar' => 0,
                    'VitetEPunes' => 0,
                ]
            ];
            $year_counts[$emri] = [];
        }

        if (!isset($data[$emri][$viti])) {
            $data[$emri][$viti] = [
                'Muaji' => [],
                'Totali' => [
                    'DitetQeKaPunuar' => 0,
                    'OretQeKaPunuar' => 0,
                    'OreQeKaPunuarNeOrarPune' => 0,
                    'OreQePunuarJashteOrarit' => 0,
                    'OretQeNukKaPunuarNeOrarPune' => 0
                ]
            ];

            if (!in_array($viti, $year_counts[$emri])) {
                $year_counts[$emri][] = $viti;
                $data[$emri]['Totali']['VitetEPunes'] += 1;
            }
        }

        $data[$emri][$viti]['Muaji'][$muaji] = [
            'DitetQeKaPunuar' => htmlspecialchars($row['DitetQeKaPunuar']),
            'OretQeKaPunuar' => htmlspecialchars($row['OretQeKaPunuar']),
            'OreQeKaPunuarNeOrarPune' => htmlspecialchars($row['OreQeKaPunuarNeOrarPune']),
            'OreQePunuarJashteOrarit' => htmlspecialchars($row['OreQePunuarJashteOrarit']),
            'OretQeNukKaPunuarNeOrarPune' => htmlspecialchars($row['OretQeNukKaPunuarNeOrarPune'])
        ];

        $data[$emri][$viti]['Totali']['DitetQeKaPunuar'] += (int)$row['DitetQeKaPunuar'];
        $data[$emri][$viti]['Totali']['OretQeKaPunuar'] += (float)$row['OretQeKaPunuar'];
        $data[$emri][$viti]['Totali']['OreQeKaPunuarNeOrarPune'] += (float)$row['OreQeKaPunuarNeOrarPune'];
        $data[$emri][$viti]['Totali']['OreQePunuarJashteOrarit'] += (float)$row['OreQePunuarJashteOrarit'];
        $data[$emri][$viti]['Totali']['OretQeNukKaPunuarNeOrarPune'] += (float)$row['OretQeNukKaPunuarNeOrarPune'];

        $data[$emri]['Totali']['DitetQeKaPunuar'] += (int)$row['DitetQeKaPunuar'];
        $data[$emri]['Totali']['OreTePunuar'] += (float)$row['OretQeKaPunuar'];
    }

    $stmt->close();
    return $data;
}


function checkUsernameDuplicateForUpdate($username, $email, $user_id, $conn): array
{
    $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $stmt->bind_param("ssi", $username, $email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $response = [
            'status' => false,
            'message' => "Username or email already exists for another user."
        ];
    } else {
        $response = [
            'status' => true,
            'message' => "Username and email are available."
        ];
    }

    $stmt->close();
    return $response;
}

function updateUserProfileInDB($user_id, $username, $firstName, $lastName, $phone, $email, $birthday, $address, $conn): array
{
    $stmt = $conn->prepare("
        UPDATE users 
        SET username = ?, name = ?, last_name = ?, phone = ?, email = ?, birthday = ?, address = ?
        WHERE id = ?
    ");

    $stmt->bind_param("sssssssi", $username, $firstName, $lastName, $phone, $email, $birthday, $address, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        $response = [
            'status' => false,
            'message' => "No changes were made or user not found."
        ];
    } else {
        $response = [
            'status' => true,
            'message' => "Profile updated successfully."
        ];
    }

    $stmt->close();
    return $response;
}

function getUserProfileDataFromDB($user_id, $conn): array
{
    $stmt = $conn->prepare("SELECT username, name, last_name, email, phone, birthday, address FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return [];
    }

    $user_data = $result->fetch_assoc();
    $stmt->close();

    return $user_data;
}

function getUserByIdFromDB($user_id, $conn): array
{
    try {
        $stmt = $conn->prepare("
            SELECT 
                users.name, 
                users.username, 
                users.last_name, 
                users.address, 
                users.phone, 
                users.email, 
                users.manager_id, 
                users.birthday, 
                users.termsChecked, 
                roles.name AS role_name
            FROM 
                users
            INNER JOIN 
                user_roles ON users.id = user_roles.user_id
            INNER JOIN 
                roles ON user_roles.role_id = roles.id
            WHERE 
                users.id = ?
        ");

        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $stmt->close();
            return [
                'status' => false,
                'message' => 'User not found.'
            ];
        }

        $user = $result->fetch_assoc();
        $stmt->close();

        // Format the data
        $user['role_name'] = ucfirst(strtolower($user['role_name']));
        $user['birthday'] = date('d/m/Y', strtotime($user['birthday']));
        $user['termsChecked'] = $user['termsChecked'] == 1;

        // Get manager username if manager_id exists
        $user['manager_username'] = null;
        if ($user['manager_id']) {
            $manager_stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
            $manager_stmt->bind_param("i", $user['manager_id']);
            $manager_stmt->execute();
            $manager_result = $manager_stmt->get_result();

            if ($manager_result->num_rows > 0) {
                $manager = $manager_result->fetch_assoc();
                $user['manager_username'] = $manager['username'];
            }
            $manager_stmt->close();
        }

        return [
            'status' => true,
            'user' => $user
        ];

    } catch (Exception $e) {
        return [
            'status' => false,
            'message' => 'Database error occurred.'
        ];
    }
}

function validateAdminManagerRules($user_id, $role_id, $manager_id, $conn): array
{
    $is_admin = false;

    if ($role_id !== null) {
        // Check if new role is admin
        $stmt = $conn->prepare("SELECT name FROM roles WHERE id = ?");
        $stmt->bind_param("i", $role_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $role_row = $result->fetch_assoc();
        $stmt->close();

        if ($role_row && $role_row['name'] === 'ADMIN') {
            $is_admin = true;
        }
    } else {
        // Check current role
        $stmt = $conn->prepare("SELECT r.name FROM roles r JOIN user_roles ur ON r.id = ur.role_id WHERE ur.user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $role_row = $result->fetch_assoc();
        $stmt->close();

        if ($role_row && $role_row['name'] === 'ADMIN') {
            $is_admin = true;
        }
    }

    // Admin users cannot have managers
    if ($is_admin && $manager_id !== null) {
        return [
            'status' => false,
            'message' => 'Admin users cannot have managers.'
        ];
    }

    return [
        'status' => true,
        'message' => 'Validation passed.'
    ];
}

function updateUserDataInDB($user_id, $username, $firstName, $lastName, $phone, $email, $birthday, $address, $manager_id, $conn): array
{
    try {
        $stmt = $conn->prepare("
            UPDATE users 
            SET username = ?, name = ?, last_name = ?, phone = ?, email = ?, birthday = ?, address = ?, manager_id = ?
            WHERE id = ?
        ");

        $stmt->bind_param("sssssssii", $username, $firstName, $lastName, $phone, $email, $birthday, $address, $manager_id, $user_id);
        $stmt->execute();

        $affected_rows = $stmt->affected_rows;
        $stmt->close();

        return [
            'status' => true,
            'message' => 'User data updated successfully.',
            'affected_rows' => $affected_rows
        ];

    } catch (Exception $e) {
        return [
            'status' => false,
            'message' => 'Failed to update user data.'
        ];
    }
}

function updateUserRoleInDB($user_id, $role_id, $conn): array
{
    try {
        // Get current role info
        $stmt = $conn->prepare("SELECT r.name AS role_name FROM roles r 
                        JOIN user_roles ur ON r.id = ur.role_id 
                        WHERE ur.user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_role = $result->fetch_assoc();
        $stmt->close();

        // Get new role info
        $stmt = $conn->prepare("SELECT name FROM roles WHERE id = ?");
        $stmt->bind_param("i", $role_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $new_role = $result->fetch_assoc();
        $stmt->close();

        // Check if user_roles entry exists
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_roles WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        // Update or insert role
        if ($row['count'] > 0) {
            $stmt = $conn->prepare("UPDATE user_roles SET role_id = ? WHERE user_id = ?");
            $stmt->bind_param("ii", $role_id, $user_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $role_id);
        }
        $stmt->execute();
        $stmt->close();

        // If changing from MANAGER to non-MANAGER, remove this user as manager from others
        if ($current_role && $current_role['role_name'] === 'MANAGER' &&
            $new_role && $new_role['name'] !== 'MANAGER') {

            $null_manager = null;
            $stmt = $conn->prepare("UPDATE users SET manager_id = ? WHERE manager_id = ?");
            $stmt->bind_param("si", $null_manager, $user_id);
            $stmt->execute();
            $stmt->close();
        }

        return [
            'status' => true,
            'message' => 'User role updated successfully.'
        ];

    } catch (Exception $e) {
        return [
            'status' => false,
            'message' => 'Failed to update user role.'
        ];
    }
}

function getManagersFromDB($conn): array
{
    try {
        $stmt = $conn->prepare("
            SELECT username
            FROM users u
            INNER JOIN user_roles ur ON u.id = ur.user_id
            INNER JOIN roles r ON r.id = ur.role_id
            WHERE r.name = 'MANAGER'
        ");

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $managers = [];
            while ($row = $result->fetch_assoc()) {
                $managers[] = [
                    'username' => htmlspecialchars($row['username'])
                ];
            }

            $response = [
                'status' => true,
                'message' => "Managers found.",
                'data' => $managers
            ];
        } else {
            $response = [
                'status' => false,
                'message' => "No managers found."
            ];
        }

        $stmt->close();
        return $response;

    } catch (Exception $e) {
        return [
            'status' => false,
            'message' => "Database error occurred while fetching managers."
        ];
    }
}