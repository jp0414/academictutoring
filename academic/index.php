<?php
require_once __DIR__ . '/core/workspace.php';

$adding = ($_GET['action'] ?? '') === 'add';
pageStart($adding ? 'Add tutor' : 'Tutor directory', 'directory', 'recyclebin.php', countDeletedSessions($pdo));
if ($adding) {
    require_once __DIR__ . '/core/forms.php';
    backLink('index.php', 'Tutor directory');
    pageHeading('Add tutor');
    tutorForm();
    pageEnd();
    return;
}

$query = trim((string)($_GET['search'] ?? ''));
$tutors = $query === '' ? getAllTutors($pdo) : searchTutors($pdo, $query);
$messages = ['added' => 'Tutor added.', 'updated' => 'Tutor details saved.', 'deleted' => 'Tutor and associated sessions deleted.'];
$status = $_GET['status'] ?? '';
if (isset($messages[$status])) notice($messages[$status]);
formError();

pageHeading('Tutor directory', 'Find a tutor and open their tutoring sessions.', 'index.php?action=add', 'Add tutor');
searchForm('index.php', 'Search tutors', 'Search by name, email or specialization', $query);
?>
<section class="records" aria-labelledby="list-title">
    <header class="list-heading"><h2 id="list-title"><?= $query === '' ? 'Registered tutors' : 'Search results' ?></h2><span><?= count($tutors) ?> <?= count($tutors) === 1 ? 'tutor' : 'tutors' ?></span></header>
    <?php if (!$tutors) { ?>
        <div class="empty-state"><?php emptyArt(); ?><h3><?= $query === '' ? 'Your tutor directory is empty' : 'No matching tutors' ?></h3>
            <p><?= $query === '' ? 'Add your first tutor to start recording sessions.' : 'Try another name, email or specialization.' ?></p>
            <a href="<?= $query === '' ? 'index.php?action=add' : 'index.php' ?>"><?= $query === '' ? 'Add a tutor' : 'Show all tutors' ?></a>
        </div>
    <?php } ?>
    <?php foreach ($tutors as $tutor) {
        $id = (int)$tutor['tutor_id'];
        $name = $tutor['first_name'] . ' ' . $tutor['last_name']; ?>
        <article class="record">
            <div class="record-row tutor-row">
                <div class="identity"><h3><a class="name-link" href="viewtutors.php?tutor_id=<?= $id ?>"><?= h($name) ?></a></h3><p><?= h($tutor['email']) ?></p></div>
                <div class="datum"><span>Specialization</span><?= h($tutor['specialization']) ?></div>
                <div class="datum numeric"><span>Hourly rate</span><?= h(hourlyRate($tutor['hourly_rate'])) ?></div>
                <a class="row-action" aria-label="View sessions for <?= h($name) ?>" href="viewtutors.php?tutor_id=<?= $id ?>">View sessions <span aria-hidden="true">→</span></a>
            </div>
            <details><summary>Tutor details<span class="visually-hidden"> for <?= h($name) ?></span></summary>
                <?php facts(['Phone number' => $tutor['phone_number'] ?: '—', 'Date joined' => recordDate($tutor['date_joined'])] + recordHistory($tutor)); ?>
                <div class="record-actions"><a href="edittutors.php?tutor_id=<?= $id ?>">Edit tutor</a><a class="danger-link" href="deletetutors.php?tutor_id=<?= $id ?>">Delete tutor</a></div>
            </details>
        </article>
    <?php } ?>
</section>
<?php pageEnd();