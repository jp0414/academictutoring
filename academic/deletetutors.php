<?php
require_once __DIR__ . '/core/workspace.php';

$tutor = getTutorByID($pdo, $_GET['tutor_id'] ?? null);
if (!$tutor) http_response_code(404);
pageStart('Delete tutor');
backLink('index.php', 'Tutor directory');
if (!$tutor) {
    pageHeading('Tutor not found', 'Return to the directory to choose another tutor.');
    pageEnd();
    return;
}
pageHeading('Delete tutor?');
formError();
?>
<div class="confirmation">
    <h2><?= h($tutor['first_name'] . ' ' . $tutor['last_name']) ?></h2>
    <?php facts(['Email' => $tutor['email'], 'Specialization' => $tutor['specialization'], 'Phone number' => $tutor['phone_number'] ?: '—', 'Hourly rate' => hourlyRate($tutor['hourly_rate']), 'Date joined' => recordDate($tutor['date_joined'])]); ?>
    <details><summary>Record history</summary><?php facts(recordHistory($tutor)); ?></details>
    <p class="warning">Deleting this tutor permanently removes their profile and all their sessions, including sessions in the recycle bin.</p>
    <form class="form-actions" method="post" action="core/handleForms.php?tutor_id=<?= (int)$tutor['tutor_id'] ?>">
        <?php csrfField(); ?>
        <a class="button secondary" href="index.php">Keep tutor</a><button class="button danger" name="deleteTutorBtn">Permanently delete tutor</button>
    </form>
</div>
<?php pageEnd();