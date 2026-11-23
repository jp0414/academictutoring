<?php

require_once 'dbConfig.php';
require_once 'models.php';

// Sends the browser to a page one level up (from core/) and stops.
function redirectTo($path) {
	header("Location: ../" . $path);
	exit();
}

// ============================================================
// REQUEST GUARDS
// ============================================================

// Every POST must carry the CSRF token that was printed into the form.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$token = $_POST['csrf_token'] ?? '';
	if (empty($_SESSION['csrf_token']) || !is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
		redirectTo(isset($_SESSION['user_id']) ? 'index.php?error=expired' : 'login.php?error=expired');
	}
}

$isAuthAction = isset($_POST['registerBtn']) || isset($_POST['loginBtn']);
if (!$isAuthAction && !isset($_SESSION['user_id'])) {
	redirectTo('login.php');
}


// ============================================================
// USER AUTHENTICATION HANDLING
// ============================================================

// Register a new user
if (isset($_POST['registerBtn'])) {

	$result = registerUser(
		$pdo,
		trim($_POST['firstName'] ?? ''),
		trim($_POST['lastName'] ?? ''),
		trim($_POST['username'] ?? ''),
		$_POST['password'] ?? '',
		trim($_POST['email'] ?? ''),
		$_POST['address'] ?? '',
		$_POST['age'] ?? '',
		$_POST['phoneNumber'] ?? ''
	);

	if ($result === true) {
		redirectTo("login.php?register=success");
	} elseif ($result === "username_taken" || $result === "email_taken") {
		redirectTo("register.php?error=" . $result);
	}
	redirectTo("register.php?error=failed");
}

// Login user
if (isset($_POST['loginBtn'])) {

	$user = loginUser($pdo, $_POST['username'] ?? '', $_POST['password'] ?? '');

	if ($user) {
		session_regenerate_id(true); // new session ID after login (prevents session fixation)
		$_SESSION['user_id'] = $user['user_id'];
		$_SESSION['username'] = $user['username'];
		$_SESSION['first_name'] = $user['first_name'];
		$_SESSION['last_name'] = $user['last_name'];
		redirectTo("index.php");
	}
	redirectTo("login.php?error=invalid");
}

// Logout user (POST only, so a link on another site cannot log people out)
if (isset($_POST['logoutBtn'])) {
	session_unset();
	session_destroy();
	redirectTo("login.php?logout=success");
}


// ============================================================
// TUTOR HANDLING (PARENT TABLE)
// ============================================================

// Cleans the tutor form. Returns [data, errorCode]; errorCode is null when valid.
function cleanTutorInput($post) {
	$data = [
		'firstName' => trim(strip_tags($post['firstName'] ?? '')),
		'lastName' => trim(strip_tags($post['lastName'] ?? '')),
		'email' => trim($post['email'] ?? ''),
		'phoneNumber' => trim(strip_tags($post['phoneNumber'] ?? '')),
		'specialization' => trim(strip_tags($post['specialization'] ?? '')),
		'hourlyRate' => $post['hourlyRate'] ?? '',
	];
	if ($data['firstName'] === '' || $data['lastName'] === '' || $data['specialization'] === '') {
		return [$data, 'missing'];
	}
	if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
		return [$data, 'email'];
	}
	if (!is_numeric($data['hourlyRate']) || (float)$data['hourlyRate'] < 0) {
		return [$data, 'rate'];
	}
	$data['hourlyRate'] = round((float)$data['hourlyRate'], 2);
	return [$data, null];
}

// Insert a new Tutor
if (isset($_POST['insertTutorBtn'])) {

	[$t, $error] = cleanTutorInput($_POST);
	if ($error) redirectTo("index.php?action=add&error=" . $error);

	try {
		$query = insertTutor($pdo, $t['firstName'], $t['lastName'], $t['email'], $t['phoneNumber'], $t['specialization'], $t['hourlyRate'], $_SESSION['user_id']);
	} catch (PDOException $e) {
		$query = false;
	}

	if ($query) redirectTo("index.php?status=added");
	redirectTo("index.php?action=add&error=failed");
}

// Edit an existing Tutor
if (isset($_POST['editTutorBtn'])) {

	$tutorId = (int)($_GET['tutor_id'] ?? 0);
	[$t, $error] = cleanTutorInput($_POST);
	if ($error) redirectTo("edittutors.php?tutor_id=" . $tutorId . "&error=" . $error);

	try {
		$query = updateTutor($pdo, $tutorId, $t['firstName'], $t['lastName'], $t['email'], $t['phoneNumber'], $t['specialization'], $t['hourlyRate'], $_SESSION['user_id']);
	} catch (PDOException $e) {
		$query = false;
	}

	if ($query) redirectTo("index.php?status=updated");
	redirectTo("edittutors.php?tutor_id=" . $tutorId . "&error=failed");
}

// Delete a Tutor
if (isset($_POST['deleteTutorBtn'])) {

	$tutorId = (int)($_GET['tutor_id'] ?? 0);
	if (deleteTutor($pdo, $tutorId)) redirectTo("index.php?status=deleted");
	redirectTo("deletetutors.php?tutor_id=" . $tutorId . "&error=failed");
}


// ============================================================
// SESSION HANDLING (CHILD TABLE)
// ============================================================

const MAX_IMAGE_BYTES = 5 * 1024 * 1024; // 5 MB

// Saves an uploaded session image.
// Returns ['name' => filename|null, 'error' => code|null].
function handleSessionImageUpload() {
	$file = $_FILES['sessionImage'] ?? null;
	if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
		return ['name' => null, 'error' => null];
	}
	if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > MAX_IMAGE_BYTES) {
		return ['name' => null, 'error' => 'image_size'];
	}

	// Check the file content, not just the name: it must really be an image.
	$allowedTypes = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
	$info = @getimagesize($file['tmp_name']);
	if ($info === false || !isset($allowedTypes[$info[2]])) {
		return ['name' => null, 'error' => 'image_type'];
	}

	$uploadDir = __DIR__ . '/../uploads/';
	if (!is_dir($uploadDir)) {
		mkdir($uploadDir, 0755, true);
	}
	$newFileName = 'session_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $allowedTypes[$info[2]];
	if (!move_uploaded_file($file['tmp_name'], $uploadDir . $newFileName)) {
		return ['name' => null, 'error' => 'image_failed'];
	}
	return ['name' => $newFileName, 'error' => null];
}

// Removes an old image file when it has been replaced.
function removeSessionImage($fileName) {
	if (!$fileName) return;
	$path = __DIR__ . '/../uploads/' . basename($fileName);
	if (is_file($path)) unlink($path);
}

// Returns an error code if the session form is invalid, otherwise null.
function sessionInputError($post) {
	foreach (['sessionDate', 'startTime', 'endTime', 'subjectTaught'] as $field) {
		if (trim($post[$field] ?? '') === '') return 'missing';
	}
	if (strtotime($post['endTime']) <= strtotime($post['startTime'])) return 'time_order';
	return null;
}

// Insert a new Session under a specific Tutor (with image upload)
if (isset($_POST['insertSessionBtn']) || isset($_POST['insertNewSessionBtn'])) {

	$tutorId = (int)($_GET['tutor_id'] ?? 0);
	$formUrl = "viewtutors.php?tutor_id=" . $tutorId . "&action=add";

	$error = sessionInputError($_POST);
	if ($error) redirectTo($formUrl . "&error=" . $error);

	$upload = handleSessionImageUpload();
	if ($upload['error']) redirectTo($formUrl . "&error=" . $upload['error']);

	$query = insertSession(
		$pdo,
		$tutorId,
		$_POST['sessionDate'],
		$_POST['startTime'],
		$_POST['endTime'],
		trim(strip_tags($_POST['subjectTaught'])),
		trim($_POST['notes'] ?? ''),
		$_SESSION['user_id'],
		$upload['name']
	);

	if ($query) redirectTo("viewtutors.php?tutor_id=" . $tutorId . "&added=success");
	removeSessionImage($upload['name']);
	redirectTo($formUrl . "&error=failed");
}

// Edit an existing Session (with optional image update)
if (isset($_POST['editSessionBtn'])) {

	$tutorId = (int)($_GET['tutor_id'] ?? 0);
	$sessionId = (int)($_GET['session_id'] ?? 0);
	$formUrl = "editsessions.php?session_id=" . $sessionId . "&tutor_id=" . $tutorId;

	$error = sessionInputError($_POST);
	if ($error) redirectTo($formUrl . "&error=" . $error);

	$upload = handleSessionImageUpload();
	if ($upload['error']) redirectTo($formUrl . "&error=" . $upload['error']);

	$existing = getSessionByID($pdo, $sessionId);

	$query = updateSession(
		$pdo,
		$sessionId,
		$_POST['sessionDate'],
		$_POST['startTime'],
		$_POST['endTime'],
		trim(strip_tags($_POST['subjectTaught'])),
		trim($_POST['notes'] ?? ''),
		$_SESSION['user_id'],
		$upload['name']
	);

	if ($query) {
		if ($upload['name'] && $existing) removeSessionImage($existing['image']);
		redirectTo("viewtutors.php?tutor_id=" . $tutorId . "&updated=success");
	}
	removeSessionImage($upload['name']);
	redirectTo($formUrl . "&error=failed");
}

// Soft-Delete a Session (moves to recycle bin, tracks deleted_by and deleted_at)
if (isset($_POST['deleteSessionBtn'])) {

	$tutorId = (int)($_GET['tutor_id'] ?? 0);
	$sessionId = (int)($_GET['session_id'] ?? 0);

	if (softDeleteSession($pdo, $sessionId, $_SESSION['user_id'])) {
		redirectTo("viewtutors.php?tutor_id=" . $tutorId . "&deleted=success");
	}
	redirectTo("deletesessions.php?session_id=" . $sessionId . "&tutor_id=" . $tutorId . "&error=failed");
}

// Restore a soft-deleted Session from recycle bin.
// Stays on the recycle bin so several sessions can be restored in a row.
if (isset($_POST['restoreSessionBtn'])) {

	$tutorId = (int)($_GET['tutor_id'] ?? 0);
	$sessionId = (int)($_GET['session_id'] ?? 0);
	$bin = !empty($_GET['scoped']) ? "recyclebin.php?tutor_id=" . $tutorId . "&" : "recyclebin.php?";

	if (restoreSession($pdo, $sessionId)) {
		redirectTo($bin . "restored=" . $tutorId);
	}
	redirectTo($bin . "error=failed");
}

redirectTo('index.php');
