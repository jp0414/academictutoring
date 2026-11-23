<?php
require_once __DIR__ . '/core/workspace.php';

$session = getSessionByID($pdo, $_GET['session_id'] ?? null);
if (!$session) http_response_code(404);
$id = (int)($session['tutor_id'] ?? 0);
pageStart('Move session to recycle bin', 'sessions', $id ? 'recyclebin.php?tutor_id=' . $id : 'recyclebin.php', $id ? countDeletedSessions($pdo, $id) : null);
if (!$session) {
    backLink('index.php', 'Tutor directory');
    pageHeading('Session not found', 'Open a tutor from the directory to find their sessions.');
    pageEnd();
    return;
}
$back = 'viewtutors.php?tutor_id=' . $id;
backLink($back, 'Sessions for ' . $session['tutor_name']);
pageHeading('Move session to recycle bin?');
formError();
?>
<div class="confirmation">
    <h2><?= h($session['subject_taught']) ?></h2>
    <p class="confirm-stamp"><?= dateStamp($session['session_date']) ?></p>
    <?php facts(['Tutor' => $session['tutor_name'], 'Time' => sessionTime($session), 'Notes' => $session['notes'] ?: 'No notes']); sessionImage($session); ?>
    <details><summary>Record history</summary><?php facts(recordHistory($session)); ?></details>
    <p class="warning">This session will leave the active list. You can restore it from the recycle bin.</p>
    <form class="form-actions" method="post" action="core/handleForms.php?session_id=<?= (int)$session['session_id'] ?>&amp;tutor_id=<?= $id ?>">
        <?php csrfField(); ?>
        <a class="button secondary" href="<?= h($back) ?>">Keep session</a><button class="button danger" name="deleteSessionBtn">Move to recycle bin</button>
    </form>
</div>
<?php pageEnd();