<?php
/**
 * Date: 2026-04-05
 * Description: Authentication utility functions for SyncSpace. Provides session
 *              checks and login guards used at the top of every protected page.
 */
session_start();

/**
 * Redirects the user to the login page if they are not currently authenticated.
 * Call this at the top of any page that requires a logged-in session.
 *
 * @return void — exits the script after redirecting if the user is not logged in
 */
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Checks whether a user is currently logged in via PHP session.
 *
 * @return bool true if user_id is set in the session, false otherwise
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}
