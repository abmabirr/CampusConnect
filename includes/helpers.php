<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(array(
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'path' => '/'
    ));
    session_start();
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf()
{
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(400);
        exit('Invalid form submission. Refresh the page and try again.');
    }
}

function require_login()
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

function require_role($role)
{
    require_login();

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        http_response_code(403);
        exit('You do not have permission to access this page.');
    }
}

function dashboard_url()
{
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin') {
        return 'admin_dashboard.php';
    }
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'Teacher') {
        return 'teacher_dashboard.php';
    }
    return 'student_dashboard.php';
}

function profile_picture_url()
{
    $picture = isset($_SESSION['profile_picture']) ? $_SESSION['profile_picture'] : '';
    if (!is_string($picture) || !preg_match('/\A[a-f0-9]{32}\.(?:jpg|png|webp)\z/D', $picture)) {
        return '';
    }

    return 'uploads/profile_pictures/' . $picture;
}

function render_profile_avatar($class, $initial)
{
    $pictureUrl = profile_picture_url();
    if ($pictureUrl !== '') {
        echo '<img class="' . e($class) . '" src="' . e($pictureUrl) . '" alt="">';
        return;
    }

    echo '<span class="' . e($class) . '">' . e($initial) . '</span>';
}

function page_header($title)
{
    $name = isset($_SESSION['name']) ? $_SESSION['name'] : 'CampusConnect';
    $currentPage = basename(parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH));
    $requestFragment = parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '', PHP_URL_FRAGMENT);
    $requestFragment = $requestFragment ? $requestFragment : '';
    $renderNavLink = function ($href, $label, $icon) use ($currentPage, $requestFragment) {
        $hrefPath = basename(parse_url($href, PHP_URL_PATH));
        $hrefFragment = parse_url($href, PHP_URL_FRAGMENT);
        $hrefFragment = $hrefFragment ? $hrefFragment : '';
        $isActive = $hrefPath === $currentPage
            && (($hrefFragment !== '' && $hrefFragment === $requestFragment)
                || ($hrefFragment === '' && $requestFragment === ''));
        echo '<a href="' . e($href) . '"' . ($isActive ? ' class="active" aria-current="page"' : '') . '>';
        echo '<span class="sidebar-nav-icon" aria-hidden="true">' . e($icon) . '</span><span>' . e($label) . '</span></a>';
    };
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . e($title) . ' | CampusConnect</title>';
    echo '<script>(function () { try { var savedTheme = localStorage.getItem("campusconnect-theme"); document.documentElement.dataset.theme = savedTheme === "dark" ? "dark" : "light"; } catch (error) { document.documentElement.dataset.theme = "light"; console.warn("CampusConnect could not restore the saved theme.", error); } }());</script>';
    $styleVersion = filemtime(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'style.css');
    $dashboardStyleVersion = filemtime(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'dashboard.css');
    echo '<link rel="stylesheet" href="css/style.css?v=' . (int) $styleVersion . '"><link rel="stylesheet" href="css/dashboard.css?v=' . (int) $dashboardStyleVersion . '"></head><body class="portal-page">';
    echo '<div class="app-shell portal-shell"><aside class="sidebar portal-sidebar">';
    echo '<a class="brand portal-brand" href="' . e(dashboard_url()) . '"><span class="portal-brand-mark">C</span><span>Campus<span>Connect</span></span></a>';
    echo '<p class="sidebar-subtitle"><span class="sidebar-live-dot"></span>' . e($_SESSION['role']) . ' Portal</p><p class="sidebar-label">WORKSPACE</p><nav class="portal-nav">';
    $renderNavLink(dashboard_url(), 'Dashboard', '⌂');

    if ($_SESSION['role'] === 'Student') {
        $renderNavLink('student_profile.php', 'My Profile', '◉');
        $renderNavLink('notices.php', 'Notice Board', '▤');
        $renderNavLink('complaints.php', 'Complaints', '✳');
        $renderNavLink('appointments.php', 'Teacher Appointment', '◷');
        $renderNavLink('labs.php', 'Lab Booking', '⌘');
    } elseif ($_SESSION['role'] === 'Teacher') {
        $renderNavLink('teacher_dashboard.php#appointments', 'Appointments', '◷');
        $renderNavLink('notices.php', 'Notice Board', '▤');
    } else {
        $renderNavLink('admin_dashboard.php#notices', 'Manage Notices', '▤');
        $renderNavLink('admin_dashboard.php#complaints', 'Complaints', '✳');
        $renderNavLink('admin_dashboard.php#users', 'Users & Labs', '◉');
    }

    $initial = function_exists('mb_substr') ? mb_substr($name, 0, 1, 'UTF-8') : substr($name, 0, 1);
    echo '</nav><div class="sidebar-spacer"></div><div class="sidebar-account">';
    render_profile_avatar('account-avatar', $initial);
    echo '<span class="account-copy"><strong>' . e($name) . '</strong><small>' . e($_SESSION['role']) . ' account</small></span><span class="account-presence" aria-label="Online"></span></div>';
    echo '<form method="post" action="logout.php" class="sidebar-logout-form">' . csrf_field() . '<button class="logout-link" type="submit"><span aria-hidden="true">↪</span> Sign out</button></form>';
    echo '</aside><main class="page-main portal-main"><header class="page-topbar portal-topbar"><div class="page-heading"><p class="eyebrow">' . e($_SESSION['role']) . ' workspace <span class="heading-dot"></span> ' . e(date('l, F j')) . '</p>';
    echo '<h1>' . e($title) . '</h1><p class="topbar-subtitle">Here is what is happening across your campus today.</p></div><div class="topbar-actions"><button class="theme-toggle" type="button" aria-label="Switch to dark mode" aria-pressed="false"><span class="theme-toggle-icon" aria-hidden="true">☾</span><span class="theme-toggle-label">Dark mode</span></button><a class="public-site-link" href="index.php"><span aria-hidden="true">↗</span> Public site</a>';
    render_profile_avatar('topbar-avatar', $initial);
    echo '</div></header>';
}

function page_footer()
{
    echo '</main></div><script>
        (function () {
            var toggle = document.querySelector(".theme-toggle");
            if (!toggle) {
                return;
            }

            var icon = toggle.querySelector(".theme-toggle-icon");
            var label = toggle.querySelector(".theme-toggle-label");
            var updateToggle = function (theme) {
                var isDark = theme === "dark";
                document.documentElement.dataset.theme = isDark ? "dark" : "light";
                toggle.setAttribute("aria-pressed", isDark ? "true" : "false");
                toggle.setAttribute("aria-label", isDark ? "Switch to light mode" : "Switch to dark mode");
                icon.textContent = isDark ? "☀" : "☾";
                label.textContent = isDark ? "Light mode" : "Dark mode";
            };

            updateToggle(document.documentElement.dataset.theme);
            toggle.addEventListener("click", function () {
                var nextTheme = document.documentElement.dataset.theme === "dark" ? "light" : "dark";
                updateToggle(nextTheme);
                try {
                    localStorage.setItem("campusconnect-theme", nextTheme);
                } catch (error) {
                    console.warn("CampusConnect could not save the selected theme.", error);
                }
            });
        }());
    </script></body></html>';
}

function post_value($key)
{
    return isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : '';
}
