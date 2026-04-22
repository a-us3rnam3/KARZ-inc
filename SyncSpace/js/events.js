/**
 * Name: Erfan Zamani
 * Date: 2026-04-01
 * Description: Event management module for SyncSpace. Handles event creation,
 *              deletion, and calendar rendering across monthly, weekly, and daily
 *              views. Manages all modal dialogs and sidebar updates. All data is
 *              persisted via the PHP API to MySQL (zamane1_db.events table).
 */

'use strict';

const API = 'api/events.php';
// CURRENT_USER_ID is provided by index.php

// Erfan Zamani
// ─── API Calls ────────────────────────────────────────────────────────────────

/**
 * Fetches all events visible to the current user from the server.
 *
 * @returns {Promise<Array>} array of event objects, or an empty array on error
 */
async function fetchEvents() {
    try {
        // 1. personal events
        const res = await fetch(API);
        const personalEvents = res.ok ? await res.json() : [];

        // map by event_id
        const eventMap = new Map();

        personalEvents.forEach(ev => {
            eventMap.set(ev.event_id, {
                ...ev,
                group_ids: [] // IMPORTANT
            });
        });

        // 2. groups
        const gRes = await fetch('api/groups.php');
        const groups = await gRes.json();

        // 3. group events
        const groupEventPromises = groups.map(async g => {
            const r = await fetch(`api/event_groups.php?group_id=${g.group_id}`);
            const data = await r.json();
            if (!data.success) return [];

            return data.events.map(ev => ({
                ...ev,
                group_id: g.group_id
            }));
        });

        const groupEvents = (await Promise.all(groupEventPromises)).flat();

        // 4. merge into map
        groupEvents.forEach(ev => {
            if (eventMap.has(ev.event_id)) {
                // already exists → just add group
                eventMap.get(ev.event_id).group_ids.push(ev.group_id);
            } else {
                // event ONLY exists via group
                eventMap.set(ev.event_id, {
                    ...ev,
                    group_ids: [ev.group_id]
                });
            }
        });

        return Array.from(eventMap.values());

    } catch (err) {
        console.error(err);
        return [];
    }
}

/**
 * Sends a new event to the server to be saved in the database.
 *
 * @param {Object} payload - event data matching the events table columns
 * @returns {Promise<Object>} the saved event object returned by the server
 */
async function createEvent(payload) {
    const res = await fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
    const data = await res.json().catch(() => ({ error: 'Server error — check PHP logs' }));
    if (!res.ok) throw new Error(data.error || 'Failed to save event');
    return data;
}

/**
 * Deletes the event with the given ID from the database.
 *
 * @param {number} event_id - the ID of the event to delete
 * @returns {Promise<void>}
 */
async function removeEvent(event_id) {
    const res = await fetch(`${API}?action=delete&id=${event_id}`, { method: 'POST' });
    if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        throw new Error(data.error || 'Failed to delete event');
    }
}

/**
 * Fetches the current user's groups from the API and populates the Share
 * Event group dropdown. Shows a disabled placeholder if no groups exist.
 *
 * @returns {Promise<void>}
 */
async function loadGroupsIntoDropdown() {
    const select = document.getElementById('share-group');

    try {
        const res = await fetch('api/groups.php');
        const groups = await res.json();

        select.innerHTML = '';

        if (!groups.length) {
            const opt = document.createElement('option');
            opt.textContent = 'No groups available';
            opt.disabled = true;
            opt.selected = true;
            select.appendChild(opt);
            return;
        }

        groups.forEach(group => {
            const opt = document.createElement('option');
            opt.value = group.group_id;
            opt.textContent = group.group_name;
            select.appendChild(opt);
        });

    } catch (err) {
        select.innerHTML = '<option disabled>Error loading groups</option>';
    }
}

// ─── State ────────────────────────────────────────────────────────────────────

const MONTH_NAMES = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];
const DAY_SHORT = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const HOURS = Array.from({ length: 16 }, (_, i) => i + 7); // 7 AM – 10 PM

let state = {
    year: new Date().getFullYear(),
    month: new Date().getMonth(),
    view: 'monthly',
    selectedDate: todayStr(),
    events: [],
    groupVisibility: {},   // { group_id: true/false }
    showPersonal: true
};

// ─── Add-Event Modal ──────────────────────────────────────────────────────────

/**
 * Opens the Add Event modal and pre-fills the date field.
 *
 * @param {string} dateStr - ISO date string (YYYY-MM-DD) for the event date;
 *                           defaults to today if omitted
 * @returns {void}
 */
function openModal(dateStr) {
    const modal = document.getElementById('event-modal');
    document.getElementById('event-form').reset();
    document.getElementById('event-date').value = dateStr || todayStr();
    document.getElementById('event-start').value = '09:00';
    document.getElementById('event-end').value = '10:00';
    document.getElementById('repeat-end-field').style.display = 'none';
    setAllDayFields(false);
    modal.classList.add('open');
    document.getElementById('event-title').focus();
}

/**
 * Closes the Add Event modal.
 *
 * @returns {void}
 */
function closeModal() {
    document.getElementById('event-modal').classList.remove('open');
}

/**
 * Shows or hides the time input fields based on the All Day checkbox.
 *
 * @param {boolean} isAllDay - true to hide time fields, false to show them
 * @returns {void}
 */
function setAllDayFields(isAllDay) {
    document.getElementById('time-fields').style.display = isAllDay ? 'none' : 'grid';
}

// ─── Detail Modal ─────────────────────────────────────────────────────────────

/**
 * Opens the Event Detail modal and populates it with the given event's data.
 * Anonymous events owned by another user display redacted information.
 * The Delete button is only shown to the event's creator.
 *
 * @param {Object} ev - the event object from state.events
 * @returns {void}
 */
function openDetailModal(ev) {
    const modal = document.getElementById('detail-modal');
    const start = new Date(ev.start_time);
    const end = new Date(ev.end_time);
    const hidden = isHidden(ev);

    document.getElementById('detail-title').textContent = hidden ? '(Anonymous Event)' : ev.title;
    document.getElementById('detail-date').textContent = start.toLocaleDateString('en-US',
        { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    document.getElementById('detail-time').textContent = ev.is_all_day
        ? 'All Day'
        : `${fmt12(start)} – ${fmt12(end)}`;
    document.getElementById('detail-location').textContent = hidden ? '—' : (ev.location || '—');
    document.getElementById('detail-desc').textContent = hidden ? 'Details hidden (anonymous event)' : (ev.description || '—');
    document.getElementById('detail-anon').textContent = ev.anonymous ? 'Yes' : 'No';

    const badge = document.getElementById('detail-priority');
    badge.textContent = capitalize(ev.priority);
    badge.className = `detail-priority-badge priority-${ev.priority}`;

    // Only the creator can delete; hide the button for shared/group events
    const deleteBtn = document.getElementById('detail-delete-btn');
    deleteBtn.style.display = ev.created_by === CURRENT_USER_ID ? '' : 'none';

    deleteBtn.onclick = async () => {
        if (!confirm('Delete this event' + (ev.repeat_type && ev.repeat_type !== 'none' ? ' and all its repeats' : '') + '?')) return;
        try {
            await removeEvent(ev.event_id);
            state.events = state.events.filter(e => e.event_id !== ev.event_id);
            closeDetailModal();
            renderCalendar();
            updateUpcomingEvents();
        } catch (err) {
            alert('Could not delete event: ' + err.message);
        }
    };

    modal.classList.add('open');
}

/**
 * Closes the Event Detail modal.
 *
 * @returns {void}
 */
function closeDetailModal() {
    document.getElementById('detail-modal').classList.remove('open');
}

// Marcus Rotaru
// ─── Share Modal ────────────────────────────────────────────────────────────

/**
 * Opens the Share Event modal, loads available groups from the API, and
 * populates the event dropdown with events owned by the current user.
 *
 * @returns {Promise<void>}
 */
async function openShareModal() {
    const modal = document.getElementById('share-modal');
    const eventSelect = document.getElementById('share-event');

    // Load groups dynamically
    await loadGroupsIntoDropdown();

    // Existing event logic
    eventSelect.innerHTML = '';

    // Only use base events (deduplicate recurring instances by event_id)
    const seen = new Set();
    const userEvents = state.events.filter(ev => {
        if (ev.owner_user_id !== CURRENT_USER_ID || seen.has(ev.event_id)) return false;
        seen.add(ev.event_id);
        return true;
    });

    if (!userEvents.length) {
        eventSelect.innerHTML = '<option disabled selected>No events available</option>';
        modal.classList.add('open');
        return;
    }

    userEvents.forEach(ev => {
        const option = document.createElement('option');
        option.value = ev.event_id;
        option.textContent = `${ev.title} (${new Date(ev.start_time).toLocaleDateString()})`;
        eventSelect.appendChild(option);
    });

    eventSelect.selectedIndex = 0;
    modal.classList.add('open');
}

/**
 * Closes the Share Event modal.
 *
 * @returns {void}
 */
function closeShareModal() {
    document.getElementById('share-modal').classList.remove('open');
}
// Mazen Anklis
// ─── Free Time Modal ─────────────────────────────────────────────────────────

/**
 * Opens the Find Free Time modal, loads available groups, and resets the
 * date field to today.
 *
 * @returns {Promise<void>}
 */
async function openFreeTimeModal() {
    const modal = document.getElementById('free-time-modal');
    const select = document.getElementById('free-group');

    try {
        const res = await fetch('api/groups.php');
        const groups = await res.json();

        select.innerHTML = '';

        groups.forEach(g => {
            const opt = document.createElement('option');
            opt.value = g.group_id;
            opt.textContent = g.group_name;
            select.appendChild(opt);
        });

    } catch {
        select.innerHTML = '<option>Error loading groups</option>';
    }

    document.getElementById('free-date').value = todayStr();
    document.getElementById('free-time-results').innerHTML = '';

    modal.classList.add('open');
}

/**
 * Closes the Find Free Time modal.
 *
 * @returns {void}
 */
function closeFreeTimeModal() {
    document.getElementById('free-time-modal').classList.remove('open');
}

/**
 * Handles Find Free Time form submission. POSTs the selected group ID and
 * date to the backend, then renders the returned HTML into the results box.
 *
 * @param {Event} e - the DOM submit event from the free-time form
 * @returns {Promise<void>}
 */
async function handleFreeTimeSubmit(e) {
    e.preventDefault();

    const group_id = document.getElementById('free-group').value;
    const date = document.getElementById('free-date').value;

    const resBox = document.getElementById('free-time-results');
    resBox.innerHTML = 'Loading...';

    try {
        const res = await fetch('api/find_free_time.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `group_id=${encodeURIComponent(group_id)}&date=${encodeURIComponent(date)}`
        });

        const text = await res.text();

        if (!res.ok) {
            resBox.innerHTML = `Server error (${res.status})<br><pre>${text}</pre>`;
            return;
        }

        resBox.innerHTML = text;

    } catch (err) {
        resBox.innerHTML = 'Error finding free time: ' + err.message;
    }
}

// ─── Form Submission ──────────────────────────────────────────────────────────

/**
 * Handles Add Event form submission. Validates time order, builds the payload,
 * POSTs it to the API, expands any new recurring instances, and refreshes the
 * calendar on success.
 *
 * @param {Event} e - the DOM submit event from the event form
 * @returns {Promise<void>}
 */
async function handleFormSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const isAllDay = form['event-allday'].checked;
    const startT = form['event-start'].value;
    const endT = form['event-end'].value;
    const date = form['event-date'].value;
    const repeatType = form['event-repeat'].value;
    const repeatEndDate = form['event-repeat-end'].value || null;

    if (!isAllDay && startT >= endT) {
        alert('End time must be after start time.');
        return;
    }

    // Build payload with field names matching the EVENTS table
    const payload = {
        title: form['event-title'].value.trim(),
        description: form['event-desc'].value.trim(),
        start_time: isAllDay ? `${date} 00:00:00` : `${date} ${startT}:00`,
        end_time: isAllDay ? `${date} 23:59:59` : `${date} ${endT}:00`,
        created_by: CURRENT_USER_ID,
        owner_user_id: CURRENT_USER_ID,
        owner_group_id: null,
        location: form['event-location'].value.trim(),
        is_all_day: isAllDay,
        priority: form['event-priority'].value,
        anonymous: form['event-anon'].checked,
        repeat_type: repeatType,
        repeat_end_date: repeatEndDate
    };

    try {
        const saved = await createEvent(payload);
        // Expand the newly saved event (may produce recurring instances) and add to state
        const instances = expandRecurring([saved]);
        state.events.push(...instances);
        closeModal();
        renderCalendar();
        updateUpcomingEvents();
    } catch (err) {
        alert('Could not save event: ' + err.message);
    }
}

/**
 * Handles Share Event form submission. Reads the selected event, group, and
 * anonymous flag, then POSTs the share request to the backend.
 *
 * @param {Event} e - the DOM submit event from the share form
 * @returns {Promise<void>}
 */
async function handleShareSubmit(e) {
    e.preventDefault();

    const eventId = document.getElementById('share-event').value;
    const groupId = document.getElementById('share-group').value;
    const anonymous = document.getElementById('share-anon').checked;

    const payload = {
        event_id: eventId,
        group_id: groupId,
        anonymous: anonymous
    };

    // TODO: connect to backend endpoint
    await fetch('api/event_groups.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });

    closeShareModal();
    alert('Event shared successfully.');
}

// ─── Recurring Event Expansion ────────────────────────────────────────────────

/**
 * Expands each recurring event into individual instances up to 3 months ahead
 * (or the series end date, whichever comes first). Non-recurring events are
 * passed through unchanged.
 *
 * @param {Array} baseEvents - array of raw event objects from the API
 * @returns {Array} flat array containing the original events plus all instances
 */
function expandRecurring(baseEvents) {
    const expanded = [];
    const cutoff = new Date();
    cutoff.setMonth(cutoff.getMonth() + 3);

    for (const ev of baseEvents) {
        expanded.push(ev);
        if (!ev.repeat_type || ev.repeat_type === 'none') continue;

        const baseStart = new Date(ev.start_time);
        const duration = new Date(ev.end_time) - baseStart; // milliseconds
        const seriesEnd = ev.repeat_end_date ? new Date(ev.repeat_end_date) : cutoff;
        const limit = seriesEnd < cutoff ? seriesEnd : cutoff;

        const cur = new Date(baseStart);
        for (let i = 0; i < 500; i++) {
            if (ev.repeat_type === 'daily') cur.setDate(cur.getDate() + 1);
            else if (ev.repeat_type === 'weekly') cur.setDate(cur.getDate() + 7);
            else if (ev.repeat_type === 'monthly') cur.setMonth(cur.getMonth() + 1);

            if (cur > limit) break;

            expanded.push({
                ...ev,
                start_time: toDbTimestamp(new Date(cur)),
                end_time: toDbTimestamp(new Date(cur.getTime() + duration)),
            });
        }
    }
    return expanded;
}

/**
 * Formats a Date object as a MySQL-style timestamp string ("YYYY-MM-DD HH:MM:SS").
 *
 * @param {Date} date - the date to format
 * @returns {string} timestamp string in "YYYY-MM-DD HH:MM:SS" format
 */
function toDbTimestamp(date) {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ` +
        `${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;
}

// ─── Calendar Rendering ───────────────────────────────────────────────────────

/**
 * Renders the calendar by delegating to the appropriate view function based
 * on the current value of state.view ('monthly', 'weekly', or 'daily').
 *
 * @returns {void}
 */
function renderCalendar() {
    const grid = document.querySelector('.calendar-grid');
    grid.style.gridTemplateColumns = '';
    grid.style.gap = '';

    if (state.view === 'monthly') renderMonthly();
    else if (state.view === 'weekly') renderWeekly();
    else renderDaily();
}
/**
 * Refreshes the ui
 */
function refreshUI() {
    renderCalendar();
    updateUpcomingEvents();
}

/**
 * Applies filters onto the data
 * 
 */
async function initGroupFilters() {
    const container = document.getElementById('group-filters');

    try {
        const res = await fetch('api/groups.php');
        const groups = await res.json();

        container.innerHTML = '';

        // --- PERSONAL toggle ---
        const personalDiv = document.createElement('div');
        personalDiv.innerHTML = `
            <label>
                <input type="checkbox" checked id="toggle-personal">
                Personal
            </label>
        `;
        container.appendChild(personalDiv);

        document.getElementById('toggle-personal').addEventListener('change', (e) => {
            state.showPersonal = e.target.checked;
            refreshUI();
        });

        // --- GROUP toggles ---
        groups.forEach(g => {
            state.groupVisibility[String(g.group_id)] = true;

            const div = document.createElement('div');
            div.innerHTML = `
                <label>
                    <input type="checkbox" checked data-group="${g.group_id}">
                    ${g.group_name}
                </label>
            `;
            container.appendChild(div);
        });

        // attach listeners
        container.querySelectorAll('input[data-group]').forEach(cb => {
            cb.addEventListener('change', (e) => {
                const gid = String(e.target.dataset.group);
                state.groupVisibility[gid] = e.target.checked;
                refreshUI();
            });
        });

    } catch (err) {
        container.innerHTML = '<p>Error loading groups</p>';
    }
}

/**
 * applies filter logic
 * 
 * @returns 
 */
function getVisibleEvents() {
    return state.events.filter(ev => {

        const hasGroups = ev.group_ids && ev.group_ids.length > 0;

        // PERSONAL
        if (!hasGroups) {
            return state.showPersonal;
        }

        // GROUP → show if ANY enabled
        return ev.group_ids.some(gid =>
            state.groupVisibility[String(gid)] !== false
        );
    });
}

// --- Monthly -----------------------------------------------------------------

/**
 * Renders the monthly calendar grid. Builds day-name headers, empty offset
 * cells, and one cell per day with event pills for each event on that date.
 *
 * @returns {void}
 */
function renderMonthly() {
    const { year, month } = state;
    const events = getVisibleEvents();
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const today = new Date();

    document.querySelector('.section-header h2').textContent = 'Monthly Calendar View';
    document.querySelector('.calendar-controls span').textContent = `${MONTH_NAMES[month]} ${year}`;

    const grid = document.querySelector('.calendar-grid');
    grid.innerHTML = '';

    DAY_SHORT.forEach(name => {
        const cell = document.createElement('div');
        cell.className = 'day-name';
        cell.textContent = name;
        grid.appendChild(cell);
    });

    for (let i = 0; i < firstDay; i++) {
        const empty = document.createElement('div');
        empty.className = 'day empty';
        grid.appendChild(empty);
    }

    for (let d = 1; d <= daysInMonth; d++) {
        const isToday = today.getFullYear() === year &&
            today.getMonth() === month &&
            today.getDate() === d;

        const cell = document.createElement('div');
        cell.className = 'day' + (isToday ? ' active-day' : '');
        const dateStr = `${year}-${pad(month + 1)}-${pad(d)}`;
        cell.dataset.date = dateStr;

        const dateSpan = document.createElement('span');
        dateSpan.className = 'date';
        dateSpan.textContent = d;
        cell.appendChild(dateSpan);

        events
            .filter(ev => sameDay(ev.start_time, year, month, d))
            .forEach(ev => cell.appendChild(makePill(ev)));

        cell.addEventListener('click', () => openModal(dateStr));
        grid.appendChild(cell);
    }
}

// --- Weekly ------------------------------------------------------------------

/**
 * Renders the weekly calendar view. Shows a 7-column time grid from 7 AM to
 * 10 PM for the week containing the current month's reference date.
 *
 * @returns {void}
 */
function renderWeekly() {
    const { year, month } = state;
    const events = getVisibleEvents();
    const today = new Date();
    const anchor = new Date(year, month, 1);
    const ref = (today.getFullYear() === year && today.getMonth() === month) ? today : anchor;

    const sunday = new Date(ref);
    sunday.setDate(ref.getDate() - ref.getDay());
    const saturday = new Date(sunday);
    saturday.setDate(saturday.getDate() + 6);

    document.querySelector('.section-header h2').textContent = 'Weekly Calendar View';
    document.querySelector('.calendar-controls span').textContent =
        `${sunday.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} – ` +
        `${saturday.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;

    const grid = document.querySelector('.calendar-grid');
    grid.innerHTML = '';
    grid.style.gridTemplateColumns = '60px repeat(7, 1fr)';
    grid.style.gap = '4px';

    const corner = document.createElement('div');
    corner.className = 'day-name';
    grid.appendChild(corner);

    for (let i = 0; i < 7; i++) {
        const d = new Date(sunday);
        d.setDate(sunday.getDate() + i);
        const cell = document.createElement('div');
        cell.className = 'day-name week-day-header' + (datesEqual(d, today) ? ' today-header' : '');
        cell.innerHTML = `<span>${DAY_SHORT[i]}</span><span class="week-date-num">${d.getDate()}</span>`;
        grid.appendChild(cell);
    }

    HOURS.forEach(hour => {
        const label = document.createElement('div');
        label.className = 'time-label';
        label.textContent = fmtHour(hour);
        grid.appendChild(label);

        for (let i = 0; i < 7; i++) {
            const d = new Date(sunday);
            d.setDate(sunday.getDate() + i);
            const cell = document.createElement('div');
            cell.className = 'time-cell' + (datesEqual(d, today) ? ' today-col' : '');

            events
                .filter(ev => {
                    if (ev.is_all_day) return false;
                    const s = new Date(ev.start_time);
                    const e = new Date(ev.end_time);
                    // Show in every hour slot the event spans, not just the start hour
                    return datesEqual(s, d) &&
                        s.getHours() <= hour &&
                        (e.getHours() > hour || (e.getHours() === hour && e.getMinutes() > 0));
                })
                .forEach(ev => cell.appendChild(makePill(ev, true)));

            if (hour === 7) {
                events
                    .filter(ev => ev.is_all_day && datesEqual(new Date(ev.start_time), d))
                    .forEach(ev => {
                        const pill = makePill(ev, true);
                        pill.textContent += ' (All Day)';
                        cell.appendChild(pill);
                    });
            }

            const dateStr = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
            cell.addEventListener('click', () => openModal(dateStr));
            grid.appendChild(cell);
        }
    });
}

// --- Daily -------------------------------------------------------------------

/**
 * Renders the daily calendar view. Shows a 2-column time grid (Time | Day)
 * for state.selectedDate, from 7 AM to 10 PM plus an All Day row if needed.
 *
 * @returns {void}
 */
function renderDaily() {
    const events = getVisibleEvents();
    const target = new Date(state.selectedDate + 'T12:00:00');
    const y = target.getFullYear(), m = target.getMonth(), d = target.getDate();
    const now = new Date();

    document.querySelector('.section-header h2').textContent = 'Daily Calendar View';
    document.querySelector('.calendar-controls span').textContent =
        target.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });

    const grid = document.querySelector('.calendar-grid');
    grid.innerHTML = '';
    grid.style.gridTemplateColumns = '60px 1fr';
    grid.style.gap = '4px';

    const th1 = document.createElement('div');
    th1.className = 'day-name';
    th1.textContent = 'Time';
    grid.appendChild(th1);

    const th2 = document.createElement('div');
    th2.className = 'day-name';
    th2.textContent = target.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' });
    grid.appendChild(th2);

    const allDay = events.filter(ev => ev.is_all_day && sameDay(ev.start_time, y, m, d));
    if (allDay.length) {
        const lbl = document.createElement('div');
        lbl.className = 'time-label';
        lbl.textContent = 'All Day';
        grid.appendChild(lbl);

        const cell = document.createElement('div');
        cell.className = 'time-cell';
        allDay.forEach(ev => cell.appendChild(makePill(ev)));
        const dateStr = `${y}-${pad(m + 1)}-${pad(d)}`;
        cell.addEventListener('click', () => openModal(dateStr));
        grid.appendChild(cell);
    }

    HOURS.forEach(hour => {
        const lbl = document.createElement('div');
        lbl.className = 'time-label';
        lbl.textContent = fmtHour(hour);
        grid.appendChild(lbl);

        const isCurrent = now.getHours() === hour && sameDay(now.toISOString(), y, m, d);
        const cell = document.createElement('div');
        cell.className = 'time-cell' + (isCurrent ? ' current-hour' : '');

        events
            .filter(ev => {
                if (ev.is_all_day) return false;
                const s = new Date(ev.start_time);
                const e = new Date(ev.end_time);
                // Show in every hour slot the event spans, not just the start hour
                return sameDay(ev.start_time, y, m, d) &&
                    s.getHours() <= hour &&
                    (e.getHours() > hour || (e.getHours() === hour && e.getMinutes() > 0));
            })
            .forEach(ev => {
                const pill = makePill(ev);
                const s = new Date(ev.start_time);
                const e2 = new Date(ev.end_time);
                if (!isHidden(ev)) pill.textContent = `${ev.title} (${fmt12(s)}–${fmt12(e2)})`;
                cell.appendChild(pill);
            });

        const dateStr = `${y}-${pad(m + 1)}-${pad(d)}`;
        cell.addEventListener('click', () => openModal(dateStr));
        grid.appendChild(cell);
    });
}

// ─── Event Pill ───────────────────────────────────────────────────────────────

/**
 * Creates a colored event pill element for display inside a calendar cell.
 * Anonymous events owned by another user display as "(Private)". Recurring
 * events show a repeat indicator (↻) appended to the title.
 *
 * @param {Object}  ev    - the event object from state.events
 * @param {boolean} small - if true, renders the pill at a smaller font size
 * @returns {HTMLDivElement} the pill div element, ready to append to the DOM
 */
function makePill(ev, small = false) {
    const pill = document.createElement('div');
    pill.className = `event-pill ${ev.priority}`;
    if (small) pill.style.fontSize = '0.65rem';
    const recurringMark = (ev.repeat_type && ev.repeat_type !== 'none') ? ' ↻' : '';
    pill.textContent = isHidden(ev) ? '(Private)' : ev.title + recurringMark;
    pill.title = isHidden(ev) ? 'Anonymous — time blocked'
        : `${ev.title}${ev.location ? ' @ ' + ev.location : ''}`;
    pill.addEventListener('click', e => { e.stopPropagation(); openDetailModal(ev); });
    return pill;
}

// ─── Upcoming Events Panel ────────────────────────────────────────────────────

/**
 * Rebuilds the Upcoming Events list in the sidebar with the next 5 events
 * that have not yet ended, sorted by start time.
 *
 * @returns {void}
 */
function updateUpcomingEvents() {
    const now = new Date();
    const upcoming = state.events
        .filter(ev => new Date(ev.end_time) >= now)
        .sort((a, b) => new Date(a.start_time) - new Date(b.start_time))
        .slice(0, 5);

    const list = document.querySelector('.event-list');
    list.innerHTML = '';

    if (!upcoming.length) {
        list.innerHTML = '<li style="padding:12px;color:#888">No upcoming events. Click any day to add one.</li>';
        return;
    }

    upcoming.forEach(ev => {
        const li = document.createElement('li');
        li.className = `event-list-${ev.priority}`;
        const start = new Date(ev.start_time);
        const dateStr = start.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        const timeStr = ev.is_all_day ? 'All Day' : fmt12(start);
        li.innerHTML = `
            <strong>${isHidden(ev) ? '(Anonymous)' : ev.title}</strong>
            <span>${dateStr} – ${timeStr}${ev.location && !isHidden(ev) ? ' @ ' + ev.location : ''}</span>
        `;
        li.style.cursor = 'pointer';
        li.addEventListener('click', () => openDetailModal(ev));
        list.appendChild(li);
    });
}

// ─── View Switching & Navigation ─────────────────────────────────────────────

/**
 * Attaches click handlers to the Monthly, Weekly, and Daily view buttons.
 * Updates state.view and re-renders the calendar on each click.
 *
 * @returns {void}
 */
function initViewSwitcher() {
    const views = ['monthly', 'weekly', 'daily'];
    document.querySelectorAll('.view-btn').forEach((btn, i) => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            state.view = views[i];
            if (state.view === 'daily') state.selectedDate = todayStr();
            renderCalendar();
        });
    });
}

/**
 * Attaches click handlers to the previous and next navigation buttons.
 * Advances or retreats by one day (daily view) or one month (other views).
 *
 * @returns {void}
 */
function initNavigation() {
    const [prevBtn, nextBtn] = document.querySelectorAll('.calendar-controls button');

    prevBtn.addEventListener('click', () => {
        if (state.view === 'daily') {
            const d = new Date(state.selectedDate + 'T12:00:00');
            d.setDate(d.getDate() - 1);
            state.selectedDate = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
        } else {
            state.month--;
            if (state.month < 0) { state.month = 11; state.year--; }
        }
        renderCalendar();
    });

    nextBtn.addEventListener('click', () => {
        if (state.view === 'daily') {
            const d = new Date(state.selectedDate + 'T12:00:00');
            d.setDate(d.getDate() + 1);
            state.selectedDate = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
        } else {
            state.month++;
            if (state.month > 11) { state.month = 0; state.year++; }
        }
        renderCalendar();
    });
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Determines whether an event's details should be hidden from the current user.
 * Anonymous events are only hidden from others — the owner always sees full details.
 *
 * @param {Object} ev - the event object from state.events
 * @returns {boolean} true if the event is anonymous and owned by another user
 */
function isHidden(ev) {
    return ev.anonymous && ev.owner_user_id !== CURRENT_USER_ID;
}

/**
 * Checks whether an ISO datetime string falls on a specific calendar date.
 *
 * @param {string} isoStr - ISO datetime string (e.g. "2026-04-21 09:00:00")
 * @param {number} year   - the full year to compare (e.g. 2026)
 * @param {number} month  - the 0-based month index (0 = January)
 * @param {number} date   - the day of the month (1–31)
 * @returns {boolean} true if isoStr represents a date on the given year/month/date
 */
function sameDay(isoStr, year, month, date) {
    const d = new Date(isoStr);
    return d.getFullYear() === year && d.getMonth() === month && d.getDate() === date;
}

/**
 * Checks whether two Date objects represent the same calendar day.
 *
 * @param {Date} a - first date
 * @param {Date} b - second date
 * @returns {boolean} true if a and b share the same year, month, and day
 */
function datesEqual(a, b) {
    return a.getFullYear() === b.getFullYear() &&
        a.getMonth() === b.getMonth() &&
        a.getDate() === b.getDate();
}

/**
 * Zero-pads a number to at least 2 digits.
 *
 * @param {number} n - the number to pad
 * @returns {string} the number as a string, left-padded with '0' if needed
 */
function pad(n) { return String(n).padStart(2, '0'); }

/**
 * Returns today's date formatted as a YYYY-MM-DD string.
 *
 * @returns {string} today's date in ISO date format (e.g. "2026-04-21")
 */
function todayStr() {
    const t = new Date();
    return `${t.getFullYear()}-${pad(t.getMonth() + 1)}-${pad(t.getDate())}`;
}

/**
 * Formats a Date object as a 12-hour time string (e.g. "9:30 AM").
 *
 * @param {Date} date - the date whose time will be formatted
 * @returns {string} 12-hour time string with AM/PM suffix
 */
function fmt12(date) {
    let h = date.getHours();
    const m = pad(date.getMinutes());
    const ap = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return `${h}:${m} ${ap}`;
}

/**
 * Formats an hour integer as a 12-hour clock label (e.g. 13 → "1 PM").
 *
 * @param {number} h - hour in 24-hour format (0–23)
 * @returns {string} 12-hour label with AM/PM suffix
 */
function fmtHour(h) {
    const ap = h >= 12 ? 'PM' : 'AM';
    return `${h % 12 || 12} ${ap}`;
}

/**
 * Capitalizes the first character of a string.
 *
 * @param {string} s - the input string
 * @returns {string} the string with its first character uppercased
 */
function capitalize(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

// ─── Init ─────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', async () => {

    // Load events and expand any recurring series
    state.events = expandRecurring(await fetchEvents());

    // Add-event form
    document.getElementById('event-form').addEventListener('submit', handleFormSubmit);
    document.querySelectorAll('.modal-dismiss').forEach(btn =>
        btn.addEventListener('click', closeModal));
    document.getElementById('event-modal').addEventListener('click', e => {
        if (e.target === e.currentTarget) closeModal();
    });
    document.getElementById('event-allday').addEventListener('change', e =>
        setAllDayFields(e.target.checked));

    // Show/hide the "Repeat Until" date field based on repeat type selection
    document.getElementById('event-repeat').addEventListener('change', e => {
        document.getElementById('repeat-end-field').style.display =
            e.target.value === 'none' ? 'none' : '';
    });

    // Detail modal
    document.querySelectorAll('.detail-dismiss').forEach(btn =>
        btn.addEventListener('click', closeDetailModal));
    document.getElementById('detail-modal').addEventListener('click', e => {
        if (e.target === e.currentTarget) closeDetailModal();
    });

    // Open Share Modal
    document.querySelectorAll('.open-share-modal').forEach(btn =>
        btn.addEventListener('click', openShareModal)
    );

    // Close Share Modal
    document.querySelectorAll('.share-dismiss').forEach(btn =>
        btn.addEventListener('click', closeShareModal)
    );
    // Open Free Time Modal
    document.getElementById('open-free-time')
        .addEventListener('click', e => {
            e.preventDefault();
            openFreeTimeModal();
        });

    // Close Free Time Modal
    document.querySelectorAll('.free-dismiss')
        .forEach(btn => btn.addEventListener('click', closeFreeTimeModal));

    // Click outside closes
    document.getElementById('free-time-modal')
        .addEventListener('click', e => {
            if (e.target === e.currentTarget) closeFreeTimeModal();
        });

    // Submit form
    document.getElementById('free-time-form')
        .addEventListener('submit', handleFreeTimeSubmit);

    // Click outside to close
    document.getElementById('share-modal').addEventListener('click', e => {
        if (e.target === e.currentTarget) closeShareModal();
    });

    // Form submit
    document.getElementById('share-form')
        .addEventListener('submit', handleShareSubmit);

    // Sidebar buttons
    document.querySelectorAll('.open-add-modal').forEach(btn =>
        btn.addEventListener('click', () => openModal()));

    initViewSwitcher();
    initNavigation();

    await initGroupFilters();

    renderCalendar();
    updateUpcomingEvents();

});
