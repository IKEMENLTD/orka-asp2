<?php
/**
 * Global Configuration
 * This file contains global constants and variables used throughout the application
 */

// User types
$NOT_LOGIN_USER_TYPE = 0;
$ADMIN_USER_TYPE = 1;
$AFFILIATE_USER_TYPE = 2;

// Default login user type
$loginUserType = isset($_SESSION['loginUserType']) ? $_SESSION['loginUserType'] : $NOT_LOGIN_USER_TYPE;
$loginUserRank = isset($_SESSION['loginUserRank']) ? $_SESSION['loginUserRank'] : 0;

// Form names
$LOGIN_KEY_FORM_NAME = 'login_id';
$LOGIN_PASSWD_FORM_NAME = 'login_pass';

// Global Master List
$gm = GMList::getList();

// Template path
$template_path = './template/';

// Record placeholder
$rec = [];
?>
