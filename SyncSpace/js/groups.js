
/**
 * groups.js
 * Date: 2026-04-05
 * Description: Front-end module for managing user groups in SyncSpace.
 *              Fetches group data from the server and dynamically renders
 *              group information in the UI. Handles group creation by sending
 *              user input (group name, description, and member usernames)
 *              to the backend API and updates the interface accordingly.
 */

const EVENT_API = 'api/event_groups.php';
const USER_EVENTS_API = 'api/events.php';
let currentGroupId = null;

// ─── Fetch groups ─────────────────────────────
async function loadGroups() {
    const res = await fetch(USER_EVENTS_API);
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
    list.innerHTML = '';

    if (!data.events || !data.events.length) {
        list.innerHTML = '<li>No group events</li>';
        return;
    }

    data.events.forEach(e => {
        const li = document.createElement('li');

        li.innerHTML = `
            <strong>${e.title}</strong>
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

    const groupEventIds = new Set(
        (groupData.events || []).map(e => e.event_id)
    );

    const list = document.getElementById('user-events');
    list.innerHTML = '';

    userEvents.forEach(e => {
        const isShared = groupEventIds.has(e.event_id);

        const li = document.createElement('li');

        li.innerHTML = `
            <strong>${e.title}</strong>
            <span>${isShared ? '✔ Shared' : 'Not shared'}</span>
        `;

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

        const res = await fetch(USER_EVENTS_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
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
    if (!confirm('Are you sure you want to leave this group?')) return;

    const res = await fetch(USER_EVENTS_API, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ group_id: currentGroupId })
    });

    const data = await res.json();

    if (data.success) {
        alert('You left the group');
        closeGroupModal();
        loadGroups();
    } else {
        alert(data.message);
    }
}

// Init
loadGroups();