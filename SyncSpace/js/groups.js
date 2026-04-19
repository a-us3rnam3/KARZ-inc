const API = 'api/groups.php';

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

// Init
loadGroups();