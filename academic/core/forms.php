<?php
function tutorForm($tutor = []) {
    $editing = isset($tutor['tutor_id']);
    $action = 'core/handleForms.php' . ($editing ? '?tutor_id=' . (int)$tutor['tutor_id'] : ''); ?>
    <?php formError(); ?>
    <form class="editor" action="<?= h($action) ?>" method="post">
        <?php csrfField(); ?>
        <fieldset><legend>Tutor information</legend>
            <div class="fields">
                <label>First name<input name="firstName" maxlength="100" value="<?= h($tutor['first_name'] ?? '') ?>" required></label>
                <label>Last name<input name="lastName" maxlength="100" value="<?= h($tutor['last_name'] ?? '') ?>" required></label>
                <label>Email<input type="email" name="email" maxlength="150" value="<?= h($tutor['email'] ?? '') ?>" required></label>
                <label>Phone number <small>(optional)</small><input type="tel" name="phoneNumber" maxlength="50" value="<?= h($tutor['phone_number'] ?? '') ?>"></label>
                <label>Specialization<input name="specialization" maxlength="255" placeholder="e.g. Mathematics" value="<?= h($tutor['specialization'] ?? '') ?>" required></label>
                <label>Hourly rate (PHP)<input type="number" name="hourlyRate" step="0.01" min="0" inputmode="decimal" value="<?= h($tutor['hourly_rate'] ?? '') ?>" required></label>
            </div>
        </fieldset>
        <div class="form-actions"><button class="button primary" name="<?= $editing ? 'editTutorBtn' : 'insertTutorBtn' ?>"><?= $editing ? 'Save changes' : 'Add tutor' ?></button><a href="index.php">Cancel</a></div>
    </form>
<?php }

function sessionForm($tutorId, $session = []) {
    $editing = isset($session['session_id']);
    $back = 'viewtutors.php?tutor_id=' . (int)$tutorId;
    $action = 'core/handleForms.php?tutor_id=' . (int)$tutorId . ($editing ? '&session_id=' . (int)$session['session_id'] : ''); ?>
    <?php formError(); ?>
    <form class="editor" action="<?= h($action) ?>" method="post" enctype="multipart/form-data">
        <?php csrfField(); ?>
        <fieldset><legend>Session information</legend>
            <div class="fields">
                <label>Subject<input name="subjectTaught" maxlength="200" value="<?= h($session['subject_taught'] ?? '') ?>" required></label>
                <label>Session date<input type="date" name="sessionDate" value="<?= h($session['session_date'] ?? '') ?>" required></label>
                <label>Start time<input type="time" name="startTime" step="1" value="<?= h($session['start_time'] ?? '') ?>" required></label>
                <label>End time<input type="time" name="endTime" step="1" value="<?= h($session['end_time'] ?? '') ?>" required></label>
                <label class="span-all">Notes <small>(optional)</small><textarea rows="3" name="notes"><?= h($session['notes'] ?? '') ?></textarea></label>
                <div class="span-all">
                    <?php sessionImage($session); ?>
                    <label><?= empty($session['image']) ? 'Session image' : 'Replace image' ?> <small>(optional)</small>
                        <input type="file" name="sessionImage" accept=".jpg,.jpeg,.png,.gif,.webp" aria-describedby="image-types">
                    </label>
                    <small id="image-types">JPG, PNG, GIF or WebP, up to 5 MB</small>
                </div>
            </div>
        </fieldset>
        <div class="form-actions"><button class="button primary" name="<?= $editing ? 'editSessionBtn' : 'insertSessionBtn' ?>"><?= $editing ? 'Save changes' : 'Add session' ?></button><a href="<?= h($back) ?>">Cancel</a></div>
    </form>
<?php }