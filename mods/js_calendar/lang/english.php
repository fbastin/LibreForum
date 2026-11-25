<?php
if (!defined('PHORUM')) return;

// Loaded by phorum_mod_js_calendar_load_language(), with english.php as the
// fallback for a language that has no file here. "%name%" is replaced by a
// username. "jscalendar_language" is the code of jsCalendar's own language
// file (js/jsCalendar.lang.<code>.js); "en" is built in.
$PHORUM['DATA']['LANG']['mod_js_calendar'] = array(
    'jscalendar_language' => 'en',
    'months'              => array('January', 'February', 'March', 'April', 'May', 'June',
                                   'July', 'August', 'September', 'October', 'November', 'December'),

    'page_title'          => 'Event calendar',
    'prev_month'          => 'Previous month',
    'next_month'          => 'Next month',
    'events_on'           => 'Events on',
    'export'              => 'Export',
    'export_title'        => 'Export in iCal format (.ics)',
    'add'                 => '+ Add',
    'select_date'         => 'Select a date.',
    'login_to_add'        => 'Log in to propose an event.',
    'add_event'           => 'Add an event',
    'edit_event'          => 'Edit the event',
    'date'                => 'Date',
    'title'               => 'Title',
    'title_placeholder'   => 'Event title',
    'description'         => 'Description',
    'desc_placeholder'    => 'Details, times, place...',
    'save'                => 'Save',
    'cancel'              => 'Cancel',
    'delete'              => 'Delete',

    'loading'             => 'Loading...',
    'no_events'           => 'No event planned.',
    'load_error'          => 'Loading error.',
    'confirm_delete'      => 'Delete this event?',
    'error'               => 'Error: ',
    'unknown'             => 'Unknown',
    'network_error'       => 'Network error.',
    'by'                  => 'By %name%',
    'birthday_of'         => 'Birthday of %name%',

    'not_logged_in'       => 'Not logged in',
    'invalid_token'       => 'Invalid token: reload the page.',
    'invalid_date'        => 'Invalid date',
    'missing_fields'      => 'Missing required fields',
    'not_found'           => 'Event not found',
    'forbidden'           => 'Access denied',
);
?>
