<?php

	// ============================================================
	// USERS - Registration, Login, and Fetch Functions
	// ============================================================

	// Register a new user
	function registerUser($pdo, $firstName, $lastName, $username, $password, $email, $address, $age, $phoneNumber) {
		$checkSql = "SELECT user_id FROM users WHERE username = ?";
		$checkStmt = $pdo->prepare($checkSql);
		$checkStmt->execute([$username]);
		if ($checkStmt->fetch()) {
			return "username_taken";
		}

		$checkSql = "SELECT user_id FROM users WHERE email = ?";
		$checkStmt = $pdo->prepare($checkSql);
		$checkStmt->execute([$email]);
		if ($checkStmt->fetch()) {
			return "email_taken";
		}

		$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

		$cleanAge = (!empty($age) || $age === '0' || $age === 0) ? (int)$age : null;
		$cleanAddress = !empty($address) ? trim($address) : null;
		$cleanPhone = !empty($phoneNumber) ? trim($phoneNumber) : null;

		$sql = "INSERT INTO users (first_name, last_name, username, password, email, address, age, phone_number) 
				VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
		$stmt = $pdo->prepare($sql);
		$executeQuery = $stmt->execute([$firstName, $lastName, $username, $hashedPassword, $email, $cleanAddress, $cleanAge, $cleanPhone]);

		return (bool)$executeQuery;
	}

	// Login user - verify credentials
	function loginUser($pdo, $username, $password) {
		$sql = "SELECT * FROM users WHERE username = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$username]);
		$user = $stmt->fetch(PDO::FETCH_ASSOC);

		if ($user && password_verify($password, $user['password'])) {
			return $user;
		}
		return false;
	}

	// Get user by ID
	function getUserByID($pdo, $user_id) {
		$sql = "SELECT * FROM users WHERE user_id = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$user_id]);
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	// Get username by user_id
	function getUsernameByID($pdo, $user_id) {
		if (!$user_id) return 'Unknown';
		$sql = "SELECT username FROM users WHERE user_id = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$user_id]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ? $row['username'] : 'Unknown';
	}


	// ============================================================
	// TUTORS (PARENT TABLE) - CRUD Functions
	// ============================================================

	// Get all tutors ordered by date joined
	function getAllTutors($pdo) {
		$sql = "SELECT t.*, u1.username AS added_by_username, u2.username AS last_updated_by_username 
				FROM tutors t 
				LEFT JOIN users u1 ON t.added_by = u1.user_id 
				LEFT JOIN users u2 ON t.last_updated_by = u2.user_id 
				ORDER BY t.date_joined DESC, t.tutor_id DESC";
		$stmt = $pdo->prepare($sql);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	// Search tutors by keyword
	function searchTutors($pdo, $keyword) {
		$searchTerm = "%" . $keyword . "%";
		$sql = "SELECT t.*, u1.username AS added_by_username, u2.username AS last_updated_by_username 
				FROM tutors t 
				LEFT JOIN users u1 ON t.added_by = u1.user_id 
				LEFT JOIN users u2 ON t.last_updated_by = u2.user_id 
				WHERE CONCAT(t.first_name, ' ', t.last_name) LIKE ? OR t.email LIKE ? 
				      OR t.specialization LIKE ? OR t.phone_number LIKE ?
				ORDER BY t.date_joined DESC, t.tutor_id DESC";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	// Get a single tutor by ID
	function getTutorByID($pdo, $tutor_id) {
		$sql = "SELECT t.*, u1.username AS added_by_username, u2.username AS last_updated_by_username 
				FROM tutors t 
				LEFT JOIN users u1 ON t.added_by = u1.user_id 
				LEFT JOIN users u2 ON t.last_updated_by = u2.user_id 
				WHERE t.tutor_id = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$tutor_id]);
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	function getAllInfoByTutorID($pdo, $tutor_id) {
		return getTutorByID($pdo, $tutor_id);
	}

	// Insert a new tutor
	function insertTutor($pdo, $firstName, $lastName, $email, $phoneNumber, $specialization, $hourlyRate, $added_by) {
		$sql = "INSERT INTO tutors (first_name, last_name, email, phone_number, specialization, hourly_rate, added_by, last_updated_by) 
				VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
		$stmt = $pdo->prepare($sql);
		$executeQuery = $stmt->execute([$firstName, $lastName, $email, $phoneNumber, $specialization, $hourlyRate, $added_by, $added_by]);
		return (bool)$executeQuery;
	}

	// Update tutor details
	function updateTutor($pdo, $tutor_id, $firstName, $lastName, $email, $phoneNumber, $specialization, $hourlyRate, $last_updated_by) {
		$sql = "UPDATE tutors 
				SET first_name = ?, last_name = ?, email = ?, phone_number = ?, specialization = ?, hourly_rate = ?,
				    last_updated_by = ?, last_updated = CURRENT_TIMESTAMP
				WHERE tutor_id = ?";
		$stmt = $pdo->prepare($sql);
		$executeQuery = $stmt->execute([$firstName, $lastName, $email, $phoneNumber, $specialization, $hourlyRate, $last_updated_by, $tutor_id]);
		return (bool)$executeQuery;
	}

	// Delete a tutor (cascades to delete their sessions)
	function deleteTutor($pdo, $tutor_id) {
		$sql = "DELETE FROM tutors WHERE tutor_id = ?";
		$stmt = $pdo->prepare($sql);
		$executeQuery = $stmt->execute([$tutor_id]);
		return (bool)$executeQuery;
	}


	// ============================================================
	// SESSIONS (CHILD TABLE) - CRUD Functions
	// ============================================================

	// Get all ACTIVE sessions for a specific tutor (excludes soft-deleted)
	function getSessionsByTutor($pdo, $tutor_id) {
		$sql = "SELECT s.*, CONCAT(t.first_name, ' ', t.last_name) AS tutor_name, 
				       u1.username AS added_by_username, u2.username AS last_updated_by_username 
				FROM sessions s 
				INNER JOIN tutors t ON s.tutor_id = t.tutor_id 
				LEFT JOIN users u1 ON s.added_by = u1.user_id 
				LEFT JOIN users u2 ON s.last_updated_by = u2.user_id 
				WHERE s.tutor_id = ? AND s.is_deleted = 0
				ORDER BY s.session_date DESC, s.start_time DESC";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$tutor_id]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	// Search ACTIVE sessions for a specific tutor by keyword
	function searchSessionsByTutor($pdo, $tutor_id, $keyword) {
		$searchTerm = "%" . $keyword . "%";
		$sql = "SELECT s.*, CONCAT(t.first_name, ' ', t.last_name) AS tutor_name, 
				       u1.username AS added_by_username, u2.username AS last_updated_by_username 
				FROM sessions s 
				INNER JOIN tutors t ON s.tutor_id = t.tutor_id 
				LEFT JOIN users u1 ON s.added_by = u1.user_id 
				LEFT JOIN users u2 ON s.last_updated_by = u2.user_id 
				WHERE s.tutor_id = ? AND s.is_deleted = 0
				      AND (s.subject_taught LIKE ? OR s.notes LIKE ? OR s.session_date LIKE ?)
				ORDER BY s.session_date DESC, s.start_time DESC";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$tutor_id, $searchTerm, $searchTerm, $searchTerm]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	// Get a single session by ID
	function getSessionByID($pdo, $session_id) {
		$sql = "SELECT s.*, CONCAT(t.first_name, ' ', t.last_name) AS tutor_name, 
				       u1.username AS added_by_username, u2.username AS last_updated_by_username,
				       u3.username AS deleted_by_username
				FROM sessions s 
				INNER JOIN tutors t ON s.tutor_id = t.tutor_id 
				LEFT JOIN users u1 ON s.added_by = u1.user_id 
				LEFT JOIN users u2 ON s.last_updated_by = u2.user_id 
				LEFT JOIN users u3 ON s.deleted_by = u3.user_id
				WHERE s.session_id = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$session_id]);
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	// Insert a new tutoring session (with added_by tracking and optional image)
	function insertSession($pdo, $tutor_id, $sessionDate, $startTime, $endTime, $subjectTaught, $notes, $added_by, $image = null) {
		$sql = "INSERT INTO sessions (tutor_id, session_date, start_time, end_time, subject_taught, notes, image, added_by, last_updated_by) 
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$stmt = $pdo->prepare($sql);
		$executeQuery = $stmt->execute([$tutor_id, $sessionDate, $startTime, $endTime, $subjectTaught, $notes, $image, $added_by, $added_by]);
		return (bool)$executeQuery;
	}

	// Update an existing tutoring session (with optional image update)
	function updateSession($pdo, $session_id, $sessionDate, $startTime, $endTime, $subjectTaught, $notes, $last_updated_by, $image = null) {
		if ($image !== null) {
			$sql = "UPDATE sessions 
					SET session_date = ?, start_time = ?, end_time = ?, subject_taught = ?, notes = ?,
					    image = ?, last_updated_by = ?, last_updated = CURRENT_TIMESTAMP
					WHERE session_id = ?";
			$stmt = $pdo->prepare($sql);
			$executeQuery = $stmt->execute([$sessionDate, $startTime, $endTime, $subjectTaught, $notes, $image, $last_updated_by, $session_id]);
		} else {
			$sql = "UPDATE sessions 
					SET session_date = ?, start_time = ?, end_time = ?, subject_taught = ?, notes = ?,
					    last_updated_by = ?, last_updated = CURRENT_TIMESTAMP
					WHERE session_id = ?";
			$stmt = $pdo->prepare($sql);
			$executeQuery = $stmt->execute([$sessionDate, $startTime, $endTime, $subjectTaught, $notes, $last_updated_by, $session_id]);
		}
		return (bool)$executeQuery;
	}

	// Soft-delete a session (marks as deleted, stores who deleted it and timestamp)
	function softDeleteSession($pdo, $session_id, $deleted_by) {
		$sql = "UPDATE sessions 
				SET is_deleted = 1, deleted_by = ?, deleted_at = CURRENT_TIMESTAMP
				WHERE session_id = ?";
		$stmt = $pdo->prepare($sql);
		$executeQuery = $stmt->execute([$deleted_by, $session_id]);
		return (bool)$executeQuery;
	}

	// Restore a soft-deleted session back to active list
	function restoreSession($pdo, $session_id) {
		$sql = "UPDATE sessions 
				SET is_deleted = 0, deleted_by = NULL, deleted_at = NULL
				WHERE session_id = ?";
		$stmt = $pdo->prepare($sql);
		$executeQuery = $stmt->execute([$session_id]);
		return (bool)$executeQuery;
	}

	// Get all DELETED sessions for a specific tutor (Recycle Bin by tutor)
	function getDeletedSessionsByTutor($pdo, $tutor_id) {
		$sql = "SELECT s.*, CONCAT(t.first_name, ' ', t.last_name) AS tutor_name, 
				       u1.username AS added_by_username, u2.username AS last_updated_by_username,
				       u3.username AS deleted_by_username
				FROM sessions s 
				INNER JOIN tutors t ON s.tutor_id = t.tutor_id 
				LEFT JOIN users u1 ON s.added_by = u1.user_id 
				LEFT JOIN users u2 ON s.last_updated_by = u2.user_id 
				LEFT JOIN users u3 ON s.deleted_by = u3.user_id
				WHERE s.tutor_id = ? AND s.is_deleted = 1
				ORDER BY s.deleted_at DESC";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$tutor_id]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	// Get ALL DELETED sessions across all tutors (Global Recycle Bin)
	function getAllDeletedSessions($pdo) {
		$sql = "SELECT s.*, CONCAT(t.first_name, ' ', t.last_name) AS tutor_name, 
				       u1.username AS added_by_username, u2.username AS last_updated_by_username,
				       u3.username AS deleted_by_username
				FROM sessions s 
				INNER JOIN tutors t ON s.tutor_id = t.tutor_id 
				LEFT JOIN users u1 ON s.added_by = u1.user_id 
				LEFT JOIN users u2 ON s.last_updated_by = u2.user_id 
				LEFT JOIN users u3 ON s.deleted_by = u3.user_id
				WHERE s.is_deleted = 1
				ORDER BY s.deleted_at DESC";
		$stmt = $pdo->prepare($sql);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	// Count total deleted sessions (for badge count)
	function countDeletedSessions($pdo, $tutor_id = null) {
		if ($tutor_id !== null) {
			$sql = "SELECT COUNT(*) FROM sessions WHERE is_deleted = 1 AND tutor_id = ?";
			$stmt = $pdo->prepare($sql);
			$stmt->execute([$tutor_id]);
		} else {
			$sql = "SELECT COUNT(*) FROM sessions WHERE is_deleted = 1";
			$stmt = $pdo->prepare($sql);
			$stmt->execute();
		}
		return (int)$stmt->fetchColumn();
	}

	// Permanently delete a session
	function deleteSession($pdo, $session_id) {
		$sql = "DELETE FROM sessions WHERE session_id = ?";
		$stmt = $pdo->prepare($sql);
		$executeQuery = $stmt->execute([$session_id]);
		return (bool)$executeQuery;
	}

?>
