<?php
require_once 'includes/helpers.php';
require_once 'includes/db.php';
require_role('Student');

$profileQuery = $conn->prepare('SELECT s.student_id, s.semester, d.department_name FROM students s JOIN departments d ON d.department_id = s.department_id WHERE s.user_id = ?');
$profileQuery->bind_param('i', $_SESSION['user_id']);
$profileQuery->execute();
$profile = $profileQuery->get_result()->fetch_assoc();
$profileQuery->close();
if (!$profile) {
    http_response_code(404);
    exit('Student profile not found. Contact an administrator.');
}

$studentId = (int) $profile['student_id'];
$counts = array('complaints' => 0, 'appointments' => 0, 'bookings' => 0);
foreach (array('complaints' => 'complaints', 'appointments' => 'appointments', 'bookings' => 'lab_bookings') as $key => $table) {
    $countQuery = $conn->prepare("SELECT COUNT(*) AS total FROM {$table} WHERE student_id = ?");
    $countQuery->bind_param('i', $studentId);
    $countQuery->execute();
    $counts[$key] = (int) $countQuery->get_result()->fetch_assoc()['total'];
    $countQuery->close();
}

$notices = $conn->query("SELECT title, description, publish_date FROM notices WHERE status = 'Published' ORDER BY publish_date DESC LIMIT 3");
page_header('Student Dashboard');
?>
<section class="welcome-section dashboard-welcome">
    <div><p class="eyebrow light">Your campus, connected</p><h2>Welcome back, <?php echo e($_SESSION['name']); ?>!</h2>
        <p>Access university services and keep track of your requests in one place.</p></div>
    <a class="button button-light" href="student_profile.php">View profile <span aria-hidden="true">↗</span></a>
</section>
<section class="stats-grid dashboard-stats">
    <article class="stat-card"><span class="stat-icon stat-violet" aria-hidden="true">✳</span><span class="stat-label">My complaints</span><strong><?php echo $counts['complaints']; ?></strong><a href="complaints.php">Track your requests <span aria-hidden="true">→</span></a></article>
    <article class="stat-card"><span class="stat-icon stat-blue" aria-hidden="true">◷</span><span class="stat-label">Appointments</span><strong><?php echo $counts['appointments']; ?></strong><a href="appointments.php">Manage appointments <span aria-hidden="true">→</span></a></article>
    <article class="stat-card"><span class="stat-icon stat-mint" aria-hidden="true">⌘</span><span class="stat-label">Lab bookings</span><strong><?php echo $counts['bookings']; ?></strong><a href="labs.php">Manage bookings <span aria-hidden="true">→</span></a></article>
</section>
<section class="content-panel">
    <div class="section-heading"><div><p class="eyebrow">Campus updates</p><h2>Latest notices</h2></div><a class="text-link" href="notices.php">All notices</a></div>
    <?php if ($notices->num_rows === 0) { ?><p class="empty-state">No notices have been published yet.</p><?php } ?>
    <?php while ($notice = $notices->fetch_assoc()) { ?>
        <article class="notice-preview"><div><h3><?php echo e($notice['title']); ?></h3><time><?php echo e(date('M j, Y', strtotime($notice['publish_date']))); ?></time></div><p><?php echo nl2br(e($notice['description'])); ?></p></article>
    <?php } ?>
</section>
<section class="content-panel">
    <div class="section-heading"><div><p class="eyebrow">Student information</p><h2>My account</h2></div><a class="text-link" href="student_profile.php">Edit profile</a></div>
    <div class="detail-grid"><p><span>Name</span><strong><?php echo e($_SESSION['name']); ?></strong></p><p><span>Email</span><strong><?php echo e($_SESSION['email']); ?></strong></p><p><span>Department</span><strong><?php echo e($profile['department_name']); ?></strong></p><p><span>Semester</span><strong><?php echo e($profile['semester']); ?></strong></p></div>
</section>
<?php page_footer(); ?>
