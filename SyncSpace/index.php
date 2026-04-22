<?php

/**
 * Date: 2026-03-31
 * Description: Main dashboard page for SyncSpace. Requires an authenticated
 *              session, then renders the calendar, sidebar, upcoming events panel,
 *              and the Add Event, Event Detail, and Share Event modal dialogs.
 *              Calendar data is loaded dynamically by js/events.js.
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
    <title>SyncSpace - Calendar App</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="shortcut icon" href="images/croppedLogo.png">
</head>

<body>

    <!-- Header -->
    <header class="site-header">
        <div class="logo-area">
            <img src="images/logo.png" alt="SyncSpace Logo" class="logo-img large-logo">
        </div>

        <nav class="main-nav">
            <a href="#" class="active">Dashboard</a>
            <a href="groups.php">Groups</a>

            <!-- Profile Button -->
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

    <!-- Main Page Layout -->
    <main class="app-layout">

        <!-- Sidebar -->
        <aside class="sidebar">
            <section class="sidebar-card profile-card">
                <h2>User Panel</h2>
                <p><strong>Name:</strong> <?= htmlspecialchars($username) ?></p>
                <p><strong>Status:</strong> Logged In</p>
                <p><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>
                <button class="primary-btn open-add-modal">+ Add Event</button>
                <button class="primary-btn open-share-modal">↥ Share Event</button>
            </section>

            <section class="sidebar-card">
                <h2>Quick Actions</h2>

                <button class="primary-btn full-btn open-add-modal">+ Create Personal Event</button>
                <button class="primary-btn full-btn open-add-modal">+ Create Group Event</button>

                <ul class="quick-links">
                    <li><a href="#">Compare Schedules</a></li>
                    <li><a href="#" id="open-free-time">Find Free Time</a></li>
                </ul>
            </section>
            <section class="sidebar-card">
                <h2>Filter Events</h2>

                <div id="group-filters">
                </div>
            </section>
        </aside>

        <!-- Main Content -->
        <section class="main-content">
            <!-- Different View Buttons -->
            <div class="view-switcher">
                <button class="view-btn active">Monthly</button>
                <button class="view-btn">Weekly</button>
                <button class="view-btn">Daily</button>
            </div>
            <!-- Calendar Section -->
            <section class="calendar-section">
                <div class="section-header">
                    <h2>Monthly Calendar View</h2>
                    <div class="calendar-controls">
                        <button>&lt;</button>
                        <span>April 2026</span>
                        <button>&gt;</button>
                    </div>
                </div>

                <!-- Populated dynamically by js/events.js -->
                <div class="calendar-grid"></div>
            </section>

            <!-- Bottom Panels -->
            <section class="bottom-grid">
                <article class="panel">
                    <h2>Upcoming Events</h2>
                    <!-- Populated dynamically by js/events.js -->
                    <ul class="event-list"></ul>
                </article>

                <article class="panel">
                    <h2>Shared Availability</h2>
                    <p>Best common free time:</p>
                    <div class="availability-box">Wednesday, 3:00 PM - 5:00 PM</div>
                </article>
            </section>
            <section class="priority-legend panel">
                <div class="legend-header">
                    <h2>Event Priority Legend</h2>
                </div>

                <div class="legend-items">
                    <div class="legend-item">
                        <span class="legend-color high-color"></span>
                        <span>High Priority</span>
                    </div>

                    <div class="legend-item">
                        <span class="legend-color medium-color"></span>
                        <span>Medium Priority</span>
                    </div>

                    <div class="legend-item">
                        <span class="legend-color low-color"></span>
                        <span>Low Priority</span>
                    </div>
                </div>
            </section>

        </section>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <p>&copy; 2026 KARZ inc. | SyncSpace Calendar App</p>
    </footer>

    <!-- ═══════════════════════════════════════════════════════════════════════
         Add Event Modal
         ═══════════════════════════════════════════════════════════════════════ -->
    <div id="event-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-heading">
        <div class="modal-card">

            <div class="modal-header">
                <h2 id="modal-heading">Add Event</h2>
                <button class="modal-close-x modal-dismiss" type="button" aria-label="Close">&times;</button>
            </div>

            <form id="event-form" novalidate>

                <div class="form-group">
                    <label for="event-title">Title <span class="required">*</span></label>
                    <input type="text" id="event-title" name="event-title" placeholder="Event title" required
                        maxlength="100">
                </div>

                <div class="form-group">
                    <label for="event-date">Date <span class="required">*</span></label>
                    <input type="date" id="event-date" name="event-date" required>
                </div>

                <div class="form-row">
                    <label class="checkbox-label">
                        <input type="checkbox" id="event-allday" name="event-allday">
                        All Day
                    </label>
                </div>

                <!-- Hidden when All Day is checked -->
                <div id="time-fields" class="form-grid-2">
                    <div class="form-group">
                        <label for="event-start">Start Time</label>
                        <input type="time" id="event-start" name="event-start">
                    </div>
                    <div class="form-group">
                        <label for="event-end">End Time</label>
                        <input type="time" id="event-end" name="event-end">
                    </div>
                </div>

                <div class="form-group">
                    <label for="event-location">Location</label>
                    <input type="text" id="event-location" name="event-location" placeholder="Optional location"
                        maxlength="150">
                </div>

                <div class="form-group">
                    <label for="event-desc">Description</label>
                    <textarea id="event-desc" name="event-desc" rows="3" placeholder="Optional description"></textarea>
                </div>

                <div class="form-group">
                    <label for="event-priority">Priority</label>
                    <select id="event-priority" name="event-priority">
                        <option value="high">High</option>
                        <option value="medium" selected>Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="event-owner">Event Type</label>
                    <select id="event-owner" name="event-owner">
                        <option value="personal">Personal</option>
                        <option value="group">Group</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="event-repeat">Repeat</label>
                    <select id="event-repeat" name="event-repeat">
                        <option value="none">Does not repeat</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>

                <div class="form-group" id="repeat-end-field" style="display:none">
                    <label for="event-repeat-end">Repeat Until</label>
                    <input type="date" id="event-repeat-end" name="event-repeat-end">
                </div>

                <div class="form-row">
                    <label class="checkbox-label">
                        <input type="checkbox" id="event-anon" name="event-anon">
                        Anonymous &mdash; hide all details from others; only time &amp; priority visible
                    </label>
                </div>

                <div class="modal-actions">
                    <button type="button" class="secondary-btn modal-dismiss">Cancel</button>
                    <button type="submit" class="primary-btn">Save Event</button>
                </div>

            </form>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         Event Detail Modal
         ═══════════════════════════════════════════════════════════════════════ -->
    <div id="detail-modal" class="modal-overlay" role="dialog" aria-modal="true">
        <div class="modal-card">

            <div class="modal-header">
                <h2 id="detail-title"></h2>
                <button class="modal-close-x detail-dismiss" type="button" aria-label="Close">&times;</button>
            </div>

            <div class="detail-body">
                <div class="detail-row">
                    <span class="detail-label">Date</span>
                    <span id="detail-date"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Time</span>
                    <span id="detail-time"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Location</span>
                    <span id="detail-location"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Description</span>
                    <span id="detail-desc"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Priority</span>
                    <span id="detail-priority" class="detail-priority-badge"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Anonymous</span>
                    <span id="detail-anon"></span>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="danger-btn" id="detail-delete-btn">Delete Event</button>
                <button type="button" class="primary-btn detail-dismiss">Close</button>
            </div>

        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
     Share Event Modal
     ═══════════════════════════════════════════════════════════════════════ -->
    <div id="share-modal" class="modal-overlay" role="dialog" aria-modal="true">
        <div class="modal-card">

            <div class="modal-header">
                <h2>Share Event</h2>
                <button class="modal-close-x share-dismiss" type="button">&times;</button>
            </div>

            <form id="share-form">

                <!-- Event Dropdown -->
                <div class="form-group">
                    <label for="share-event">Select Event</label>
                    <select id="share-event" required></select>
                </div>

                <!-- Group Dropdown -->
                <div class="form-group">
                    <label for="share-group">Select Group</label>
                    <select id="share-group" required></select>
                </div>

                <!-- Anonymous Toggle -->
                <div class="form-row">
                    <label class="checkbox-label">
                        <input type="checkbox" id="share-anon">
                        Share as anonymous
                    </label>
                </div>

                <div class="modal-actions">
                    <button type="button" class="secondary-btn share-dismiss">Cancel</button>
                    <button type="submit" class="primary-btn">Share Event</button>
                </div>

            </form>
        </div>
    </div>
    <!-- ═══════════════════════════════════════════════════════════════════════
     Find Free Time Modal
     ═══════════════════════════════════════════════════════════════════════ -->
    <div id="free-time-modal" class="modal-overlay" role="dialog" aria-modal="true">
        <div class="modal-card">

            <div class="modal-header">
                <h2>Find Free Time</h2>
                <button class="modal-close-x free-dismiss" type="button">&times;</button>
            </div>

            <form id="free-time-form">

                <div class="form-group">
                    <label for="free-group">Select Group</label>
                    <select id="free-group" required></select>
                </div>

                <div class="form-group">
                    <label for="free-date">Select Date</label>
                    <input type="date" id="free-date" required>
                </div>

                <div class="modal-actions">
                    <button type="button" class="secondary-btn free-dismiss">Cancel</button>
                    <button type="submit" class="primary-btn">Find Free Time</button>
                </div>

            </form>

            <div id="free-time-results" style="margin-top:15px;"></div>

        </div>
    </div>

    <script>
        const CURRENT_USER_ID = <?= (int)$_SESSION['user_id'] ?>;
        const CURRENT_USERNAME = <?= json_encode($_SESSION['username']) ?>;

        document.addEventListener('DOMContentLoaded', () => {
            const profileMenu = document.querySelector('.profile-menu');
            const profileToggle = document.getElementById('profile-toggle');

            profileToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                profileMenu.classList.toggle('open');
            });

            document.addEventListener('click', (e) => {
                if (!profileMenu.contains(e.target)) {
                    profileMenu.classList.remove('open');
                }
            });
        });
    </script>
    <script src="js/events.js"></script>

</body>

</html>