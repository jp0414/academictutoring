<?php
require_once __DIR__ . '/core/dbConfig.php';
require_once __DIR__ . '/core/ui.php';
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
pageStart('Log in', 'auth');
?>
<section class="auth-card" aria-labelledby="auth-title">
    <h1 id="auth-title">Welcome back</h1>
    <p class="auth-intro">Log in to your tutoring workspace.</p>
    <?php
    if (($_GET['error'] ?? '') === 'expired') notice('Your form expired. Please log in again.', true);
    if (($_GET['error'] ?? '') === 'invalid') notice('The username or password was incorrect. Try again.', true);
    if (($_GET['register'] ?? '') === 'success') notice('Account created. You can now log in.');
    if (($_GET['logout'] ?? '') === 'success') notice('You’ve been logged out.');
    ?>
    <form action="core/handleForms.php" method="post" class="auth-form">
        <?php csrfField(); ?>
        <label>Username<input name="username" autocomplete="username" autocapitalize="none" required></label>
        <label>Password<input type="password" name="password" autocomplete="current-password" required></label>
        <button class="button primary" name="loginBtn">Log in</button>
    </form>
    <p class="auth-footer">New to the workspace? <a href="register.php">Create an account</a></p>
</section>
<?php pageEnd();