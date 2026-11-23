<?php
require_once __DIR__ . '/core/workspace.php';
require_once __DIR__ . '/core/forms.php';

$tutor = getTutorByID($pdo, $_GET['tutor_id'] ?? null);
if (!$tutor) http_response_code(404);
pageStart('Edit tutor');
backLink('index.php', 'Tutor directory');
if ($tutor) {
    pageHeading('Edit tutor', $tutor['first_name'] . ' ' . $tutor['last_name']);
    tutorForm($tutor);
} else {
    pageHeading('Tutor not found', 'Return to the directory to choose another tutor.');
}
pageEnd();
