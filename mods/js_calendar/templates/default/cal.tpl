<link rel="stylesheet" href="/forum/mods/js_calendar/css/jsCalendar.min.css?v=1">
<script src="/forum/mods/js_calendar/js/jsCalendar.min.js?v=1"></script>
<script src="/forum/mods/js_calendar/js/jsCalendar.lang.fr.js?v=1"></script>
<link rel="stylesheet" href="/forum/mods/js_calendar/css/calendar.css?v=20260627">

<div class="calendar-page-header">Calendrier des &eacute;v&eacute;nements</div>
<div class="PhorumStdBlock calendar-page-main">
    <div class="calendar-layout-container">
        <div class="cal-nav-row">
            <button type="button" id="cal-prev" class="cal-nav-btn" title="Mois pr&eacute;c&eacute;dent">&#8249;</button>
            <div class="cal-grid">
                <div class="cal-cell cal-side" id="cal-left"></div>
                <div class="cal-cell cal-center" id="cal-center"></div>
                <div class="cal-cell cal-side" id="cal-right"></div>
            </div>
            <button type="button" id="cal-next" class="cal-nav-btn" title="Mois suivant">&#8250;</button>
        </div>

        <div class="calendar-events-panel">
            <div class="events-panel-header">
                <h3 class="events-panel-title">&Eacute;v&eacute;nements le <span id="selected-date-str">-</span></h3>
                <div class="events-panel-actions">
                    <a href="{CALENDAR->ajax_url},action=export" class="btn-export-cal" title="Exporter au format iCal (.ics)">Exporter</a>
                    {IF CALENDAR->is_logged_in}
                    <button type="button" id="btn-open-add" class="btn-add-event">+ Ajouter</button>
                    {/IF}
                </div>
            </div>
            <div id="events-container" class="events-scroll">S&eacute;lectionnez une date.</div>
            {IF NOT CALENDAR->is_logged_in}
            <p style="color: #888; font-style: italic; margin: 1rem 0 0; font-size: 0.9rem;">
                Connectez-vous pour proposer un &eacute;v&eacute;nement.
            </p>
            {/IF}
        </div>
    </div>
</div>

{IF CALENDAR->is_logged_in}
<div id="cal-modal-overlay" class="cal-modal-overlay">
    <div class="cal-modal" role="dialog" aria-labelledby="modal-title">
        <div class="cal-modal-head">
            <h3 id="modal-title">Ajouter un &eacute;v&eacute;nement</h3>
            <button type="button" class="cal-modal-close" id="modal-close">&times;</button>
        </div>
        <div class="cal-modal-body">
            <form id="event-form">
                <input type="hidden" id="event-id" name="event_id" value="">
                <div class="form-group">
                    <label for="event-date">Date</label>
                    <input type="date" id="event-date" name="date" required>
                </div>
                <div class="form-group">
                    <label for="event-title">Titre</label>
                    <input type="text" id="event-title" name="title" required placeholder="Titre de l'&eacute;v&eacute;nement">
                </div>
                <div class="form-group">
                    <label for="event-desc">Description</label>
                    <textarea id="event-desc" name="description" placeholder="D&eacute;tails, horaires, lieu..."></textarea>
                </div>
            </form>
        </div>
        <div class="cal-modal-foot">
            <button type="button" id="btn-save" class="btn-save">Enregistrer</button>
            <button type="button" id="btn-cancel" class="btn-cancel">Annuler</button>
            <button type="button" id="btn-modal-delete" class="btn-delete" style="display:none">Supprimer</button>
        </div>
    </div>
</div>
{/IF}

<script type="text/javascript">
(function() {
    var AJAX_URL = "{CALENDAR->ajax_url}";
    var USER_ID = {CALENDAR->user_id};
    var IS_ADMIN = {CALENDAR->is_admin};

    function boot() {
        if (typeof jsCalendar === 'undefined') {
            setTimeout(boot, 150);
            return;
        }

        var calOpts = { language: 'fr', monthFormat: 'month YYYY' };

        var calL = jsCalendar.new('#cal-left',   null, calOpts);
        var calC = jsCalendar.new('#cal-center', null, calOpts);
        var calR = jsCalendar.new('#cal-right',  null, calOpts);
        var cals = [calL, calC, calR];

        var centerDate = new Date();
        centerDate.setDate(1);

        function shiftMonth(d, n) {
            var r = new Date(d.getFullYear(), d.getMonth() + n, 1);
            return r;
        }

        function syncCalendars() {
            calL.goto(shiftMonth(centerDate, -1));
            calC.goto(centerDate);
            calR.goto(shiftMonth(centerDate, 1));
        }

        syncCalendars();

        document.getElementById('cal-prev').addEventListener('click', function() {
            centerDate = shiftMonth(centerDate, -1);
            syncCalendars();
            refreshSelections();
        });
        document.getElementById('cal-next').addEventListener('click', function() {
            centerDate = shiftMonth(centerDate, 1);
            syncCalendars();
            refreshSelections();
        });

        var $events = document.getElementById('events-container');
        var $dateLabel = document.getElementById('selected-date-str');

        var $overlay = document.getElementById('cal-modal-overlay');
        var $modalTitle = document.getElementById('modal-title');
        var $modalClose = document.getElementById('modal-close');
        var $dateInput = document.getElementById('event-date');
        var $titleInput = document.getElementById('event-title');
        var $descInput = document.getElementById('event-desc');
        var $idInput = document.getElementById('event-id');
        var $btnSave = document.getElementById('btn-save');
        var $btnCancel = document.getElementById('btn-cancel');
        var $btnDelete = document.getElementById('btn-modal-delete');
        var $btnAdd = document.getElementById('btn-open-add');

        var currentDate = '';
        var editingId = 0;
        var allEventDates = [];

        function apiUrl(action) {
            return AJAX_URL + ',action=' + action;
        }

        function toDate(s) {
            var p = s.split('-');
            return new Date(parseInt(p[0]), parseInt(p[1]) - 1, parseInt(p[2]));
        }

        function fmtDate(d) {
            var y = d.getFullYear();
            var m = ('0' + (d.getMonth() + 1)).slice(-2);
            var day = ('0' + d.getDate()).slice(-2);
            return y + '-' + m + '-' + day;
        }

        function fmtDateFR(d) {
            var months = ['janvier','février','mars','avril','mai','juin',
                          'juillet','août','septembre','octobre','novembre','décembre'];
            return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
        }

        function esc(text) {
            if (!text) return '';
            var d = document.createElement('div');
            d.textContent = text;
            return d.innerHTML;
        }

        function refreshSelections() {
            var dateObjs = allEventDates.map(toDate);
            for (var i = 0; i < cals.length; i++) {
                cals[i].clearselect();
                if (dateObjs.length) cals[i].select(dateObjs);
            }
        }

        /* --- Modal --- */
        function openModal(mode, ev) {
            if (!$overlay) return;
            editingId = 0;
            if (mode === 'edit' && ev) {
                editingId = ev.event_id;
                $modalTitle.textContent = "Modifier l'événement";
                $idInput.value = ev.event_id;
                $titleInput.value = ev.title;
                $descInput.value = ev.description || '';
                $dateInput.value = ev.event_date;
                $btnDelete.style.display = 'inline-block';
            } else {
                $modalTitle.textContent = "Ajouter un événement";
                $idInput.value = '';
                $titleInput.value = '';
                $descInput.value = '';
                $dateInput.value = currentDate;
                $btnDelete.style.display = 'none';
            }
            $overlay.classList.add('open');
            document.body.style.overflow = 'hidden';
            setTimeout(function() { $titleInput.focus(); }, 100);
        }

        function closeModal() {
            if (!$overlay) return;
            $overlay.classList.remove('open');
            document.body.style.overflow = '';
            editingId = 0;
        }

        if ($overlay) {
            $modalClose.addEventListener('click', closeModal);
            $btnCancel.addEventListener('click', closeModal);
            $overlay.addEventListener('click', function(e) {
                if (e.target === $overlay) closeModal();
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && $overlay.classList.contains('open')) closeModal();
            });
        }

        if ($btnAdd) {
            $btnAdd.addEventListener('click', function() { openModal('add'); });
        }

        /* --- Events --- */
        function loadEvents(date) {
            currentDate = date;
            $dateLabel.textContent = fmtDateFR(toDate(date));
            $events.innerHTML = '<div class="loader">Chargement...</div>';

            fetch(apiUrl('list') + ',date=' + date)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data || data.error || !Array.isArray(data) || data.length === 0) {
                        $events.innerHTML = '<p class="no-events">Aucun événement prévu.</p>';
                        return;
                    }
                    var html = '';
                    for (var i = 0; i < data.length; i++) {
                        var ev = data[i];
                        var canEdit = USER_ID > 0 && (IS_ADMIN || USER_ID == ev.user_id) && ev.type !== 'birthday';
                        var isBirthday = ev.type === 'birthday';
                        html += '<div class="event-item' + (canEdit ? ' editable' : '') + (isBirthday ? ' is-birthday' : '') + '" data-idx="' + i + '">';
                        if (canEdit) {
                            html += '<span class="btn-delete-event" title="Supprimer">&times;</span>';
                        }
                        html += '<div class="event-title">' + (isBirthday ? '🎂 ' : '') + esc(ev.title) + '</div>';
                        if (!isBirthday) {
                            html += '<div class="event-author">Par ' + esc(ev.username) + '</div>';
                        }
                        if (ev.description) {
                            html += '<div class="event-desc">' + esc(ev.description) + '</div>';
                        }
                        html += '</div>';
                    }
                    $events.innerHTML = html;

                    var items = $events.querySelectorAll('.event-item');
                    for (var j = 0; j < items.length; j++) {
                        (function(el) {
                            var idx = parseInt(el.getAttribute('data-idx'));
                            var ev = data[idx];
                            if (ev.type !== 'birthday') {
                                el.addEventListener('click', function() { openModal('edit', ev); });
                            }
                            var del = el.querySelector('.btn-delete-event');
                            if (del) {
                                del.addEventListener('click', function(e) {
                                    e.stopPropagation();
                                    deleteEvent(ev.event_id);
                                });
                            }
                        })(items[j]);
                    }
                })
                .catch(function() {
                    $events.innerHTML = '<p class="no-events">Erreur de chargement.</p>';
                });
        }

        function highlightDates() {
            fetch(apiUrl('list_all_dates'))
                .then(function(r) { return r.json(); })
                .then(function(dates) {
                    allEventDates = Array.isArray(dates) ? dates : [];
                    refreshSelections();
                });
        }

        function deleteEvent(eventId) {
            if (!confirm("Supprimer cet événement ?")) return;
            fetch(apiUrl('delete') + ',event_id=' + eventId)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        closeModal();
                        loadEvents(currentDate);
                        highlightDates();
                    } else {
                        alert('Erreur: ' + (data.error || 'Inconnue'));
                    }
                });
        }

        /* --- Save --- */
        if ($btnSave) {
            $btnSave.addEventListener('click', function() {
                if (!$titleInput.value.trim()) { $titleInput.focus(); return; }
                if (!$dateInput.value) { $dateInput.focus(); return; }
                $btnSave.disabled = true;
                var fd = new FormData(document.getElementById('event-form'));
                fetch(apiUrl('save'), { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            var targetDate = $dateInput.value;
                            closeModal();
                            loadEvents(targetDate);
                            highlightDates();
                        } else {
                            alert('Erreur: ' + (data.error || 'Inconnue'));
                        }
                    })
                    .catch(function() { alert('Erreur réseau.'); })
                    .finally(function() { $btnSave.disabled = false; });
            });
        }

        if ($btnDelete) {
            $btnDelete.addEventListener('click', function() {
                if (editingId) deleteEvent(editingId);
            });
        }

        /* --- Date click on any calendar --- */
        function onDateClicked(event, date) {
            loadEvents(fmtDate(date));
        }
        calL.onDateClick(onDateClicked);
        calC.onDateClick(onDateClicked);
        calR.onDateClick(onDateClicked);

        /* --- Init --- */
        loadEvents(fmtDate(new Date()));
        highlightDates();
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        boot();
    } else {
        document.addEventListener('DOMContentLoaded', boot);
    }
})();
</script>
