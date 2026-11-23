<?php
require_once __DIR__ . '/core/workspace.php';
require_once __DIR__ . '/core/forms.php';

$session = getSessionByID($pdo, $_GET['session_id'] ?? null);
if (!$session) http_response_code(404);
$id = (int)($session['tutor_id'] ?? 0);
pageStart('Edit session', 'sessions', $id ? 'recyclebin.php?tutor_id=' . $id : 'recyclebin.php', $id ? countDeletedSessions($pdo, $id) : null);
if ($session) {
    backLink('viewtutors.php?tutor_id=' . $id, 'Sessions for ' . $session['tutor_name']);
    pageHeading('Edit session', $session['tutor_name']);
    sessionForm($id, $session);
} else {
    backLink('index.php', 'Tutor directory');
    pageHeading('Session not found', 'Open a tutor from the directory to find their sessions.');
}
pageEnd();