<?php
/**
 * Date: 2026-04-21
 * Author: Taewoo Kim
 * Description: Handles user logout by destroying the current session and
 *              redirecting the user to the login page. Ensures that all
 *              session data is cleared.
 */

session_start();
session_unset();
session_destroy();

header('Location: login.php');
exit;