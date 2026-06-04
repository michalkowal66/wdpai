<?php

// Check if running from CLI
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

require_once __DIR__ . '/src/repositories/UsersRepository.php';

// Get arguments
$email = $argv[1] ?? null;
$password = $argv[2] ?? null;
$fullName = $argv[3] ?? 'System Administrator';

if (!$email || !$password) {
    echo "Usage: php create-admin.php <email> <password> [\"Full Name\"]\n";
    echo "Example: php create-admin.php admin@hotdesk.io SecretPass123 \"Super Admin\"\n";
    exit(1);
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Error: Invalid email format.\n";
    exit(1);
}

// Validate password length
if (strlen($password) < 8) {
    echo "Error: Password must be at least 8 characters long.\n";
    exit(1);
}

echo "Initializing Admin Account...\n";

try {
    $usersRepo = UsersRepository::getInstance();
    
    // Check if user already exists
    $existingUser = $usersRepo->getUserByEmail($email);
    if ($existingUser) {
        echo "Error: User with email '$email' already exists.\n";
        exit(1);
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Insert directly using PDO to bypass default User values (EMPLOYEE/is_active=false)
    $db = (new Database())->connect();
    $query = $db->prepare("
        INSERT INTO users (full_name, email, password, role, is_active, job_title) 
        VALUES (:full_name, :email, :password, 'ADMIN', true, 'Administrator')
    ");
    
    $query->execute([
        ':full_name' => $fullName,
        ':email' => $email,
        ':password' => $hashedPassword
    ]);

    echo "Success! Admin account created.\n";
    echo "Email: $email\n";
    echo "You can now log in at http://localhost:8080/login\n";

} catch (Exception $e) {
    echo "Fatal Error: Could not create admin account.\n";
    echo $e->getMessage() . "\n";
    exit(1);
}
