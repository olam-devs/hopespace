<?php
require_once __DIR__ . '/../../app/config/init.php';
require_once APP_PATH . '/middleware/Auth.php';
Auth::logout();
redirect(url('author/login.php'));
