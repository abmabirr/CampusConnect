<?php
require_once 'includes/helpers.php';
require_once 'includes/db.php';
require_role('Student');

$query = $conn->prepare('SELECT s.student_id, s.department_id, s.phone, s.semester, s.address, s.profile_picture, d.department_name FROM students s JOIN departments d ON d.department_id = s.department_id WHERE s.user_id = ?');
$query->bind_param('i', $_SESSION['user_id']);
$query->execute();
$profile = $query->get_result()->fetch_assoc();
$query->close();
if (!$profile) {
    http_response_code(404);
    exit('Student profile not found. Contact an administrator.');
}
$_SESSION['profile_picture'] = $profile['profile_picture'];

$message = '';
$messageType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = post_value('action');

    if ($action === 'upload_picture') {
        $upload = isset($_FILES['profile_picture']) ? $_FILES['profile_picture'] : null;
        if (!$upload || !isset($upload['error'], $upload['tmp_name'], $upload['size'])) {
            $message = 'Select a profile picture to upload.';
            $messageType = 'error';
        } elseif ($upload['error'] === UPLOAD_ERR_NO_FILE) {
            $message = 'Select a profile picture to upload.';
            $messageType = 'error';
        } elseif ($upload['error'] !== UPLOAD_ERR_OK) {
            $message = $upload['error'] === UPLOAD_ERR_INI_SIZE || $upload['error'] === UPLOAD_ERR_FORM_SIZE
                ? 'Image is too large. Maximum file size is 5 MB.'
                : 'The image upload failed. Please choose another file and try again.';
            $messageType = 'error';
        } elseif (!is_uploaded_file($upload['tmp_name']) || $upload['size'] < 1 || $upload['size'] > 5 * 1024 * 1024) {
            $message = 'Choose a valid image up to 5 MB.';
            $messageType = 'error';
        } else {
            $imageInfo = getimagesize($upload['tmp_name']);
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($upload['tmp_name']);
            $allowedTypes = array(
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            );

            if (!$imageInfo || !isset($allowedTypes[$mimeType]) || $imageInfo['mime'] !== $mimeType) {
                $message = 'Use a valid JPEG, PNG, or WebP image.';
                $messageType = 'error';
            } elseif ($imageInfo[0] < 1 || $imageInfo[1] < 1 || $imageInfo[0] > 4096 || $imageInfo[1] > 4096) {
                $message = 'Image dimensions must not exceed 4096 × 4096 pixels.';
                $messageType = 'error';
            } else {
                $uploadDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profile_pictures';
                if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                    error_log('CampusConnect profile upload directory could not be created.');
                    $message = 'Profile pictures are temporarily unavailable. Please try again later.';
                    $messageType = 'error';
                } else {
                    $newPicture = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mimeType];
                    $destination = $uploadDirectory . DIRECTORY_SEPARATOR . $newPicture;
                    if (!move_uploaded_file($upload['tmp_name'], $destination)) {
                        error_log('CampusConnect profile picture could not be moved to its destination.');
                        $message = 'The image could not be saved. Please try again.';
                        $messageType = 'error';
                    } else {
                        try {
                            $update = $conn->prepare('UPDATE students SET profile_picture = ? WHERE user_id = ?');
                            $update->bind_param('si', $newPicture, $_SESSION['user_id']);
                            $update->execute();
                            $oldPicture = $profile['profile_picture'];
                            $profile['profile_picture'] = $newPicture;
                            $_SESSION['profile_picture'] = $newPicture;
                            if (is_string($oldPicture) && preg_match('/\A[a-f0-9]{32}\.(?:jpg|png|webp)\z/D', $oldPicture)) {
                                $oldPath = $uploadDirectory . DIRECTORY_SEPARATOR . $oldPicture;
                                if (is_file($oldPath) && !unlink($oldPath)) {
                                    error_log('CampusConnect could not remove a replaced profile picture.');
                                }
                            }
                            $message = 'Profile picture updated successfully.';
                            $messageType = 'success';
                        } catch (Throwable $exception) {
                            if (is_file($destination) && !unlink($destination)) {
                                error_log('CampusConnect could not clean up an unlinked profile picture.');
                            }
                            error_log('CampusConnect profile picture update failed: ' . $exception->getMessage());
                            $message = 'Profile picture could not be saved. Please try again.';
                            $messageType = 'error';
                        }
                    }
                }
            }
        }
    } elseif ($action === 'remove_picture') {
        if (empty($profile['profile_picture'])) {
            $message = 'There is no profile picture to remove.';
            $messageType = 'error';
        } else {
            try {
                $oldPicture = $profile['profile_picture'];
                $remove = $conn->prepare('UPDATE students SET profile_picture = NULL WHERE user_id = ?');
                $remove->bind_param('i', $_SESSION['user_id']);
                $remove->execute();
                $profile['profile_picture'] = null;
                $_SESSION['profile_picture'] = null;
                $uploadDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profile_pictures';
                if (is_string($oldPicture) && preg_match('/\A[a-f0-9]{32}\.(?:jpg|png|webp)\z/D', $oldPicture)) {
                    $oldPath = $uploadDirectory . DIRECTORY_SEPARATOR . $oldPicture;
                    if (is_file($oldPath) && !unlink($oldPath)) {
                        error_log('CampusConnect could not remove the profile picture file.');
                    }
                }
                $message = 'Profile picture removed.';
                $messageType = 'success';
            } catch (Throwable $exception) {
                error_log('CampusConnect profile picture removal failed: ' . $exception->getMessage());
                $message = 'Profile picture could not be removed. Please try again.';
                $messageType = 'error';
            }
        }
    } elseif ($action === 'update_profile') {
        $name = post_value('name');
        $phone = post_value('phone');
        $semester = post_value('semester');
        $address = post_value('address');
        if ($name === '' || $phone === '' || $semester === '' || $address === '') {
            $message = 'Please complete every field.';
            $messageType = 'error';
        } else {
            $conn->begin_transaction();
            try {
                $updateUser = $conn->prepare('UPDATE users SET name = ? WHERE user_id = ?');
                $updateUser->bind_param('si', $name, $_SESSION['user_id']);
                $updateUser->execute();
                $updateStudent = $conn->prepare('UPDATE students SET phone = ?, semester = ?, address = ? WHERE user_id = ?');
                $updateStudent->bind_param('sssi', $phone, $semester, $address, $_SESSION['user_id']);
                $updateStudent->execute();
                $conn->commit();
                $_SESSION['name'] = $name;
                $profile['phone'] = $phone;
                $profile['semester'] = $semester;
                $profile['address'] = $address;
                $message = 'Profile updated successfully.';
                $messageType = 'success';
            } catch (Throwable $exception) {
                $conn->rollback();
                error_log('CampusConnect profile update failed: ' . $exception->getMessage());
                $message = 'Profile could not be updated. Please try again.';
                $messageType = 'error';
            }
        }
    } else {
        $message = 'Choose a valid profile action.';
        $messageType = 'error';
    }
}

page_header('My Profile');
?>
<section class="content-panel narrow-panel">
    <p class="eyebrow">Personal information</p><h2>Manage your profile</h2>
    <p class="muted">Your email and department are managed by the university.</p>
    <?php if ($message !== '') { ?><p class="form-message <?php echo e($messageType); ?>"><?php echo e($message); ?></p><?php } ?>
    <div class="profile-picture-section">
        <div class="profile-picture-preview">
            <?php
            $initial = function_exists('mb_substr') ? mb_substr($_SESSION['name'], 0, 1, 'UTF-8') : substr($_SESSION['name'], 0, 1);
            if (!empty($profile['profile_picture'])) {
                echo '<img src="' . e(profile_picture_url()) . '" alt="Your profile picture">';
            } else {
                echo '<span>' . e($initial) . '</span>';
            }
            ?>
        </div>
        <div class="profile-picture-copy">
            <h3>Profile picture</h3>
            <p>Add a face to your campus profile. JPEG, PNG, or WebP, up to 5 MB.</p>
            <form method="post" enctype="multipart/form-data" class="profile-picture-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="upload_picture">
                <label class="button button-small" for="profile_picture">Choose photo</label>
                <input id="profile_picture" type="file" name="profile_picture" accept="image/jpeg,image/png,image/webp" required>
                <span class="selected-picture-name" aria-live="polite">No photo selected</span>
                <button class="button button-small" type="submit">Upload photo</button>
            </form>
            <?php if (!empty($profile['profile_picture'])) { ?>
                <form method="post" class="remove-picture-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="remove_picture">
                    <button class="button button-small button-muted" type="submit">Remove photo</button>
                </form>
            <?php } ?>
        </div>
    </div>
    <form method="post" class="form-grid">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="update_profile">
        <div class="form-group"><label for="name">Full name</label><input id="name" name="name" maxlength="120" value="<?php echo e(isset($_POST['name']) ? post_value('name') : $_SESSION['name']); ?>" required></div>
        <div class="form-group"><label>Email</label><input value="<?php echo e($_SESSION['email']); ?>" disabled></div>
        <div class="form-group"><label>Department</label><input value="<?php echo e($profile['department_name']); ?>" disabled></div>
        <div class="form-group"><label for="phone">Phone</label><input id="phone" name="phone" maxlength="30" value="<?php echo e(isset($_POST['phone']) ? post_value('phone') : $profile['phone']); ?>" required></div>
        <div class="form-group"><label for="semester">Semester</label><input id="semester" name="semester" maxlength="30" value="<?php echo e(isset($_POST['semester']) ? post_value('semester') : $profile['semester']); ?>" required></div>
        <div class="form-group full-width"><label for="address">Address</label><input id="address" name="address" maxlength="255" value="<?php echo e(isset($_POST['address']) ? post_value('address') : $profile['address']); ?>" required></div>
        <button class="button" type="submit">Save changes</button>
    </form>
</section>
<script>
document.getElementById('profile_picture').addEventListener('change', function () {
    var file = this.files.length ? this.files[0] : null;
    var preview = document.querySelector('.profile-picture-preview');
    var image = preview.querySelector('img');
    var objectUrl;

    document.querySelector('.selected-picture-name').textContent = file ? file.name : 'No photo selected';
    if (!file || !file.type.match(/^image\/(jpeg|png|webp)$/)) {
        return;
    }

    objectUrl = URL.createObjectURL(file);
    if (!image) {
        image = document.createElement('img');
        image.alt = 'Selected profile picture preview';
        preview.replaceChildren(image);
    } else if (image.dataset.previewUrl) {
        URL.revokeObjectURL(image.dataset.previewUrl);
    }
    image.src = objectUrl;
    image.dataset.previewUrl = objectUrl;
});
</script>
<?php page_footer(); ?>
