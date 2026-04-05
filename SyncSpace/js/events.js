// SyncSpace — Event Adding Module
// Erfan Zamani
// Handles event creation, deletion, and calendar rendering.
// All data is persisted via PHP API → MySQL (zamane1_db.events table).

'use strict';

const API = 'api/events.php';
const CURRENT_USER_ID = 1; // Placeholder until Taewoo's auth module is integrated

// ─── API Calls ────────────────────────────────────────────────────────────────

async function fetchEvents() {
    const res = await fetch(API);
    return res.json();
}

async function createEvent(payload) {
    const res = await fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
    return res.json();
}

async function removeEvent(event_id) {
    await fetch(`${API}?id=${event_id}`, { method: 'DELETE' });
}

// ─── State ────────────────────────────────────────────────────────────────────

const MONTH_NAMES = [
    'January','February','March','April','May','June',
    'July','August','September','October','November','December'
];
const DAY_SHORT = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
const HOURS = Array.from({ length: 16 }, (_, i) => i + 7); // 7 AM – 10 PM

let state = {
    year:         new Date().getFullYear(),
    month:        new Date().getMonth(),
    view:         'monthly',
    selectedDate: todayStr(),
    events:       []   // in-memory cache of the DB rows
};

// ─── Add-Event Modal ──────────────────────────────────────────────────────────

function openModal(dateStr) {
    const modal = document.getElementById('event-modal');
    document.getElementById('event-form').reset();
    document.getElementById('event-date').value  = dateStr || todayStr();
    document.getElementById('event-start').value = '09:00';
    document.getElementById('event-end').value   = '10:00';
    setAllDayFields(false);
    modal.classList.add('open');
    document.getElementById('event-title').focus();
}

function closeModal() {
    document.getElementById('event-modal').classList.remove('open');
}

function setAllDayFields(isAllDay) {
    document.getElementById('time-fields').style.display = isAllDay ? 'none' : 'grid';
}

// ─── Detail Modal ─────────────────────────────────────────────────────────────

function openDetailModal(ev) {
    const modal  = document.getElementById('detail-modal');
    const start  = new Date(ev.start_time);
    const end    = new Date(ev.end_time);
    const hidden = isHidden(ev);

    document.getElementById('detail-title').textContent    = hidden ? '(Anonymous Event)' : ev.title;
    document.getElementById('detail-date').textContent     = start.toLocaleDateString('en-US',
        { weekday:'long', year:'numeric', month:'long', day:'numeric' });
    document.getElementById('detail-time').textContent     = ev.is_all_day
        ? 'All Day'
        : `${fmt12(start)} – ${fmt12(end)}`;
    document.getElementById('detail-location').textContent = hidden ? '—' : (ev.location || '—');
    document.getElementById('detail-desc').textContent     = hidden ? 'Details hidden (anonymous event)' : (ev.description || '—');
    document.getElementById('detail-anon').textContent     = ev.anonymous ? 'Yes' : 'No';

    const badge      = document.getElementById('detail-priority');
    badge.textContent = capitalize(ev.priority);
    badge.className   = `detail-priority-badge priority-${ev.priority}`;

    document.getElementById('detail-delete-btn').onclick = async () => {
        await removeEvent(ev.event_id);
        state.events = state.events.filter(e => e.event_id !== ev.event_id);
        closeDetailModal();
        renderCalendar();
        updateUpcomingEvents();
    };

    modal.classList.add('open');
}

function closeDetailModal() {
    document.getElementById('detail-modal').classList.remove('open');
}

// ─── Form Submission ──────────────────────────────────────────────────────────

async function handleFormSubmit(e) {
    e.preventDefault();
    const form     = e.target;
    const isAllDay = form['event-allday'].checked;
    const startT   = form['event-start'].value;
    const endT     = form['event-end'].value;
    const date     = form['event-date'].value;

    if (!isAllDay && startT >= endT) {
        alert('End time must be after start time.');
        return;
    }

    // Build payload with field names matching the EVENTS table
    const payload = {
        title:          form['event-title'].value.trim(),
        description:    form['event-desc'].value.trim(),
        start_time:     isAllDay ? `${date} 00:00:00` : `${date} ${startT}:00`,
        end_time:       isAllDay ? `${date} 23:59:59` : `${date} ${endT}:00`,
        created_by:     CURRENT_USER_ID,
        owner_user_id:  form['event-owner'].value === 'personal' ? CURRENT_USER_ID : null,
        owner_group_id: form['event-owner'].value === 'group'    ? 1               : null,
        location:       form['event-location'].value.trim(),
        is_all_day:     isAllDay,
        priority:       form['event-priority'].value,
        anonymous:      form['event-anon'].checked
    };

    const saved = await createEvent(payload);
    state.events.push(saved);

    closeModal();
    renderCalendar();
    updateUpcomingEvents();
}

// ─── Calendar Rendering ───────────────────────────────────────────────────────

function renderCalendar() {
    const grid = document.querySelector('.calendar-grid');
    grid.style.gridTemplateColumns = '';
    grid.style.gap = '';

    if (state.view === 'monthly')     renderMonthly();
    else if (state.view === 'weekly') renderWeekly();
    else                              renderDaily();
}

// --- Monthly -----------------------------------------------------------------

function renderMonthly() {
    const { year, month, events } = state;
    const firstDay    = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const today       = new Date();

    document.querySelector('.section-header h2').textContent      = 'Monthly Calendar View';
    document.querySelector('.calendar-controls span').textContent = `${MONTH_NAMES[month]} ${year}`;

    const grid = document.querySelector('.calendar-grid');
    grid.innerHTML = '';

    DAY_SHORT.forEach(name => {
        const cell = document.createElement('div');
        cell.className   = 'day-name';
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
                        today.getMonth()    === month &&
                        today.getDate()     === d;

        const cell       = document.createElement('div');
        cell.className   = 'day' + (isToday ? ' active-day' : '');
        const dateStr    = `${year}-${pad(month + 1)}-${pad(d)}`;
        cell.dataset.date = dateStr;

        const dateSpan       = document.createElement('span');
        dateSpan.className   = 'date';
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

function renderWeekly() {
    const { year, month, events } = state;
    const today  = new Date();
    const anchor = new Date(year, month, 1);
    const ref    = (today.getFullYear() === year && today.getMonth() === month) ? today : anchor;

    const sunday = new Date(ref);
    sunday.setDate(ref.getDate() - ref.getDay());
    const saturday = new Date(sunday);
    saturday.setDate(saturday.getDate() + 6);

    document.querySelector('.section-header h2').textContent      = 'Weekly Calendar View';
    document.querySelector('.calendar-controls span').textContent =
        `${sunday.toLocaleDateString('en-US',{month:'short',day:'numeric'})} – ` +
        `${saturday.toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'})}`;

    const grid = document.querySelector('.calendar-grid');
    grid.innerHTML = '';
    grid.style.gridTemplateColumns = '60px repeat(7, 1fr)';
    grid.style.gap = '4px';

    const corner = document.createElement('div');
    corner.className = 'day-name';
    grid.appendChild(corner);

    for (let i = 0; i < 7; i++) {
        const d    = new Date(sunday);
        d.setDate(sunday.getDate() + i);
        const cell = document.createElement('div');
        cell.className = 'day-name week-day-header' + (datesEqual(d, today) ? ' today-header' : '');
        cell.innerHTML = `<span>${DAY_SHORT[i]}</span><span class="week-date-num">${d.getDate()}</span>`;
        grid.appendChild(cell);
    }

    HOURS.forEach(hour => {
        const label       = document.createElement('div');
        label.className   = 'time-label';
        label.textContent = fmtHour(hour);
        grid.appendChild(label);

        for (let i = 0; i < 7; i++) {
            const d    = new Date(sunday);
            d.setDate(sunday.getDate() + i);
            const cell = document.createElement('div');
            cell.className = 'time-cell' + (datesEqual(d, today) ? ' today-col' : '');

            events
                .filter(ev => !ev.is_all_day &&
                              datesEqual(new Date(ev.start_time), d) &&
                              new Date(ev.start_time).getHours() === hour)
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

            const dateStr = `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
            cell.addEventListener('click', () => openModal(dateStr));
            grid.appendChild(cell);
        }
    });
}

// --- Daily -------------------------------------------------------------------

function renderDaily() {
    const { events } = state;
    const target = new Date(state.selectedDate + 'T12:00:00');
    const y = target.getFullYear(), m = target.getMonth(), d = target.getDate();
    const now = new Date();

    document.querySelector('.section-header h2').textContent      = 'Daily Calendar View';
    document.querySelector('.calendar-controls span').textContent =
        target.toLocaleDateString('en-US', { weekday:'long', month:'long', day:'numeric', year:'numeric' });

    const grid = document.querySelector('.calendar-grid');
    grid.innerHTML = '';
    grid.style.gridTemplateColumns = '60px 1fr';
    grid.style.gap = '4px';

    const th1 = document.createElement('div');
    th1.className = 'day-name';
    th1.textContent = 'Time';
    grid.appendChild(th1);

    const th2 = document.createElement('div');
    th2.className   = 'day-name';
    th2.textContent = target.toLocaleDateString('en-US', { weekday:'long', month:'short', day:'numeric' });
    grid.appendChild(th2);

    const allDay = events.filter(ev => ev.is_all_day && sameDay(ev.start_time, y, m, d));
    if (allDay.length) {
        const lbl       = document.createElement('div');
        lbl.className   = 'time-label';
        lbl.textContent = 'All Day';
        grid.appendChild(lbl);

        const cell      = document.createElement('div');
        cell.className  = 'time-cell';
        allDay.forEach(ev => cell.appendChild(makePill(ev)));
        const dateStr = `${y}-${pad(m+1)}-${pad(d)}`;
        cell.addEventListener('click', () => openModal(dateStr));
        grid.appendChild(cell);
    }

    HOURS.forEach(hour => {
        const lbl       = document.createElement('div');
        lbl.className   = 'time-label';
        lbl.textContent = fmtHour(hour);
        grid.appendChild(lbl);

        const isCurrent = now.getHours() === hour && sameDay(now.toISOString(), y, m, d);
        const cell      = document.createElement('div');
        cell.className  = 'time-cell' + (isCurrent ? ' current-hour' : '');

        events
            .filter(ev => !ev.is_all_day &&
                          sameDay(ev.start_time, y, m, d) &&
                          new Date(ev.start_time).getHours() === hour)
            .forEach(ev => {
                const pill = makePill(ev);
                const s    = new Date(ev.start_time);
                const e2   = new Date(ev.end_time);
                if (!isHidden(ev)) pill.textContent = `${ev.title} (${fmt12(s)}–${fmt12(e2)})`;
                cell.appendChild(pill);
            });

        const dateStr = `${y}-${pad(m+1)}-${pad(d)}`;
        cell.addEventListener('click', () => openModal(dateStr));
        grid.appendChild(cell);
    });
}

// ─── Event Pill ───────────────────────────────────────────────────────────────

function makePill(ev, small = false) {
    const pill     = document.createElement('div');
    pill.className = `event-pill ${ev.priority}`;
    if (small) pill.style.fontSize = '0.65rem';
    pill.textContent = isHidden(ev) ? '(Private)' : ev.title;
    pill.title       = isHidden(ev) ? 'Anonymous — time blocked'
                                    : `${ev.title}${ev.location ? ' @ ' + ev.location : ''}`;
    pill.addEventListener('click', e => { e.stopPropagation(); openDetailModal(ev); });
    return pill;
}

// ─── Upcoming Events Panel ────────────────────────────────────────────────────

function updateUpcomingEvents() {
    const now      = new Date();
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
        const li      = document.createElement('li');
        li.className  = `event-list-${ev.priority}`;
        const start   = new Date(ev.start_time);
        const dateStr = start.toLocaleDateString('en-US', { month:'short', day:'numeric' });
        const timeStr = ev.is_all_day ? 'All Day' : fmt12(start);
        li.innerHTML  = `
            <strong>${isHidden(ev) ? '(Anonymous)' : ev.title}</strong>
            <span>${dateStr} – ${timeStr}${ev.location && !isHidden(ev) ? ' @ ' + ev.location : ''}</span>
        `;
        li.style.cursor = 'pointer';
        li.addEventListener('click', () => openDetailModal(ev));
        list.appendChild(li);
    });
}

// ─── View Switching & Navigation ─────────────────────────────────────────────

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

function initNavigation() {
    const [prevBtn, nextBtn] = document.querySelectorAll('.calendar-controls button');

    prevBtn.addEventListener('click', () => {
        if (state.view === 'daily') {
            const d = new Date(state.selectedDate + 'T12:00:00');
            d.setDate(d.getDate() - 1);
            state.selectedDate = `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
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
            state.selectedDate = `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
        } else {
            state.month++;
            if (state.month > 11) { state.month = 0; state.year++; }
        }
        renderCalendar();
    });
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

// Anonymous events are only hidden from others — the owner always sees full details
function isHidden(ev) {
    return ev.anonymous && ev.owner_user_id !== CURRENT_USER_ID;
}

function sameDay(isoStr, year, month, date) {
    const d = new Date(isoStr);
    return d.getFullYear() === year && d.getMonth() === month && d.getDate() === date;
}

function datesEqual(a, b) {
    return a.getFullYear() === b.getFullYear() &&
           a.getMonth()    === b.getMonth()    &&
           a.getDate()     === b.getDate();
}

function pad(n)  { return String(n).padStart(2, '0'); }

function todayStr() {
    const t = new Date();
    return `${t.getFullYear()}-${pad(t.getMonth()+1)}-${pad(t.getDate())}`;
}

function fmt12(date) {
    let h  = date.getHours();
    const m  = pad(date.getMinutes());
    const ap = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return `${h}:${m} ${ap}`;
}

function fmtHour(h) {
    const ap = h >= 12 ? 'PM' : 'AM';
    return `${h % 12 || 12} ${ap}`;
}

function capitalize(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

// ─── Init ─────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', async () => {

    // Load events from database
    state.events = await fetchEvents();

    // Add-event form
    document.getElementById('event-form').addEventListener('submit', handleFormSubmit);
    document.querySelectorAll('.modal-dismiss').forEach(btn =>
        btn.addEventListener('click', closeModal));
    document.getElementById('event-modal').addEventListener('click', e => {
        if (e.target === e.currentTarget) closeModal();
    });
    document.getElementById('event-allday').addEventListener('change', e =>
        setAllDayFields(e.target.checked));

    // Detail modal
    document.querySelectorAll('.detail-dismiss').forEach(btn =>
        btn.addEventListener('click', closeDetailModal));
    document.getElementById('detail-modal').addEventListener('click', e => {
        if (e.target === e.currentTarget) closeDetailModal();
    });

    // Sidebar buttons
    document.querySelectorAll('.open-add-modal').forEach(btn =>
        btn.addEventListener('click', () => openModal()));

    initViewSwitcher();
    initNavigation();

    renderCalendar();
    updateUpcomingEvents();
});
