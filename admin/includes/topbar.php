<?php
// $topbar_page: 'members' | 'cms' | 'profile'
// $topbar_cms:  'events'  | 'photos' | 'music' | null
$topbar_page = $topbar_page ?? '';
$topbar_cms  = $topbar_cms  ?? '';
?>
<style>
    .topbar {
        background: #ffe137;
        padding: 0 1.5rem;
        display: flex; align-items: center; justify-content: space-between;
        position: sticky; top: 0; z-index: 100;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        height: 52px;
    }
    .topbar .brand { font-weight: 900; font-size: 1.15rem; letter-spacing: 2px; color: #000; text-decoration: none; }
    .topbar-nav { display: flex; align-items: center; gap: 0.25rem; }
    .topbar-nav a {
        padding: 0.3rem 0.85rem; border-radius: 5px;
        font-weight: 600; font-size: 0.85rem; color: #000; text-decoration: none;
        transition: background 0.15s;
    }
    .topbar-nav a:hover   { background: rgba(0,0,0,0.1); }
    .topbar-nav a.active  { background: #000; color: #ffe137; }
    .topbar-right { display: flex; align-items: center; gap: 0.75rem; }
    .topbar-right a { font-size: 0.85rem; color: #000; text-decoration: none; font-weight: 600; }
    .topbar-right a:hover { color: #555; }
    .topbar-right .logout:hover { color: #d30505; }

    /* CMS sub-nav */
    .subnav {
        background: #111; padding: 0 1.5rem;
        display: flex; align-items: center; gap: 0.25rem; height: 40px;
    }
    .subnav a {
        padding: 0.25rem 0.8rem; border-radius: 4px;
        font-weight: 600; font-size: 0.8rem; color: #aaa; text-decoration: none;
        transition: color 0.15s, background 0.15s;
    }
    .subnav a:hover  { color: #fff; }
    .subnav a.active { background: #ffe137; color: #000; }
</style>

<div class="topbar">
    <div class="d-flex align-items-center gap-3">
        <a href="/admin/dashboard.php" class="brand">SELEKTIERT_</a>
        <nav class="topbar-nav">
            <a href="/admin/dashboard.php"    class="<?= $topbar_page === 'members' ? 'active' : '' ?>">Members</a>
            <a href="/admin/cms/"             class="<?= $topbar_page === 'cms'     ? 'active' : '' ?>">CMS</a>
        </nav>
    </div>
    <div class="topbar-right">
        <a href="/admin/profile.php?id=<?= $current_user['id'] ?>">
            <i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']) ?>
        </a>
        <a href="/admin/logout.php" class="logout"><i class="fas fa-sign-out-alt"></i></a>
    </div>
</div>

<?php if ($topbar_page === 'cms'): ?>
<div class="subnav">
    <a href="/admin/cms/events.php" class="<?= $topbar_cms === 'events' ? 'active' : '' ?>"><i class="fas fa-calendar-alt me-1"></i>Events</a>
    <a href="/admin/cms/photos.php" class="<?= $topbar_cms === 'photos' ? 'active' : '' ?>"><i class="fas fa-images me-1"></i>Photos</a>
    <a href="/admin/cms/music.php"  class="<?= $topbar_cms === 'music'  ? 'active' : '' ?>"><i class="fas fa-music me-1"></i>Music</a>
</div>
<?php endif; ?>
