<?php
require_once __DIR__ . '/core/workspace.php';

$tutor = getTutorByID($pdo, $_GET['tutor_id'] ?? null);
if (!$tutor) {
    header('Location: index.php');
    exit;
}
$id = (int)$tutor['tutor_id'];
$name = $tutor['first_name'] . ' ' . $tutor['last_name'];
$url = 'viewtutors.php?tutor_id=' . $id;
$bin = 'recyclebin.php?tutor_id=' . $id;
$deleted = countDeletedSessions($pdo, $id);
$adding = ($_GET['action'] ?? '') === 'add';
pageStart(($adding ? 'Add session' : 'Tutoring sessions') . ' · ' . $name, 'sessions', $bin, $deleted);
if ($adding) {
    require_once __DIR__ . '/core/forms.php';
    backLink($url, 'Sessions for ' . $name);
    pageHeading('Add session', $name);
    sessionForm($id);
    pageEnd();
    return;
}
backLink('index.php', 'Tutor directory');
if (($_GET['deleted'] ?? '') === 'success') { ?>
    <p class="notice" role="status">Session moved to the recycle bin. <a href="<?= h($bin) ?>">View recycle bin</a></p>
<?php }
foreach (['restored' => 'Session restored.', 'added' => 'Session added.', 'updated' => 'Session changes saved.'] as $key => $message) {
    if (($_GET[$key] ?? '') === 'success') notice($message);
}
pageHeading($name, $tutor['specialization'] . ' · ' . hourlyRate($tutor['hourly_rate']) . ' per hour · ' . $tutor['email'], $url . '&action=add', 'Add session');
$query = trim((string)($_GET['search'] ?? ''));
$sessions = $query === '' ? getSessionsByTutor($pdo, $id) : searchSessionsByTutor($pdo, $id, $query);
searchForm('viewtutors.php', 'Search sessions', 'Search by subject, notes or date', $query, $id);
?>
<section class="records" aria-labelledby="list-title">
    <header class="list-heading"><h2 id="list-title"><?= $query === '' ? 'Tutoring sessions' : 'Search results' ?></h2><span><?= count($sessions) ?> <?= count($sessions) === 1 ? 'session' : 'sessions' ?></span></header>
    <?php if (!$sessions) { ?>
        <div class="empty-state"><?php emptyArt(); ?><h3><?= $query === '' ? 'No active sessions' : 'No matching sessions' ?></h3>
            <p><?= $query === '' ? 'Add a session to record the subject and time.' : 'Try another subject, note or date.' ?></p>
            <a href="<?= h($query === '' ? $url . '&action=add' : $url) ?>"><?= $query === '' ? 'Add a session' : 'Show all active sessions' ?></a>
            <?php if ($deleted) { ?><a href="<?= h($bin) ?>">View deleted sessions (<?= $deleted ?>)</a><?php } ?>
        </div>
    <?php } ?>
    <?php foreach ($sessions as $row) {
        $params = 'session_id=' . (int)$row['session_id'] . '&amp;tutor_id=' . $id; ?>
        <article class="record">
            <div class="record-row session-row">
                <div class="identity"><?php if (!empty($row['image'])) { ?><img class="thumb" src="uploads/<?= h(rawurlencode($row['image'])) ?>" alt="" width="48" height="48" loading="lazy"><?php } ?><div><h3><?= h($row['subject_taught']) ?></h3><p class="ref">Session #<?= (int)$row['session_id'] ?></p></div></div>
                <div class="datum"><span>Date</span><?= dateStamp($row['session_date']) ?></div>
                <div class="datum numeric"><span>Time</span><?= h(sessionTime($row)) ?></div>
                <a class="row-action" href="editsessions.php?<?= $params ?>">Edit session</a>
            </div>
            <details><summary>Session details<span class="visually-hidden"> for <?= h($row['subject_taught']) ?></span></summary>
                <?php facts(['Notes' => $row['notes'] ?: 'No notes', 'Tutor' => $name] + recordHistory($row)); sessionImage($row); ?>
                <div class="record-actions"><a href="editsessions.php?<?= $params ?>">Edit session</a><a class="danger-link" href="deletesessions.php?<?= $params ?>">Move to recycle bin</a></div>
            </details>
        </article>
    <?php } ?>
</section>
<?php pageEnd();