<?php
require_once '../config.php';
requireLogin();

session_destroy();
header('Location: login.php');
exit;
