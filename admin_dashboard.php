<?php
require_once 'includes/helpers.php';
require_once 'includes/db.php';
require_role('Admin');

$adminQuery = $conn->prepare('SELECT admin_id FROM admins WHERE user_id = ?');
$adminQuery->bind_param('i', $_SESSION['user_id']);
$adminQuery->execute();
$admin = $adminQuery->get_result()->fetch_assoc();
$adminQuery->close();
if (!$admin) {
    http_response_code(403);
    exit('Administrator profile is missing. Add an administrator record before continuing.');
}
$adminId = (int) $admin['admin_id'];
$message = '';
$messageType = '';
$transactionOpen = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = post_value('action');
    try {
        if ($action === 'create_notice') {
            $title = post_value('title');
            $description = post_value('description');
            if ($title === '' || strlen($title) > 180 || $description === '' || strlen($description) > 10000) {
                throw new InvalidArgumentException('Enter a title and description (up to 10,000 characters).');
            }
            $insert = $conn->prepare("INSERT INTO notices (admin_id, title, description, publish_date, status) VALUES (?, ?, ?, CURRENT_DATE(), 'Published')");
            $insert->bind_param('iss', $adminId, $title, $description);
            $insert->execute();
            $message = 'Notice published.';
            $messageType = 'success';
        } elseif ($action === 'archive_notice' || $action === 'publish_notice') {
            $noticeId = filter_var(post_value('notice_id'), FILTER_VALIDATE_INT);
            if (!$noticeId) {
                throw new InvalidArgumentException('Select a valid notice.');
            }
            $exists = $conn->prepare('SELECT notice_id FROM notices WHERE notice_id = ?');
            $exists->bind_param('i', $noticeId);
            $exists->execute();
            $noticeExists = $exists->get_result()->num_rows === 1;
            $exists->close();
            if (!$noticeExists) {
                throw new InvalidArgumentException('That notice was not found.');
            }
            $newStatus = $action === 'archive_notice' ? 'Archived' : 'Published';
            $update = $conn->prepare('UPDATE notices SET status = ? WHERE notice_id = ?');
            $update->bind_param('si', $newStatus, $noticeId);
            $update->execute();
            $message = 'Notice status updated.';
            $messageType = 'success';
        } elseif ($action === 'update_complaint') {
            $complaintId = filter_var(post_value('complaint_id'), FILTER_VALIDATE_INT);
            $status = post_value('status');
            $response = post_value('response');
            if (!$complaintId || !in_array($status, array('Pending', 'In Progress', 'Resolved', 'Rejected'), true) || strlen($response) > 10000) {
                throw new InvalidArgumentException('Provide a valid complaint status and response.');
            }
            $exists = $conn->prepare('SELECT complaint_id FROM complaints WHERE complaint_id = ?');
            $exists->bind_param('i', $complaintId);
            $exists->execute();
            $complaintExists = $exists->get_result()->num_rows === 1;
            $exists->close();
            if (!$complaintExists) {
                throw new InvalidArgumentException('That complaint was not found.');
            }
            $update = $conn->prepare('UPDATE complaints SET status = ?, response = ? WHERE complaint_id = ?');
            $update->bind_param('ssi', $status, $response, $complaintId);
            $update->execute();
            $message = 'Complaint updated.';
            $messageType = 'success';
        } elseif ($action === 'update_booking') {
            $bookingId = filter_var(post_value('booking_id'), FILTER_VALIDATE_INT);
            $status = post_value('status');
            if (!$bookingId || !in_array($status, array('Pending', 'Approved', 'Rejected', 'Completed'), true)) {
                throw new InvalidArgumentException('Select a valid lab booking and status.');
            }
            $bookingQuery = $conn->prepare('SELECT b.lab_id, b.booking_date, b.start_time, b.end_time, l.availability_status FROM lab_bookings b JOIN labs l ON l.lab_id = b.lab_id WHERE b.booking_id = ?');
            $bookingQuery->bind_param('i', $bookingId);
            $bookingQuery->execute();
            $booking = $bookingQuery->get_result()->fetch_assoc();
            $bookingQuery->close();
            if (!$booking) {
                throw new InvalidArgumentException('That lab booking was not found.');
            }
            if ($status === 'Approved') {
                if ($booking['availability_status'] !== 'Available') {
                    throw new InvalidArgumentException('This lab is currently unavailable and cannot be approved.');
                }
                $overlap = $conn->prepare("SELECT booking_id FROM lab_bookings WHERE lab_id = ? AND booking_date = ? AND status = 'Approved' AND booking_id <> ? AND start_time < ? AND end_time > ? LIMIT 1");
                $overlap->bind_param('isiss', $booking['lab_id'], $booking['booking_date'], $bookingId, $booking['end_time'], $booking['start_time']);
                $overlap->execute();
                $slotTaken = $overlap->get_result()->num_rows > 0;
                $overlap->close();
                if ($slotTaken) {
                    throw new InvalidArgumentException('Another booking is already approved for an overlapping time.');
                }
            }
            $update = $conn->prepare('UPDATE lab_bookings SET status = ? WHERE booking_id = ?');
            $update->bind_param('si', $status, $bookingId);
            $update->execute();
            $message = 'Lab booking status updated.';
            $messageType = 'success';
        } elseif ($action === 'create_teacher') {
            $name = post_value('name');
            $email = strtolower(post_value('email'));
            $departmentId = filter_var(post_value('department_id'), FILTER_VALIDATE_INT);
            $phone = post_value('phone');
            $designation = post_value('designation');
            $room = post_value('room_no');
            $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$departmentId || $phone === '' || $designation === '' || $room === '' || strlen($password) < 8) {
                throw new InvalidArgumentException('Complete all teacher fields and use a password with at least 8 characters.');
            }
            $conn->begin_transaction();
            $transactionOpen = true;
            $role = 'Teacher';
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insertUser = $conn->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
            $insertUser->bind_param('ssss', $name, $email, $hash, $role);
            $insertUser->execute();
            $userId = $conn->insert_id;
            $insertTeacher = $conn->prepare('INSERT INTO teachers (user_id, department_id, phone, designation, room_no) VALUES (?, ?, ?, ?, ?)');
            $insertTeacher->bind_param('iisss', $userId, $departmentId, $phone, $designation, $room);
            $insertTeacher->execute();
            $conn->commit();
            $transactionOpen = false;
            $message = 'Teacher account created. Share the login credentials with the teacher securely.';
            $messageType = 'success';
        } elseif ($action === 'create_lab') {
            $name = post_value('lab_name');
            $location = post_value('lab_location');
            $capacity = filter_var(post_value('capacity'), FILTER_VALIDATE_INT);
            if ($name === '' || strlen($name) > 120 || $location === '' || strlen($location) > 180 || !$capacity || $capacity > 65535) {
                throw new InvalidArgumentException('Enter a valid lab name, location, and capacity.');
            }
            $insert = $conn->prepare("INSERT INTO labs (lab_name, lab_location, capacity, availability_status) VALUES (?, ?, ?, 'Available')");
            $insert->bind_param('ssi', $name, $location, $capacity);
            $insert->execute();
            $message = 'Laboratory added.';
            $messageType = 'success';
        } elseif ($action === 'toggle_lab') {
            $labId = filter_var(post_value('lab_id'), FILTER_VALIDATE_INT);
            $status = post_value('availability_status');
            if (!$labId || !in_array($status, array('Available', 'Unavailable'), true)) {
                throw new InvalidArgumentException('Select a valid laboratory status.');
            }
            $update = $conn->prepare('UPDATE labs SET availability_status = ? WHERE lab_id = ?');
            $update->bind_param('si', $status, $labId);
            $update->execute();
            $message = 'Laboratory availability updated.';
            $messageType = 'success';
        } else {
            throw new InvalidArgumentException('Unknown action.');
        }
    } catch (InvalidArgumentException $exception) {
        if ($transactionOpen) {
            $conn->rollback();
            $transactionOpen = false;
        }
        $message = $exception->getMessage();
        $messageType = 'error';
    } catch (Throwable $exception) {
        if ($transactionOpen) {
            $conn->rollback();
            $transactionOpen = false;
        }
        error_log('CampusConnect admin action failed: ' . $exception->getMessage());
        $message = 'The action could not be completed. Verify the supplied details and try again.';
        $messageType = 'error';
    }
}

$departments = $conn->query('SELECT department_id, department_name FROM departments ORDER BY department_name');
$notices = $conn->query('SELECT notice_id, title, publish_date, status FROM notices ORDER BY publish_date DESC LIMIT 50');
$complaints = $conn->query('SELECT c.complaint_id, c.subject, c.description, c.complaint_date, c.status, c.response, u.name AS student_name, u.email FROM complaints c JOIN students s ON s.student_id = c.student_id JOIN users u ON u.user_id = s.user_id ORDER BY c.complaint_date DESC');
$bookings = $conn->query('SELECT b.booking_id, b.booking_date, b.start_time, b.end_time, b.purpose, b.status, l.lab_name, l.lab_location, u.name AS student_name, u.email FROM lab_bookings b JOIN labs l ON l.lab_id = b.lab_id JOIN students s ON s.student_id = b.student_id JOIN users u ON u.user_id = s.user_id ORDER BY b.booking_date DESC, b.start_time DESC');
$users = $conn->query('SELECT u.name, u.email, u.role, u.user_id, d.department_name, t.designation FROM users u LEFT JOIN students s ON s.user_id = u.user_id LEFT JOIN teachers t ON t.user_id = u.user_id LEFT JOIN departments d ON d.department_id = COALESCE(s.department_id, t.department_id) ORDER BY u.user_id DESC LIMIT 100');
$labs = $conn->query('SELECT lab_id, lab_name, lab_location, capacity, availability_status FROM labs ORDER BY lab_name');
$stats = array();
foreach (array('users', 'notices', 'complaints', 'appointments') as $table) {
    $stats[$table] = (int) $conn->query("SELECT COUNT(*) AS total FROM {$table}")->fetch_assoc()['total'];
}
page_header('Admin Dashboard');
?>
<section class="welcome-section"><div><p class="eyebrow light">System administration</p><h2>Welcome, <?php echo e($_SESSION['name']); ?></h2><p>Manage campus information and review student service requests.</p></div></section>
<?php if ($message !== '') { ?><p class="form-message <?php echo e($messageType); ?>"><?php echo e($message); ?></p><?php } ?>
<section class="stats-grid">
    <article class="stat-card"><span>Accounts</span><strong><?php echo $stats['users']; ?></strong></article>
    <article class="stat-card"><span>Notices</span><strong><?php echo $stats['notices']; ?></strong></article>
    <article class="stat-card"><span>Complaints</span><strong><?php echo $stats['complaints']; ?></strong></article>
    <article class="stat-card"><span>Appointments</span><strong><?php echo $stats['appointments']; ?></strong></article>
</section>
<section id="notices" class="content-panel">
    <div class="section-heading"><div><p class="eyebrow">Campus communication</p><h2>Publish a notice</h2></div></div>
    <form method="post" class="form-grid single-column">
        <?php echo csrf_field(); ?><input type="hidden" name="action" value="create_notice">
        <div class="form-group"><label for="title">Title</label><input id="title" name="title" maxlength="180" required></div>
        <div class="form-group"><label for="description">Notice details</label><textarea id="description" name="description" rows="4" maxlength="10000" required></textarea></div>
        <button class="button" type="submit">Publish notice</button>
    </form>
    <h3 class="subheading">Recent notices</h3>
    <?php if ($notices->num_rows === 0) { ?><p class="empty-state">No notices yet.</p><?php } ?>
    <?php while ($notice = $notices->fetch_assoc()) { ?>
        <article class="compact-row"><div><strong><?php echo e($notice['title']); ?></strong><span><?php echo e(date('M j, Y', strtotime($notice['publish_date']))); ?> · <?php echo e($notice['status']); ?></span></div><form method="post"><?php echo csrf_field(); ?><input type="hidden" name="notice_id" value="<?php echo (int) $notice['notice_id']; ?>"><input type="hidden" name="action" value="<?php echo $notice['status'] === 'Published' ? 'archive_notice' : 'publish_notice'; ?>"><button class="button button-small button-muted" type="submit"><?php echo $notice['status'] === 'Published' ? 'Archive' : 'Publish'; ?></button></form></article>
    <?php } ?>
</section>
<section id="complaints" class="content-panel">
    <div class="section-heading"><div><p class="eyebrow">Student support</p><h2>Manage complaints</h2></div></div>
    <?php if ($complaints->num_rows === 0) { ?><p class="empty-state">No complaints have been submitted.</p><?php } ?>
    <?php while ($complaint = $complaints->fetch_assoc()) { ?>
        <article class="request-card"><div class="request-heading"><div><h3><?php echo e($complaint['subject']); ?></h3><time><?php echo e($complaint['student_name'] . ' · ' . $complaint['email'] . ' · ' . date('M j, Y', strtotime($complaint['complaint_date']))); ?></time></div><span class="status status-<?php echo e(strtolower(str_replace(' ', '-', $complaint['status']))); ?>"><?php echo e($complaint['status']); ?></span></div><p><?php echo nl2br(e($complaint['description'])); ?></p>
            <form method="post" class="form-grid admin-complaint-form"><?php echo csrf_field(); ?><input type="hidden" name="action" value="update_complaint"><input type="hidden" name="complaint_id" value="<?php echo (int) $complaint['complaint_id']; ?>">
                <div class="form-group"><label>Status</label><select name="status"><?php foreach (array('Pending', 'In Progress', 'Resolved', 'Rejected') as $status) { ?><option value="<?php echo e($status); ?>" <?php echo $complaint['status'] === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option><?php } ?></select></div>
                <div class="form-group"><label>Response to student</label><textarea name="response" rows="2" maxlength="10000"><?php echo e($complaint['response']); ?></textarea></div><button class="button button-small" type="submit">Save response</button>
            </form>
        </article>
    <?php } ?>
</section>
<section id="users" class="content-panel">
    <div class="section-heading"><div><p class="eyebrow">Faculty directory</p><h2>Create a teacher account</h2></div></div>
    <form method="post" class="form-grid">
        <?php echo csrf_field(); ?><input type="hidden" name="action" value="create_teacher">
        <div class="form-group"><label>Name</label><input name="name" maxlength="120" required></div><div class="form-group"><label>Email</label><input type="email" name="email" maxlength="190" required></div>
        <div class="form-group"><label>Department</label><select name="department_id" required><option value="">Select department</option><?php while ($department = $departments->fetch_assoc()) { ?><option value="<?php echo (int) $department['department_id']; ?>"><?php echo e($department['department_name']); ?></option><?php } ?></select></div>
        <div class="form-group"><label>Phone</label><input name="phone" maxlength="30" required></div><div class="form-group"><label>Designation</label><input name="designation" maxlength="100" placeholder="e.g. Lecturer" required></div>
        <div class="form-group"><label>Room number</label><input name="room_no" maxlength="40" required></div><div class="form-group"><label>Temporary password</label><input type="password" name="password" minlength="8" autocomplete="new-password" required></div>
        <button class="button" type="submit">Create teacher</button>
    </form>
    <h3 class="subheading">User directory (latest 100)</h3>
    <div class="table-wrap"><table><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Department</th><th>Designation</th><th>Joined</th></tr></thead><tbody>
        <?php while ($user = $users->fetch_assoc()) { ?><tr><td><?php echo e($user['name']); ?></td><td><?php echo e($user['email']); ?></td><td><?php echo e($user['role']); ?></td><td><?php echo e($user['department_name'] ?: '—'); ?></td><td><?php echo e($user['designation'] ?: '—'); ?></td><td>#<?php echo (int) $user['user_id']; ?></td></tr><?php } ?>
    </tbody></table></div>
</section>
<section class="content-panel">
    <div class="section-heading"><div><p class="eyebrow">Facility directory</p><h2>Manage laboratories</h2></div></div>
    <form method="post" class="form-grid">
        <?php echo csrf_field(); ?><input type="hidden" name="action" value="create_lab">
        <div class="form-group"><label>Lab name</label><input name="lab_name" maxlength="120" required></div><div class="form-group"><label>Location</label><input name="lab_location" maxlength="180" required></div>
        <div class="form-group"><label>Capacity</label><input type="number" name="capacity" min="1" max="65535" required></div><button class="button" type="submit">Add lab</button>
    </form>
    <?php while ($lab = $labs->fetch_assoc()) { ?>
        <article class="compact-row"><div><strong><?php echo e($lab['lab_name']); ?></strong><span><?php echo e($lab['lab_location'] . ' · capacity ' . $lab['capacity'] . ' · ' . $lab['availability_status']); ?></span></div>
            <form method="post"><?php echo csrf_field(); ?><input type="hidden" name="action" value="toggle_lab"><input type="hidden" name="lab_id" value="<?php echo (int) $lab['lab_id']; ?>"><input type="hidden" name="availability_status" value="<?php echo $lab['availability_status'] === 'Available' ? 'Unavailable' : 'Available'; ?>"><button class="button button-small button-muted" type="submit"><?php echo $lab['availability_status'] === 'Available' ? 'Make unavailable' : 'Make available'; ?></button></form></article>
    <?php } ?>
    <h3 class="subheading">Lab booking requests</h3>
    <?php if ($bookings->num_rows === 0) { ?><p class="empty-state">No lab booking requests yet.</p><?php } ?>
    <?php while ($booking = $bookings->fetch_assoc()) { ?>
        <article class="request-card"><div class="request-heading"><div><h3><?php echo e($booking['lab_name'] . ' · ' . $booking['student_name']); ?></h3><time><?php echo e($booking['student_email'] . ' · ' . $booking['lab_location'] . ' · ' . date('M j, Y', strtotime($booking['booking_date'])) . ' · ' . date('g:i A', strtotime($booking['start_time'])) . '–' . date('g:i A', strtotime($booking['end_time']))); ?></time></div><span class="status status-<?php echo e(strtolower($booking['status'])); ?>"><?php echo e($booking['status']); ?></span></div>
            <p><?php echo nl2br(e($booking['purpose'])); ?></p>
            <?php if (in_array($booking['status'], array('Pending', 'Approved'), true)) { ?><form method="post" class="inline-actions"><?php echo csrf_field(); ?><input type="hidden" name="action" value="update_booking"><input type="hidden" name="booking_id" value="<?php echo (int) $booking['booking_id']; ?>"><button class="button button-small" name="status" value="Approved">Approve</button><button class="button button-small button-muted" name="status" value="Rejected">Reject</button><?php if ($booking['status'] === 'Approved') { ?><button class="button button-small button-muted" name="status" value="Completed">Mark completed</button><?php } ?></form><?php } ?>
        </article>
    <?php } ?>
</section>
<?php page_footer(); ?>
