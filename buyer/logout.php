
<?php
require_once __DIR__ . '/../config/config.php';

unset(
    $_SESSION['user_id'],
    $_SESSION['user_name'],
    $_SESSION['user_role'],
    $_SESSION['user_email']
);

session_regenerate_id(true);

header('Location: ' . url('login.php'));
exit;
