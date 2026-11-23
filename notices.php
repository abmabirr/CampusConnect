<?php
require_once 'includes/helpers.php';
require_once 'includes/db.php';
require_login();

$notices = $conn->query("SELECT n.title, n.description, n.publish_date, u.name AS author FROM notices n JOIN admins a ON a.admin_id = n.admin_id JOIN users u ON u.user_id = a.user_id WHERE n.status = 'Published' ORDER BY n.publish_date DESC");
page_header('Notice Board');
?>
<section class="content-panel">
    <p class="eyebrow">Campus updates</p><h2>University notices</h2>
    <?php if ($notices->num_rows === 0) { ?><p class="empty-state">There are no published notices yet.</p><?php } ?>
    <?php while ($notice = $notices->fetch_assoc()) { ?>
        <article class="notice-preview"><div><h3><?php echo e($notice['title']); ?></h3><time><?php echo e(date('M j, Y · g:i A', strtotime($notice['publish_date']))); ?> · <?php echo e($notice['author']); ?></time></div>
            <p><?php echo nl2br(e($notice['description'])); ?></p></article>
    <?php } ?>
</section>
<?php page_footer(); ?>
