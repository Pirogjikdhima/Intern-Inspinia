<?php

function isLoggedIn(): bool
{
    session_start();
    if (isset($_SESSION['user_id']) && isset($_SESSION['username']) && isset($_SESSION['role']) && isset($_SESSION['email'])) {
        return true;
    } else {
        return false;
    }
}

function register(): void
{
    if (!postRequest() || getRequest()) {
        sendError("Method not allowed.", 405);
    }
    $username = validateInput(trim($_POST['username'] ?? null), UsernameRegex, null, "Invalid username.");
    $firstName = validateInput(trim($_POST['firstName'] ?? null), NameRegex, null, "Invalid First Name.");
    $lastname = validateInput(trim($_POST['lastName'] ?? null), NameRegex, null, "Invalid Last Name.");
    $phone = validateInput(trim($_POST['phone'] ?? null), NumberRegex, null, "Invalid phone number.");
    $birthday = validateInput(trim($_POST['birthday'] ?? null), null, null, "Invalid birthday.");
    $birthday = validateBirthday($birthday);
    $email = validateInput(trim($_POST['email'] ?? null), null, FILTER_VALIDATE_EMAIL, "Invalid email.");
    $address = trim($_POST['address']);
    $password = validateInput(trim($_POST['password'] ?? null), PasswordRegex, null, "Invalid password.");
    $confirmPassword = validateInput(trim($_POST['confirmPassword'] ?? null), PasswordRegex, null, "Invalid confirm password.");
    $termsAccepted = isset($_POST['termsChecked']);

    if ($password !== $confirmPassword) {
        sendError("Password and confirm password do not match.", 422);
    }

    $conn = connectDB();
    $user_exists = checkUserExistByEmailAndReturnUser($email, $conn);
    if ($user_exists['status']) {
        sendError("Email Already Exists", 422);
    }

    try {
        $conn->begin_transaction();

        $registration = registerUser($username, $firstName, $lastname, $phone, $email, $birthday, $address, $password, $termsAccepted, $conn);
        if ($registration['status'] === false) {
            $conn->rollback();
            sendError($registration['message'], 500);
        }
        $user_id = $registration['user_id'];
        $role_id = 1;

        $inserted_role = insertRole($user_id, $role_id, $conn);
        if ($inserted_role === false) {
            $conn->rollback();
            sendError("Registration Error", 500);
        }
    } catch (exception $e) {
        $conn->rollback();
        sendError("Registration Error", 500);
    }
    $conn->commit();
    disconnectDB($conn);
    sendSuccess("Registration successful. You can now Log in.", 201, ["location" => "login.php"]);
}

function login(): void
{
    if (!postRequest() || getRequest()) {
        sendError("Method not allowed.", 405);
    }

    $email = validateInput(trim($_POST['email'] ?? null), null, FILTER_VALIDATE_EMAIL, "Invalid email or password.");
    $password = validateInput(trim($_POST['password'] ?? null), PasswordRegex, null, "Invalid email or password.");

    $conn = connectDB();
    $user_exists = checkUserExistByEmailAndReturnUser($email, $conn);
    if ($user_exists['status'] === false) {
        sendError($user_exists['message'], 401);
    }

    $user = $user_exists['user'];
    if (!password_verify($password, $user['password'])) {
        sendError("Invalid email or password.", 401);
    }

    $role_name = getRoleName($user['id'], $conn);
    $user['role'] = $role_name;

    session_start();
    $_SESSION['user_id'] = encryptMessage($user['id']);
    $_SESSION['email'] = encryptMessage($user['email']);
    $_SESSION['username'] = encryptMessage($user['username']);
    $_SESSION['role'] = encryptMessage($user['role']);
    disconnectDB($conn);
    sendSuccess("Login successful.", 200, ["location" => "profile.php"]);
}

function logout(): void
{
    session_start();
    session_unset();
    session_destroy();
    sendSuccess("Logged out successfully.", 200, ["location" => "login.php"]);
}

function resetPassword(): void
{
    if (!postRequest() || getRequest()) {
        sendError("Method not allowed.", 405);
    }
    $combined_token = validateInput(trim($_POST['combined_token'] ?? null), null, null, "Bad request.", 400);
    $token = substr($combined_token, 0, 64);
    $user_id = (int)substr($combined_token, 64);
    $new_password = validateInput(trim($_POST['new_password'] ?? null), PasswordRegex, null, "Invalid Password.", 422);
    $new_confirm_password = validateInput(trim($_POST['new_confirm_password'] ?? null), PasswordRegex, null, "Invalid Password.", 422);
    if ($new_password !== $new_confirm_password) {
        sendError("Password and confirm password do not match.", 422);
    }
    $conn = connectDB();
    $attempt_valid = checkPasswordResetAttemptIsValid($user_id, $token, $conn);
    if ($attempt_valid['success'] === false) {
        sendError($attempt_valid['message'], 500);
    }
    $user_exists = checkUserExistsByIDAndReturnUser($user_id, $conn);
    if ($user_exists['success'] === false) {
        sendError("User not found.", 422);
    }
    $old_password = $user_exists['password'];
    $same_password = password_verify($new_password, $old_password);
    if ($same_password === false) {
        sendError("New password cannot be the same as the old password.", 422);
    }

    $conn->begin_transaction();
    $password_updated = updatePassword($new_password, $user_id, $conn);
    if ($password_updated['status'] === false) {
        $conn->rollback();
        sendError($password_updated['message'], 500);
    }
    $token_deleted = deleteResetToken($token, $user_id, $conn);
    if ($token_deleted['status'] === false) {
        $conn->rollback();
        sendError($token_deleted['message'], 500);
    }
    $conn->commit();
    disconnectDB($conn);
    sendSuccess($password_updated['message'], 200, $token_deleted['redirect']);
}

function changePassword(): void
{
    if (!postRequest() || getRequest()) {
        sendError("Method not allowed.", 405);
    }

    if (!isLoggedIn()) {
        sendError("You are not logged in.", 401);
    }

    $action = validateInput(trim($_POST['action'] ?? null), null, null, "Invalid action.");
    if ($action !== 'change-password') {
        sendError("Invalid action.", 403);
    }

    $user_id = validateInput(trim($_POST['user_id'] ?? null), null, null, "Invalid user id.");
    $user_id = (int)decryptMessage($user_id);

    if ($user_id <= 0) {
        sendError("Invalid user ID", 400);
    }

    $session_user_id = (int)decryptMessage($_SESSION['user_id']);
    if ($user_id !== $session_user_id) {
        sendError("You can only change your own password.", 403);
    }

    $new_password = validateInput(trim($_POST['new_password'] ?? null), PasswordRegex, null, "Password must be at least 8 characters and include lowercase, uppercase, number, and special character.");
    $new_confirm_password = validateInput(trim($_POST['new_confirm_password'] ?? null), PasswordRegex, null, "Password must be at least 8 characters and include lowercase, uppercase, number, and special character.");

    if ($new_password !== $new_confirm_password) {
        sendError("Passwords do not match.", 422);
    }

    try {
        $conn = connectDB();

        $user_exists = checkUserExistsByIDAndReturnUser($user_id, $conn);
        if ($user_exists['status'] === false) {
            disconnectDB($conn);
            sendError("User not found.", 404);
        }

        $user_data = $user_exists['user'];
        $old_password = $user_data['password'];

        if (password_verify($new_password, $old_password)) {
            disconnectDB($conn);
            sendError("New password cannot be the same as the old password.", 422);
        }

        $conn->begin_transaction();

        $password_update = updatePassword($new_password, $user_id, $conn);
        if ($password_update['status'] === false) {
            $conn->rollback();
            disconnectDB($conn);
            sendError($password_update['message'], 500);
        }

        $conn->commit();
        disconnectDB($conn);

        sendSuccess("Password changed successfully.", 200, ["location" => "profile.php"]);

    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollback();
            disconnectDB($conn);
        }
        sendError("Password update failed.", 500);
    }
}

function getAllUsers(): void
{
    if (!getRequest() || postRequest()) {
        sendError("Method not allowed.", 405);
    }
    if (!isLoggedIn()) {
        sendError("You are not logged in.", 401);
    }

    $role = validateInput(trim($_GET['role'] ?? null), null, null, "Invalid role.");
    $allowedRole = validateInput(trim($_SESSION['role'] ?? null), null, null, "Invalid role.");
    $permission = checkPermission($role, $allowedRole);
    if ($permission['status'] === false) {
        sendError($permission['message'], 403);
    }

    $action = validateInput(trim($_GET['action'] ?? null), null, null, "Invalid action.");
    $allowedAction = 'get-all-users';
    $permission = checkPermission($action, $allowedAction);
    if ($permission['status'] === false) {
        sendError($permission['message'], 403);
    }
    $conn = connectDB();
    $users = getAllUsersFromDB($conn);

    if ($users['status'] === false) {
        sendError($users['message'], 404);
    }
    disconnectDB($conn);
    sendSuccess($users['message'], 200, ["data" => $users['data']]);
}

function getManagers(): void
{
    if (!getRequest() || postRequest()) {
        sendError("Method not allowed.", 405);
    }

    if (!isLoggedIn()) {
        sendError("You are not logged in.", 401);
    }

    $role = validateInput(trim($_GET['role'] ?? null), null, null, "Invalid role.");
    $allowedRole = validateInput(trim($_SESSION['role'] ?? null), null, null, "Invalid role.");
    $permission = checkPermission($role, $allowedRole);
    if ($permission['status'] === false) {
        sendError($permission['message'], 403);
    }

    $action = validateInput(trim($_GET['action'] ?? null), null, null, "Invalid action.");
    $allowedAction = 'get-all-users';
    $permission = checkPermission($action, $allowedAction);
    if ($permission['status'] === false) {
        sendError($permission['message'], 403);
    }

    try {
        $conn = connectDB();
        $managers = getManagersFromDB($conn);

        if ($managers['status'] === false) {
            disconnectDB($conn);
            sendError($managers['message'], 404);
        }

        disconnectDB($conn);
        sendSuccess($managers['message'], 200, ["data" => $managers['data']]);

    } catch (Exception $e) {
        if (isset($conn)) {
            disconnectDB($conn);
        }
        sendError("An error occurred while retrieving managers.", 500);
    }
}
function getAgeReport(): void
{
    if (!getRequest() || postRequest()) {
        sendError("Method not allowed.", 405);
    }
    if (!isLoggedIn()) {
        sendError("You are not logged in.", 401);
    }
    $role = validateInput(trim($_GET['role'] ?? null), null, null, "Invalid role.");
    $allowedRole = validateInput(trim($_SESSION['role'] ?? null), null, null, "Invalid role.");
    $permission = checkPermission($role, $allowedRole);
    if ($permission['status'] === false) {
        sendError($permission['message'], 403);
    }

    $action = validateInput(trim($_GET['action'] ?? null), null, null, "Invalid action.");
    $allowedAction = 'get-age-report';
    $permission = checkPermission($action, $allowedAction);
    if ($permission['status'] === false) {
        sendError($permission['message'], 403);
    }
    $manager_id = validateInput(trim($_GET['manager_id'] ?? null), null, null, "Missing manager id.");
    $manager_id = decryptMessage($manager_id);
    $conn = connectDB();
    $age_report = getAgeReportFromDB($conn, $manager_id);
    disconnectDB($conn);
    sendSuccess($age_report['message'], 200, ["data" => $age_report['data']]);
}


function getUser(): void
{
    if (!getRequest() || postRequest()) {
        sendError("Method not allowed.", 405);
    }

    if (!isLoggedIn()) {
        sendError("You are not logged in.", 401);
    }

    $role = validateInput(trim($_GET['role'] ?? null), null, null, "Invalid role.");
    $allowedRole = validateInput(trim($_SESSION['role'] ?? null), null, null, "Invalid role.");
    $permission = checkPermission($role, $allowedRole);
    if ($permission['status'] === false) {
        sendError($permission['message'], 403);
    }

    $action = validateInput(trim($_GET['action'] ?? null), null, null, "Invalid action.");
    $allowedAction = 'get-user';
    $permission = checkPermission($action, $allowedAction);
    if ($permission['status'] === false) {
        sendError($permission['message'], 403);
    }

    $user_id = validateInput(trim($_GET['user_id'] ?? null), null, null, "Invalid user id.");
    $user_id = decryptMessage($user_id);

    try {
        $conn = connectDB();

        $user_data = getUserByIdFromDB($user_id, $conn);

        if ($user_data['status'] === false) {
            disconnectDB($conn);
            sendError($user_data['message'], 404);
        }

        disconnectDB($conn);
        sendSuccess("User retrieved successfully.", 200, ["data" => $user_data['user']]);

    } catch (Exception $e) {
        if (isset($conn)) {
            disconnectDB($conn);
        }
        error_log("Get user error: " . $e->getMessage());
        sendError("An error occurred while retrieving user data.", 500);
    }
}

function addUser()
{
    if (!isLoggedIn()) {
        sendError("You are not logged in.", 401);
    }
    if (!postRequest()) {
        sendError("Method not allowed.", 405);
    }
    $role = validateInput(trim($_POST['role'] ?? null), null, null, "Invalid role.");
    $allowedRole = validateInput(trim($_SESSION['role'] ?? null), null, null, "Invalid role.");
    $permission = checkPermission($role, $allowedRole);
    if ($permission['status'] === false) {
        sendError($permission['message'], 403);
    }

    $action = validateInput(trim($_POST['action'] ?? null), null, null, "Invalid action.");
    $allowedAction = 'add-user';
    $permission = checkPermission($action, $allowedAction);
    if ($permission['status'] === false) {
        sendError($permission['message'], 403);
    }

    $username = validateInput(trim($_POST['username'] ?? null), UsernameRegex, null, "Invalid username.");
    $firstName = validateInput(trim($_POST['firstName'] ?? null), NameRegex, null, "Invalid First Name.");
    $lastname = validateInput(trim($_POST['lastName'] ?? null), NameRegex, null, "Invalid Last Name.");
    $phone = validateInput(trim($_POST['phone'] ?? null), NumberRegex, null, "Invalid phone number.");
    $birthday = validateInput(trim($_POST['birthday'] ?? null), null, null, "Invalid birthday.");
    $birthday = validateBirthday($birthday);
    $email = validateInput(trim($_POST['email'] ?? null), null, FILTER_VALIDATE_EMAIL, "Invalid email.");
    $address = trim($_POST['address']);
    $password = validateInput(trim($_POST['password'] ?? null), PasswordRegex, null, "Invalid password.");
    $confirmPassword = validateInput(trim($_POST['confirmPassword'] ?? null), PasswordRegex, null, "Invalid confirm password.");
    $termsAccepted = isset($_POST['termsChecked']);
    $manager_id = isset($_POST['manager_id']) ? decryptMessage($_POST['manager_id']) : null;
    $role_id = isset($_POST['role_id']) ? decryptMessage($_POST['role_id']) : null;

    if ($password !== $confirmPassword) {
        sendError("Password and confirm password do not match.", 422);
    }

    $conn = connectDB();
    $user_exists = checkUserExistByEmailOrUsername($email, $username, $conn);
    if ($user_exists['status']) {
        sendError($user_exists['message'], 422);
    }
    try {
        $conn->begin_transaction();
        $registration = addUserFromDB($username, $firstName, $lastname, $phone, $email, $birthday, $address, $password, $termsAccepted, $manager_id, $conn);
        if ($registration['status'] === false) {
            $conn->rollback();
            sendError($registration['message'], 500);
        }
        $user_id = $registration['user_id'];

        $inserted_role = insertRole($user_id, $role_id, $conn);
        if ($inserted_role === false) {
            $conn->rollback();
            sendError("User creation failed.", 500);
        }
    } catch (exception $e) {
        $conn->rollback();
        sendError("User creation failed.", 500);
    }
    $conn->commit();
    disconnectDB($conn);
    sendSuccess("Successfully added User.", 201);
}

function updateUser(): void
{
    if (!postRequest() || getRequest()) {
        sendError("Method not allowed.", 405);
    }

    if (!isLoggedIn()) {
        sendError("You are not logged in.", 401);
    }

    $role = validateInput(trim($_POST['role'] ?? null), null, null, "Invalid role.");
    $allowedRole = validateInput(trim($_SESSION['role'] ?? null), null, null, "Invalid role.");
    $permission = checkPermission($role, $allowedRole);
    if ($permission['status'] === false) {
        sendError($permission['message'], 403);
    }

    $action = validateInput(trim($_POST['action'] ?? null), null, null, "Invalid action.");
    $allowedAction = 'update-user';
    $permission = checkPermission($action, $allowedAction);
    if ($permission['status'] === false) {
        sendError($permission['message'], 403);
    }

    $user_id = validateInput(trim($_POST['user_id'] ?? null), null, null, "Invalid user id.");
    $user_id = decryptMessage($user_id);

    $username = validateInput(trim($_POST['username'] ?? null), UsernameRegex, null, "Invalid username.");
    $firstName = validateInput(trim($_POST['firstName'] ?? null), NameRegex, null, "Invalid first name.");
    $lastName = validateInput(trim($_POST['lastName'] ?? null), NameRegex, null, "Invalid last name.");
    $phone = validateInput(trim($_POST['phone'] ?? null), NumberRegex, null, "Invalid phone number.");
    $email = validateInput(trim($_POST['email'] ?? null), null, FILTER_VALIDATE_EMAIL, "Invalid email address.");
    $address = trim($_POST['address'] ?? '');
    $birthday = validateInput(trim($_POST['birthday'] ?? null), null, null, "Invalid birthday.");
    $birthday = validateBirthday($birthday);

    $termsChecked = isset($_POST['termsChecked']) && $_POST['termsChecked'] === 'true';
    if (!$termsChecked) {
        sendError("You must accept the terms and conditions.", 422);
    }

    $role_id = isset($_POST['role_id']) && !empty($_POST['role_id']) ? decryptMessage(trim($_POST['role_id'])) : null;
    $manager_id = isset($_POST['manager_id']) && !empty($_POST['manager_id']) ? decryptMessage(trim($_POST['manager_id'])) : null;

    if ($manager_id !== null && $manager_id == $user_id) {
        sendError("A manager cannot have itself as a manager.", 422);
    }

    try {
        $conn = connectDB();

        $user_exists = checkUserExistsByIDAndReturnUser($user_id, $conn);
        if ($user_exists['status'] === false) {
            disconnectDB($conn);
            sendError("User not found.", 404);
        }

        $admin_validation = validateAdminManagerRules($user_id, $role_id, $manager_id, $conn);
        if ($admin_validation['status'] === false) {
            disconnectDB($conn);
            sendError($admin_validation['message'], 422);
        }

        $duplicate_check = checkUsernameDuplicateForUpdate($username, $email, $user_id, $conn);
        if ($duplicate_check['status'] === false) {
            disconnectDB($conn);
            sendError($duplicate_check['message'], 422);
        }

        $conn->begin_transaction();

        $update_result = updateUserDataInDB($user_id, $username, $firstName, $lastName, $phone, $email, $birthday, $address, $manager_id, $conn);

        if ($update_result['status'] === false) {
            $conn->rollback();
            disconnectDB($conn);
            sendError($update_result['message'], 500);
        }

        if ($role_id !== null) {
            $role_update = updateUserRoleInDB($user_id, $role_id, $conn);
            if ($role_update['status'] === false) {
                $conn->rollback();
                disconnectDB($conn);
                sendError($role_update['message'], 500);
            }
        }

        $conn->commit();
        disconnectDB($conn);

        sendSuccess("User updated successfully.", 200, ["updated" => true]);

    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollback();
            disconnectDB($conn);
        }

        sendError("User update failed.", 500);
    }
}

function deleteUser(): void
{
    if (getRequest() || !postRequest()) {
        sendError("Method not allowed.", 405);
    }
    if (!isLoggedIn()) {
        sendError("You are not logged in.", 401);
    }
    $role = validateInput(trim($_POST['role'] ?? null), null, null, "Invalid role.");
    $allowedRole = validateInput(trim($_SESSION['role'] ?? null), null, null, "Invalid role.");
    $permission = checkPermission($role, $allowedRole);
    if ($permission['status'] === false) {
        sendError($permission['message'], 403);
    }

    $action = validateInput(trim($_POST['action'] ?? null), null, null, "Invalid action.");
    $allowedAction = 'delete-user';
    $permission = checkPermission($action, $allowedAction);
    if ($permission['status'] === false) {
        sendError($permission['message'], 403);
    }
    $user_id = validateInput(trim($_POST['user_id'] ?? null), null, null, "Invalid user id.");
    $user_id = decryptMessage($user_id);

    $conn = connectDB();
    $deleted_user = deleteUserFromDB($conn, $user_id);
    if ($deleted_user['status'] === false) {
        sendError($deleted_user['message'], 500);
    }
    disconnectDB($conn);
    sendSuccess($deleted_user['message']);
}


function getProfile(): void
{
    if (!isLoggedIn()) {
        sendError("You are not logged in.", 401);
    }
    if (!getRequest() || postRequest()) {
        sendError("Method not allowed.", 405);
    }

    $user_id = validateInput(trim($_GET['user_id'] ?? null), null, null, "Invalid user id.");
    $user_id = decryptMessage($user_id);

    $conn = connectDB();

    $user_exists = checkUserExistsByIDAndReturnUser($user_id, $conn);

    if ($user_exists['status'] === false) {
        session_unset();
        session_destroy();
        sendError($user_exists['message'], 404, ["location" => "login.php"]);
    }

    $userData = $user_exists['user'];
    disconnectDB($conn);

    $formatted_birthday = date('d/m/Y', strtotime($userData['birthday']));

    sendSuccess("Profile retrieved successfully", 200, [
        "username" => htmlspecialchars($userData['username']),
        "firstName" => htmlspecialchars($userData['name']),
        "lastName" => htmlspecialchars($userData['last_name']),
        "birthday" => htmlspecialchars($formatted_birthday),
        "phone" => htmlspecialchars($userData['phone']),
        "email" => htmlspecialchars($userData['email']),
        "address" => htmlspecialchars($userData['address'])
    ]);
}

function updateProfile(): void
{
    if (!isLoggedIn()) {
        sendError("You are not logged in.", 401);
    }

    if (!postRequest()) {
        sendError("Method not allowed.", 405);
    }

    $action = validateInput(trim($_POST['action'] ?? null), null, null, "Invalid action.");
    if ($action !== 'update-profile') {
        sendError("Invalid action.", 403);
    }

    $user_id = validateInput(trim($_POST['user_id'] ?? null), null, null, "Invalid user id.");
    $user_id = decryptMessage($user_id);

    $session_user_id = decryptMessage($_SESSION['user_id']);
    if ($user_id != $session_user_id) {
        sendError("You can only update your own profile.", 403);
    }

    $username = validateInput(trim($_POST['username'] ?? null), UsernameRegex, null, "Invalid username.");
    $firstName = validateInput(trim($_POST['firstName'] ?? null), NameRegex, null, "Invalid first name.");
    $lastName = validateInput(trim($_POST['lastName'] ?? null), NameRegex, null, "Invalid last name.");
    $phone = validateInput(trim($_POST['phone'] ?? null), NumberRegex, null, "Invalid phone number.");
    $email = validateInput(trim($_POST['email'] ?? null), null, FILTER_VALIDATE_EMAIL, "Invalid email address.");
    $address = trim($_POST['address'] ?? '');

    $birthday = validateInput(trim($_POST['birthday'] ?? null), null, null, "Invalid birthday.");
    $birthday = validateBirthday($birthday);

    try {
        $conn = connectDB();

        $user_exists = checkUserExistsByIDAndReturnUser($user_id, $conn);
        if ($user_exists['status'] === false) {
            disconnectDB($conn);
            sendError("User not found.", 404);
        }
        $duplicate_check = checkUsernameDuplicateForUpdate($username, $email, $user_id, $conn);
        if ($duplicate_check['status'] === false) {
            disconnectDB($conn);
            sendError($duplicate_check['message'], 422);
        }

        $conn->begin_transaction();

        $update_result = updateUserProfileInDB($user_id, $username, $firstName, $lastName, $phone, $email, $birthday, $address, $conn);

        if ($update_result['status'] === false) {
            $conn->rollback();
            disconnectDB($conn);
            sendError($update_result['message'], 500);
        }

        $conn->commit();

        $updated_user = getUserProfileDataFromDB($user_id, $conn);
        $_SESSION['user_id'] = $updated_user['user_id'];
        $_SESSION['username'] = encryptMessage($updated_user['username']);
        $_SESSION['email'] = encryptMessage($updated_user['email']);
        $_SESSION['role'] = encryptMessage($updated_user['role']);
        $updated_user['birthday'] = date('d/m/Y', strtotime($updated_user['birthday']));

        disconnectDB($conn);

        sendSuccess("Profile updated successfully.", 200, [
            "data" => [
                "username" => htmlspecialchars($updated_user['username']),
                "firstName" => htmlspecialchars($updated_user['name']),
                "lastName" => htmlspecialchars($updated_user['last_name']),
                "email" => htmlspecialchars($updated_user['email']),
                "phone" => htmlspecialchars($updated_user['phone']),
                "birthday" => htmlspecialchars($updated_user['birthday']),
                "address" => htmlspecialchars($updated_user['address'])
            ]
        ]);

    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollback();
            disconnectDB($conn);
        }
        sendError("An error occurred while updating your profile." . $e->getMessage(), 500);
    }
}


function getMonthData(): void
{
    if (!isLoggedIn()) {
        sendError("You are not logged in.", 401);
    }

    if (!getRequest() || postRequest()) {
        sendError("Method not allowed.", 405);
    }

    $username = validateInput(trim($_GET['username'] ?? null), null, null, "Invalid username.", 400);
    $year = validateInput(trim($_GET['year'] ?? null), null, null, "Invalid year.", 400);

    $conn = connectDB();
    $data = getMonthsDataFromDB($conn, $username, $year);
    disconnectDB($conn);

    echo json_encode([
        "success" => true,
        "data" => $data
    ]);
}

function getYearData(): void
{
    if (!isLoggedIn()) {
        sendError("You are not logged in.", 401);
    }

    if (!getRequest() || postRequest()) {
        sendError("Method not allowed.", 405);
    }

    $username = validateInput(trim($_GET['username'] ?? null), null, null, "Invalid username.", 400);

    $conn = connectDB();
    $data = getYearsDataFromDB($conn, $username);
    disconnectDB($conn);

    echo json_encode([
        "success" => true,
        "data" => $data
    ]);
}

function getHyrjeDalje(): void
{
    if (!isLoggedIn()) {
        sendError("You are not logged in.", 401);
    }
    if (!getRequest() || postRequest()) {
        sendError("Method not allowed.", 405);
    }


    $conn = connectDB();

    $start = (int)validateInput(trim($_GET['start'] ?? 0), null, null, "Invalid start.", 400);
    $length = (int)validateInput(trim($_GET['length'] ?? 10), null, null, "Invalid length.", 400);
    $draw = (int)validateInput(trim($_GET['draw'] ?? 1), null, null, "Invalid draw.", 400);

    $search = trim($_GET['search']['value'] ?? '');

    $orderColumnIndex = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 0;
    $orderDirection = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'asc';

    $totalRecords = getTotalRecords($conn, 'hyrje_dalje_kryesore');

    $filteredRecords = getTotalRecords($conn, 'hyrje_dalje_kryesore', $search);

    $data = getHyrjeDaljeFromDB($conn, $start, $length, $search, $orderColumnIndex, $orderDirection);

    disconnectDB($conn);

    echo json_encode([
        "draw" => $draw,
        "recordsTotal" => $totalRecords['total'],
        "recordsFiltered" => $filteredRecords['total'],
        "data" => $data
    ]);
}

