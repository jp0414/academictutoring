<?php
require_once __DIR__ . '/core/dbConfig.php';
require_once __DIR__ . '/core/ui.php';
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
pageStart('Create account', 'auth');
?>
<section class="auth-card" aria-labelledby="auth-title">
    <h1 id="auth-title">Create an account</h1>
    <p class="auth-intro">Register for the tutoring workspace.</p>
    <?php
    $errors = ['username_taken' => 'That username is taken. Choose a different username.', 'email_taken' => 'This email is already registered. Log in or use a different email.', 'failed' => 'Your account could not be created. Please try again.'];
    $error = $_GET['error'] ?? '';
    if (isset($errors[$error])) notice($errors[$error], true);
    ?>
    <form action="core/handleForms.php" method="post" class="auth-form">
        <?php csrfField(); ?>
        <div class="fields">
            <label>First name<input name="firstName" maxlength="100" autocomplete="given-name" required></label>
            <label>Last name<input name="lastName" maxlength="100" autocomplete="family-name" required></label>
            <label class="span-all">Username<input name="username" maxlength="100" autocomplete="username" autocapitalize="none" required></label>
            <label class="span-all">Email<input type="email" name="email" maxlength="150" autocomplete="email" required></label>
            <label class="span-all">Password<input type="password" name="password" autocomplete="new-password" required></label>
        </div>
        <details class="optional-fields"><summary>Optional profile details</summary><div class="fields">
            <label class="span-all">Address<input name="address" maxlength="255" autocomplete="street-address"></label>
            <label>Age<input type="number" name="age" min="1" max="120"></label>
            <label>Phone number<input type="tel" name="phoneNumber" maxlength="50" autocomplete="tel"></label>
        </div></details>
        <button class="button primary" name="registerBtn">Create account</button>
    </form>
    <p class="auth-footer">Already have an account? <a href="login.php">Log in</a></p>
</section>
<?php pageEnd();