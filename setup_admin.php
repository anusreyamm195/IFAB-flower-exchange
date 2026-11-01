<?php
require_once __DIR__.'/config/config.php';
$hash=password_hash('ChangeMe@123',PASSWORD_DEFAULT);
db()->prepare("UPDATE admins SET password_hash=? WHERE email='admin@ifab.local'")->execute([$hash]);
echo 'Admin password configured. Delete setup_admin.php now. Login: admin@ifab.local / ChangeMe@123';
