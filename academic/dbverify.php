<?php
require_once __DIR__ . '/core/workspace.php';

// Shows the sessions table structure and raw rows, to verify the image column,
// soft-delete tracking and audit columns (the same checks you would do in phpMyAdmin).
$descriptions = [
    'session_id' => 'Primary key (auto increment)',
    'tutor_id' => 'Foreign key to tutors(tutor_id), the parent entity',
    'session_date' => 'Date of the tutoring lesson',
    'start_time' => 'Lesson start time',
    'end_time' => 'Lesson end time',
    'subject_taught' => 'Subject or topic covered',
    'notes' => 'Session notes or homework',
    'image' => 'One image per child entity: filename in /uploads/',
    'date_added' => 'When the record was created',
    'added_by' => 'User ID who created the record',
    'last_updated_by' => 'User ID who last edited the record',
    'last_updated' => 'When the record was last edited',
    'is_deleted' => 'Soft-delete flag (0 = active, 1 = in recycle bin)',
    'deleted_by' => 'User ID who moved the record to the recycle bin',
    'deleted_at' => 'When the record was moved to the recycle bin',
];
$highlighted = ['image', 'is_deleted', 'deleted_by', 'deleted_at'];
$columns = $pdo->query("DESCRIBE sessions")->fetchAll(PDO::FETCH_ASSOC);
$rows = $pdo->query("SELECT s.*, CONCAT(t.first_name, ' ', t.last_name) AS tutor_name, u.username AS del_user
                     FROM sessions s
                     INNER JOIN tutors t ON s.tutor_id = t.tutor_id
                     LEFT JOIN users u ON s.deleted_by = u.user_id
                     ORDER BY s.session_id DESC")->fetchAll(PDO::FETCH_ASSOC);

pageStart('Database check', 'dbverify');
backLink('index.php', 'Tutor directory');
pageHeading('Database check', 'Structure of the sessions table and every session row, including ones in the recycle bin. Highlighted columns are the image and soft-delete fields.');
?>
<section class="records table-wrap" aria-labelledby="schema-title">
    <h2 id="schema-title">1. Sessions table structure</h2>
    <table class="data-table">
        <thead><tr><th scope="col">Field</th><th scope="col">Type</th><th scope="col">Null</th><th scope="col">Key</th><th scope="col">Default</th><th scope="col">Purpose</th></tr></thead>
        <tbody>
        <?php foreach ($columns as $col) { ?>
            <tr<?= in_array($col['Field'], $highlighted, true) ? ' class="highlight"' : '' ?>>
                <td><code><?= h($col['Field']) ?></code></td>
                <td><code><?= h($col['Type']) ?></code></td>
                <td><?= h($col['Null']) ?></td>
                <td><?= h($col['Key']) ?></td>
                <td><?= h($col['Default'] ?? 'None') ?></td>
                <td><?= h($descriptions[$col['Field']] ?? '') ?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</section>

<section class="records table-wrap" aria-labelledby="rows-title">
    <h2 id="rows-title">2. All sessions (raw data)</h2>
    <table class="data-table">
        <thead><tr><th scope="col">ID</th><th scope="col">Tutor</th><th scope="col">Subject</th><th scope="col">Image file</th><th scope="col">is_deleted</th><th scope="col">deleted_by</th><th scope="col">deleted_at</th><th scope="col">added_by</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r) { ?>
            <tr<?= $r['is_deleted'] ? ' class="is-deleted"' : '' ?>>
                <td><code>#<?= (int)$r['session_id'] ?></code></td>
                <td><?= h($r['tutor_name']) ?></td>
                <td><?= h($r['subject_taught']) ?></td>
                <td><code><?= h($r['image'] ?: 'None') ?></code></td>
                <td><?= $r['is_deleted'] ? '1 (deleted)' : '0 (active)' ?></td>
                <td><?= h($r['del_user'] ? $r['del_user'] . ' (ID ' . $r['deleted_by'] . ')' : 'None') ?></td>
                <td><?= h($r['deleted_at'] ?: 'None') ?></td>
                <td><?= h($r['added_by'] !== null ? 'ID ' . $r['added_by'] : 'None') ?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</section>
<?php pageEnd();
