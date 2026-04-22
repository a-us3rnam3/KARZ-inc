<?php

/**
 * Date: 2026-04-05
 * Description: Main groups page for SyncSpace. Requires an authenticated
 *              session, displays the logged-in user's profile information,
 *              provides a form for creating new groups, and shows the list
 *              of groups the user belongs to. Group data and creation logic
 *              are handled dynamically through js/groups.js and api/groups.php.
 */
require_once 'api/auth.php';
require_login();

$username = $_SESSION['username'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$initials = strtoupper(substr($username, 0, 1));
?>

<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <title>SyncSpace - Groups</title>
    <link rel="stylesheet" href="css/main.css">
</head>

<body>

    <!-- Header -->
    <header class="site-header">
        <div class="logo-area">
            <img src="images/logo.png" class="logo-img large-logo">
        </div>

        <nav class="main-nav">
            <a href="index.php">Dashboard</a>
            <a href="groups.php" class="active">Groups</a>

            <div class="profile-menu">
                <button class="profile-btn" id="profile-toggle" type="button">
                    <span class="profile-avatar"><?= htmlspecialchars($initials) ?></span>
                </button>

                <div class="profile-dropdown">
                    <a href="profile.php">Profile</a>
                    <a href="profile.php">Settings</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </nav>
    </header>

    <!-- Layout -->
    <main class="app-layout">

        <!-- Sidebar -->
        <aside class="sidebar">

            <section class="sidebar-card profile-card">
                <h2>User Panel</h2>
                <p><strong>Name:</strong> <?= htmlspecialchars($username) ?></p>
                <p><strong>Status:</strong> Logged In</p>
                <p><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>
            </section>

        </aside>

        <!-- Main Content -->
        <section class="main-content">

            <!-- Create Group -->
            <section class="panel" id="create-group-section">
                <h2>Create New Group</h2>

                <form id="create-group-form">
                    <div class="form-group">
                        <label>Group Name</label>
                        <input type="text" id="group-name" required>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea id="group-desc"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Add Users (comma separated usernames)</label>
                        <input type="text" id="group-users" placeholder="e.g. john, sarah">
                    </div>

                    <div class="modal-actions">
                        <button type="submit" class="primary-btn">Create Group</button>
                    </div>
                </form>
            </section>

            <!-- Groups List -->
            <section class="panel">
                <h2>Your Groups</h2>
                <ul id="group-list" class="event-list"></ul>
            </section>

        </section>

    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <p>&copy; 2026 KARZ inc. | SyncSpace Calendar App</p>
    </footer>

    <script src="js/groups.js"></script>

    <script>
        // smooth scroll to form
        function scrollToCreate() {
            document.getElementById('create-group-section')
                .scrollIntoView({
                    behavior: 'smooth'
                });
        }
    </script>

    <!-- Group Detail Modal -->
    <div id="group-modal" class="modal-overlay">
        <div class="modal-card">

            <div class="modal-header">
                <h2 id="group-title"></h2>
                <button class="modal-close-x" onclick="closeGroupModal()">×</button>
            </div>

            <div class="detail-body">
                <div class="detail-row">
                    <div class="detail-label">Description</div>
                    <div id="group-desc-text"></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Members</div>
                    <div id="group-members"></div>
                </div>
            </div>

            <hr class="profile-divider">

            <h3>Group Events</h3>
            <ul id="group-events" class="event-list"></ul>

            <h3>Your Events</h3>
            <ul id="user-events" class="event-list"></ul>

            <div class="modal-actions">
                <button class="danger-btn" onclick="leaveGroup()">Leave Group</button>
            </div>

        </div>
    </div>

</body>

</html>