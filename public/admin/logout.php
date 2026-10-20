<?php
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
logout_admin();
header('Location: /admin/login');
exit;
