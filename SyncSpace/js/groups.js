
/**
 * Author: Marcus Rotaru
 * Date: 2026-04-05
 * Description: Front-end module for managing user groups in SyncSpace.
 *              Fetches group data from the server and dynamically renders
 *              group information in the UI. Handles group creation by sending
 *              user input (group name, description, and member usernames)
 *              to the backend API and updates the interface accordingly.
 */

const EVENT_API = 'api/event_groups.php';
const USER_EVENTS_API = 'api/events.php';
const API = 'api/groups.php'
let currentGroupId = null;

// ─── Fetch groups ─────────────────────────────
async function loadGroups() {
    const res = await fetch(API);
    const groups = await res.json();

    const list = document.getElementById('group-list');
    list.innerHTML = '';

    if (!groups.length) {
        list.innerHTML = '<li>No groups yet</li>';
        return;
    }

    groups.forEach(g => {
        const li = document.createElement('li');

        li.innerHTML = `
        <strong>${g.group_name}</strong>
        <span>${g.description || 'No description'}</span>
        <span>Members: ${g.members.join(', ')}</span>`;
        li.classList.add('group-card');
        li.dataset.groupId = g.group_id;

        li.addEventListener('click', () => openGroupModal(g.group_id));

        list.appendChild(li);
    });
}

//Open card
async function openGroupModal(groupId) {
    currentGroupId = groupId;

    const modal = document.getElementById('group-modal');
    modal.classList.add('open');

    const res = await fetch(API);
    const groups = await res.json();

    const group = groups.find(g => g.group_id == groupId);

    if (!group) {
        alert('Group not found');
        closeGroupModal();
        return;
    }

    document.getElementById('group-title').textContent = group.group_name;
    document.getElementById('group-desc-text').textContent =
        group.description || 'No description';
    document.getElementById('group-members').textContent =
        group.members.join(', ');

    loadGroupEvents(groupId);
    loadUserEvents(groupId);
}

//close card
function closeGroupModal() {
    document.getElementById('group-modal').classList.remove('open');
}

//load group events
async function loadGroupEvents(groupId) {
    const res = await fetch(`${EVENT_API}?group_id=${groupId}`);
    const data = await res.json();

    const list = document.getElementById('group-events');
    list.innerHTML = `
        <li style="display:flex; justify-content:space-between; font-weight:bold; padding:6px 0;">
            <span>Event</span>
            <span>Shared</span>
        </li>
    `;

    if (!data.events || !data.events.length) {
        list.innerHTML = '<li>No group events</li>';
        return;
    }

    data.events.forEach(e => {
        const li = document.createElement('li');

        const title = e.anonymous ? '(Private Event)' : e.title;

        li.innerHTML = `
        <strong>${title}</strong>
        <span>${e.start_time}</span>
    `;

        list.appendChild(li);
    });
}

//load user events
async function loadUserEvents(groupId) {
    const resEvents = await fetch(USER_EVENTS_API);
    const userEvents = await resEvents.json();

    const resGroup = await fetch(`${EVENT_API}?group_id=${groupId}`);
    const groupData = await resGroup.json();

    const groupEventsMap = new Map(
        (groupData.events || []).map(ev => [ev.event_id, ev])
    );

    const list = document.getElementById('user-events');
    list.innerHTML = '';

    userEvents.forEach(e => {

        const groupEvent = groupEventsMap.get(e.event_id) || null;
        const isShared = groupEvent !== null;
        const isAnonymous = isShared && Boolean(groupEvent.anonymous);

        // aHIDE anonymous shared events (non-owners)
        if (isShared && isAnonymous) return;

        const li = document.createElement('li');
        const title = isAnonymous ? '(Private Event)' : e.title;

        li.innerHTML =
            `
            <label style="display:flex; justify-content:space-between; align-items:center; width:100%;">
            <span>
            <strong>${title}</strong><br>
            <small>${e.start_time}</small>
            </span>

            <input type="checkbox" ${isShared ? 'checked' : ''}>
            </label>
            `;

        const checkbox = li.querySelector('input');

        checkbox.addEventListener('change', async () => {
            try {
                let res;

                if (checkbox.checked) {
                    // SHARE
                    res = await fetch(EVENT_API, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: "share",
                            event_id: e.event_id,
                            group_id: groupId
                        })
                    });

                } else {
                    // UNSHARE
                    res = await fetch(EVENT_API, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: "unshare",
                            event_id: e.event_id,
                            group_id: groupId
                        })
                    });
                }

                const data = await res.json();

                if (!data.success) {
                    alert(data.message);
                    checkbox.checked = !checkbox.checked; // revert
                    return;
                }

                // Only UI update after success
                loadGroupEvents(currentGroupId);

            } catch (err) {
                console.error(err);
                alert("Network/API error");
                checkbox.checked = !checkbox.checked; // revert
            }
        });

        list.appendChild(li);
    });
}

// ─── Create group ─────────────────────────────
document.getElementById('create-group-form')
    .addEventListener('submit', async (e) => {
        e.preventDefault();

        const name = document.getElementById('group-name').value;
        const desc = document.getElementById('group-desc').value;

        const usernames = document.getElementById('group-users')
            .value.split(',')
            .map(u => u.trim())
            .filter(u => u.length);

        const res = await fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: "create",
                group_name: name,
                description: desc,
                usernames: usernames
            })
        });

        const data = await res.json();

        if (data.success) {
            alert('Group created!');
            loadGroups();
        } else {
            alert(data.message);
        }
    });

//leave group
async function leaveGroup() {
    if (!currentGroupId) return;

    // ask server who user is in this group
    const resCheck = await fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: "check_role",
            group_id: currentGroupId
        })
    });

    const check = await resCheck.json();

    if (!check.success) {
        alert(check.message);
        return;
    }

    // OWNER FLOW → destructive delete
    if (check.role === 'owner') {

        const confirmed = confirm(
            "You are the OWNER of this group.\n\n" +
            "Deleting it will:\n" +
            "• Remove ALL group members\n" +
            "• Unlink ALL group events\n" +
            "• Permanently delete the group\n\n" +
            "This action CANNOT be undone.\n\n" +
            "Click OK to permanently delete this group."
        );

        if (!confirmed) return;

        const res = await fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: "delete_group",
                group_id: currentGroupId
            })
        });

        const data = await res.json();

        if (data.success) {
            alert("Group permanently deleted.");
            closeGroupModal();
            loadGroups();
        } else {
            alert(data.message);
        }

        return;
    }

    // NORMAL MEMBER FLOW → just leave group
    const confirmed = confirm("Leave this group?");

    if (!confirmed) return;

    const res = await fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: "leave",
            group_id: currentGroupId
        })
    });

    const data = await res.json();

    if (data.success) {
        alert("You left the group");
        closeGroupModal();
        loadGroups();
    } else {
        alert(data.message);
    }
}

// Init
loadGroups();