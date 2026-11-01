<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__.'/config/config.php';
fwrite(STDOUT, "Choose a new admin password (at least 12 characters): ");
$password = rtrim((string)fgets(STDIN), "\r\n");
if (strlen($password) < 12) {
    fwrite(STDERR, "The admin password must be at least 12 characters.\n");
    exit(1);
}

$statement = db()->prepare("UPDATE admins SET password_hash=? WHERE email='admin@ifab.local'");
$statement->execute([password_hash($password, PASSWORD_DEFAULT)]);
if ($statement->rowCount() !== 1) {
    fwrite(STDERR, "The default admin account was not found. Import database/ifab.sql first.\n");
    exit(1);
}

fwrite(STDOUT, "Admin password configured. Log in as admin@ifab.local.\n");
