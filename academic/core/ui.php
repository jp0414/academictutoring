<?php

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// One token per login session; every form prints it and handleForms.php checks it.
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function csrfField() { ?>
    <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
<?php }

// Shows the message for ?error=... sent back by handleForms.php.
function formError() {
    $messages = [
        'missing' => 'Fill in all the required fields.',
        'email' => 'Enter a valid email address, like name@example.com.',
        'rate' => 'Enter the hourly rate as a number, like 450.',
        'time_order' => 'The end time must be later than the start time.',
        'image_size' => 'The image is too large. Choose an image under 5 MB.',
        'image_type' => 'That file is not a supported image. Choose a JPG, PNG, GIF or WebP file.',
        'image_failed' => 'The image could not be saved. Try again.',
        'expired' => 'Your form expired. Please try again.',
        'failed' => 'Your changes could not be saved. Check the details and try again. If you used an email that is already registered, choose a different one.',
    ];
    $code = $_GET['error'] ?? '';
    if (is_string($code) && isset($messages[$code])) notice($messages[$code], true);
}

function recordDate($value, $withTime = false) {
    if (!$value) return '—';
    $timestamp = strtotime($value);
    return $timestamp === false ? $value : date($withTime ? 'M j, Y · g:i A' : 'M j, Y', $timestamp);
}

function sessionTime($session) {
    // Non-breaking space keeps "3:00 PM" together when the line wraps on phones.
    $nbsp = "\u{00A0}";
    return date('g:i', strtotime($session['start_time'])) . $nbsp . date('A', strtotime($session['start_time']))
        . ' – ' . date('g:i', strtotime($session['end_time'])) . $nbsp . date('A', strtotime($session['end_time']));
}

function hourlyRate($value) {
    return '₱' . number_format((float)$value, 2);
}

function brandMark($size = 32) { ?>
    <svg class="brand-mark" width="<?= (int)$size ?>" height="<?= (int)$size ?>" viewBox="0 0 32 32" fill="none" aria-hidden="true">
        <rect width="32" height="32" rx="8" fill="currentColor"/>
        <path d="M21.6 9.6A8.4 8.4 0 1 0 21.6 22.4" stroke="var(--mark-ink, #fff)" stroke-width="2.8" stroke-linecap="round"/>
        <path d="M12.2 16H26.5" stroke="var(--mark-ink, #fff)" stroke-width="2" stroke-linecap="round"/>
        <circle cx="12.2" cy="16" r="2.1" fill="var(--mark-ink, #fff)"/>
    </svg>
<?php }

function brandName() { ?>
    <span class="brand-name">Chalkline <small>Tutoring</small></span>
<?php }

// Date rendered as a circulation-card ink stamp.
function dateStamp($value, $withdrawn = false, $withTime = false) {
    $timestamp = $value ? strtotime($value) : false;
    if ($timestamp === false || $timestamp <= 0) return '<span class="stamp-empty">Not recorded</span>';
    $stamp = '<time class="stamp' . ($withdrawn ? ' withdrawn' : '') . '" datetime="' . h(date($withTime ? 'c' : 'Y-m-d', $timestamp)) . '">'
        . ($withdrawn ? '<i aria-hidden="true">Deleted</i> ' : '')
        . '<b>' . date('d', $timestamp) . '</b> ' . strtoupper(date('M Y', $timestamp)) . '</time>';
    return $withTime ? $stamp . '<small class="stamp-time">' . date('g:i A', $timestamp) . '</small>' : $stamp;
}

function firstLetter($text) {
    $text = trim((string)$text);
    if ($text === '') return '';
    $letter = function_exists('mb_substr') ? mb_substr($text, 0, 1, 'UTF-8') : (preg_match('/./u', $text, $m) ? $m[0] : $text[0]);
    return function_exists('mb_strtoupper') ? mb_strtoupper($letter, 'UTF-8') : strtoupper($letter);
}

function monogram($first, $last) {
    $initials = firstLetter($first) . firstLetter($last);
    if ($initials === '') $initials = '?';
    $tone = (ord($initials[0]) + ord(substr($initials, -1))) % 4; ?>
    <span class="monogram tone-<?= $tone ?>" aria-hidden="true"><?= h($initials) ?></span>
<?php }

// Prints an illustration inline, so it shows even if a file path or upload goes wrong.
function inlineArt($file, $class) {
    $path = __DIR__ . '/../assets/img/' . $file;
    if (!is_file($path)) return;
    $svg = preg_replace('/<!--.*?-->/s', '', (string)file_get_contents($path));
    echo '<div class="' . h($class) . '" aria-hidden="true">' . $svg . '</div>';
}

function emptyArt() {
    inlineArt('empty-pocket.svg', 'empty-art');
}

function pageStart($title, $section = 'directory', $binUrl = 'recyclebin.php', $deletedCount = null) {
    // Count deleted sessions here so the recycle bin badge shows on every page.
    if ($deletedCount === null && $section !== 'auth' && isset($GLOBALS['pdo'])) {
        $deletedCount = countDeletedSessions($GLOBALS['pdo']);
    } ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title) ?> · Chalkline Tutoring</title>
    <meta name="theme-color" content="#133a2c">
    <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
    <link rel="preload" href="assets/fonts/public-sans-latin.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="styles.css">
</head>
<body<?= $section === 'auth' ? ' class="auth-page"' : '' ?>>
<?php if ($section === 'auth') { ?>
    <main class="auth-layout" id="main">
    <aside class="auth-brand">
        <div class="auth-logo"><?php brandMark(44); brandName(); ?></div>
        <p class="auth-tagline">Every lesson leaves a mark.</p>
        <?php inlineArt('auth-card.svg', 'auth-art'); ?>
    </aside>
<?php } else { ?>
    <a class="skip-link" href="#main">Skip to content</a>
    <header class="masthead"><div class="masthead-inner">
        <a class="brand" href="index.php" aria-label="Chalkline Tutoring home"><?php brandMark(); brandName(); ?></a>
        <nav aria-label="Main navigation">
            <a href="index.php"<?= $section === 'directory' ? ' aria-current="page"' : ($section === 'sessions' ? ' aria-current="true"' : '') ?>><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="3.2"/><path d="M3.5 19c.8-3.2 3-5 5.5-5s4.7 1.8 5.5 5"/><path d="M15.5 5.3a3 3 0 0 1 0 5.4M17.5 14.4c1.6.7 2.6 2.4 3 4.6"/></svg>Tutor directory</a>
            <a href="<?= h($binUrl) ?>"<?= $section === 'bin' ? ' aria-current="page"' : '' ?>><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M9.5 7V4.5h5V7M6 7l1 13h10l1-13M10 11v5.5M14 11v5.5"/></svg>Recycle bin<?php if ($deletedCount > 0) { ?> <span class="count"><?= (int)$deletedCount ?></span><?php } ?></a>
        </nav>
        <div class="account"><?php monogram($_SESSION['first_name'] ?? '', $_SESSION['last_name'] ?? ''); ?><span><?= h(trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''))) ?> <small><?= h($_SESSION['username'] ?? '') ?></small></span><form action="core/handleForms.php" method="post"><?php csrfField(); ?><button name="logoutBtn">Log out</button></form></div>
    </div></header>
    <main class="workspace" id="main">
<?php } }

function pageEnd() { ?>
    </main>
</body>
</html>
<?php }

function pageHeading($title, $description = '', $actionUrl = '', $actionText = '') { ?>
    <div class="page-heading">
        <div><h1><?= h($title) ?></h1><?php if ($description !== '') { ?><p><?= h($description) ?></p><?php } ?></div>
        <?php if ($actionUrl !== '') { ?><a class="button primary" href="<?= h($actionUrl) ?>"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg><?= h($actionText) ?></a><?php } ?>
    </div>
<?php }

function backLink($url, $text) { ?>
    <a class="back-link" href="<?= h($url) ?>"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg><?= h($text) ?></a>
<?php }

function notice($message, $error = false) { ?>
    <p class="notice<?= $error ? ' error' : '' ?>" role="<?= $error ? 'alert' : 'status' ?>"><?= h($message) ?></p>
<?php }

function searchForm($action, $label, $hint, $query, $tutorId = null) { ?>
    <form class="search-form" role="search" action="<?= h($action) ?>" method="get">
        <?php if ($tutorId !== null) { ?><input type="hidden" name="tutor_id" value="<?= (int)$tutorId ?>"><?php } ?>
        <label class="visually-hidden" for="search"><?= h($label) ?></label>
        <div class="search-field">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg>
            <input type="search" id="search" name="search" placeholder="<?= h($hint) ?>" value="<?= h($query) ?>">
        </div>
        <button class="button secondary">Search</button>
        <?php if ($query !== '') { ?><a class="clear" href="<?= h($action . ($tutorId !== null ? '?tutor_id=' . (int)$tutorId : '')) ?>">Clear search</a><?php } ?>
    </form>
<?php }

function facts($items) { ?>
    <dl class="facts">
        <?php foreach ($items as $label => $value) { ?><div<?= $label === 'Notes' ? ' class="wide"' : '' ?>><dt><?= h($label) ?></dt><dd><?= h($value ?? '—') ?></dd></div><?php } ?>
    </dl>
<?php }

function recordHistory($row) {
    return [
        'Added by' => $row['added_by_username'] ?? 'Unknown',
        'Date added' => recordDate($row['date_added'], true),
        'Last updated by' => $row['last_updated_by_username'] ?? $row['added_by_username'] ?? 'Unknown',
        'Last updated' => recordDate($row['last_updated'], true),
    ];
}

function sessionImage($row) {
    if (empty($row['image'])) return;
    $url = 'uploads/' . rawurlencode($row['image']); ?>
    <a class="attachment" href="<?= h($url) ?>" target="_blank" rel="noopener">
        <img src="<?= h($url) ?>" alt="" width="96" height="68" loading="lazy">
        <span>View session photo</span> <span class="visually-hidden">for <?= h($row['subject_taught']) ?> (opens in a new tab)</span>
    </a>
<?php }