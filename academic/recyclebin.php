<?php
require_once __DIR__ . '/core/workspace.php';

$tutor = isset($_GET['tutor_id']) ? getTutorByID($pdo, $_GET['tutor_id']) : null;
$id = $tutor ? (int)$tutor['tutor_id'] : null;
$name = $tutor ? $tutor['first_name'] . ' ' . $tutor['last_name'] : '';
$sessions = $tutor ? getDeletedSessionsByTutor($pdo, $id) : getAllDeletedSessions($pdo);
$bin = $tutor ? 'recyclebin.php?tutor_id=' . $id : 'recyclebin.php';
pageStart('Recycle bin', 'bin', $bin, count($sessions));
backLink($tutor ? 'viewtutors.php?tutor_id=' . $id : 'index.php', $tutor ? 'Sessions for ' . $name : 'Tutor directory');
$restoredTutor = (int)($_GET['restored'] ?? 0);
if ($restoredTutor) { ?>
    <p class="notice" role="status">Session restored. <a href="viewtutors.php?tutor_id=<?= $restoredTutor ?>">View the tutor’s sessions</a></p>
<?php }
formError();
pageHeading('Recycle bin', 'Restore a deleted session to its tutor’s session list.');
?>
<section class="records" aria-labelledby="list-title">
    <header class="list-heading"><h2 id="list-title"><?= $tutor ? 'Deleted sessions for ' . h($name) : 'Deleted sessions' ?></h2><span><?= count($sessions) ?> <?= count($sessions) === 1 ? 'session' : 'sessions' ?></span></header>
    <?php if ($tutor) { ?><p class="scope-link"><a href="recyclebin.php">View deleted sessions for all tutors</a></p><?php } ?>
    <?php if (!$sessions) { ?>
        <div class="empty-state"><?php emptyArt(); ?><h3><?= $tutor ? 'No deleted sessions for this tutor' : 'The recycle bin is empty' ?></h3>
            <p>Sessions moved to the recycle bin will appear here.</p>
        </div>
    <?php } ?>
    <?php foreach ($sessions as $row) { ?>
        <article class="record">
            <div class="record-row session-row">
                <div class="identity"><div><h3><?= h($row['subject_taught']) ?></h3><p><?= h($row['tutor_name']) ?> · <span class="ref">Session #<?= (int)$row['session_id'] ?></span></p></div></div>
                <div class="datum"><span>Deleted by</span><?= h($row['deleted_by_username'] ?? 'Unknown') ?></div>
                <div class="datum"><span>Deleted on</span><?= dateStamp($row['deleted_at'], true, true) ?></div>
                <form action="core/handleForms.php?session_id=<?= (int)$row['session_id'] ?>&amp;tutor_id=<?= (int)$row['tutor_id'] ?><?= $tutor ? '&amp;scoped=1' : '' ?>" method="post">
                    <?php csrfField(); ?>
                    <button name="restoreSessionBtn" class="button secondary" aria-label="Restore <?= h($row['subject_taught']) ?>">Restore session</button>
                </form>
            </div>
            <details><summary>Session details<span class="visually-hidden"> for <?= h($row['subject_taught']) ?></span></summary>
                <?php facts(['Session date' => recordDate($row['session_date']), 'Time' => sessionTime($row), 'Notes' => $row['notes'] ?: 'No notes']); sessionImage($row); ?>
            </details>
        </article>
    <?php } ?>
</section>
<?php pageEnd();