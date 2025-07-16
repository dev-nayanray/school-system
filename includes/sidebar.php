<?php
if (!isset($_SESSION)) {
    session_start();
}

$role = $_SESSION['role'] ?? 'user';

$menu_items = [];

switch ($role) {
    case 'admin':
        $menu_items = [
            'Dashboard' => ['link' => '../dashboard/admin.php', 'icon' => 'grid'],
            'Manage Users' => ['link' => '../dashboard/admin_manage_users.php', 'icon' => 'users'],
            'Classes' => ['link' => '../dashboard/admin_classes.php', 'icon' => 'book-open'],
            'Subjects' => ['link' => '../dashboard/admin_subjects.php', 'icon' => 'book'],
            'Announcements' => ['link' => '../dashboard/admin_announcements.php', 'icon' => 'bell'],
            'Settings' => ['link' => '../dashboard/settings.php', 'icon' => 'settings']
        ];
        break;
    case 'teacher':
        $menu_items = [
            'Dashboard' => ['link' => '../dashboard/teacher.php', 'icon' => 'grid'],
            'Marks Entry' => ['link' => '../dashboard/teacher_marks.php', 'icon' => 'edit'],
            'Schedule' => ['link' => '../dashboard/teacher_schedule.php', 'icon' => 'calendar'],
            'Profile' => ['link' => '../dashboard/teacher_profile.php', 'icon' => 'user'],
            'Announcements' => ['link' => '../dashboard/announcements.php', 'icon' => 'bell'],
        ];
        break;
    case 'student':
        $menu_items = [
            'Dashboard' => ['link' => '../dashboard/student.php', 'icon' => 'grid'],
            'Marks' => ['link' => '../dashboard/student_marks.php', 'icon' => 'bar-chart-2'],
            'Schedule' => ['link' => '../dashboard/student_schedule.php', 'icon' => 'calendar'],
            'Profile' => ['link' => '../dashboard/student_profile.php', 'icon' => 'user'],
            'Announcements' => ['link' => '../dashboard/announcements.php', 'icon' => 'bell'],
            'Resources' => ['link' => '../dashboard/resources.php', 'icon' => 'folder']
        ];
        break;
    default:
        $menu_items = [
            'Dashboard' => ['link' => '../dashboard/user.php', 'icon' => 'grid'],
            'Announcements' => ['link' => '../dashboard/announcements.php', 'icon' => 'bell'],
        ];
        break;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Sidebar</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            --sidebar-width: 260px;
            --header-height: 64px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fb;
            min-height: 100vh;
            display: flex;
        }
        
        /* Mobile header */
        .mobile-header {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: var(--header-height);
            background: var(--sidebar-bg);
            z-index: 90;
            padding: 0 20px;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            font-weight: 600;
            font-size: 18px;
        }
        
        .logo-icon {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
        }
        
        .hamburger {
            display: flex;
            flex-direction: column;
            gap: 4px;
            cursor: pointer;
            padding: 8px;
        }
        
        .hamburger span {
            display: block;
            width: 24px;
            height: 2px;
            background-color: white;
            border-radius: 2px;
            transition: var(--transition);
        }
        
        /* Sidebar container */
        .sidebar-container {
            position: sticky;
            top: 0;
            height: 100vh;
            width: var(--sidebar-width);
            flex-shrink: 0;
            z-index: 110; /* Increased z-index for better stacking */
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1); /* Added subtle shadow on right edge */
        }
        
        /* Sidebar Component */
        .sidebar {
            background: var(--sidebar-bg);
            color: white;
            height: 100%;
            padding: 24px 16px;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: var(--transition);
        }
        
        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 8px 24px 8px;
            margin-bottom: 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 4px;
        }
        
        .user-role {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.8);
            background: rgba(255, 255, 255, 0.15);
            padding: 2px 8px;
            border-radius: 20px;
            display: inline-block;
        }
        
        /* Search Field */
        .search-container {
            position: relative;
            margin-bottom: 24px;
        }
        
        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
        }
        
        #sidebar-search {
            width: 100%;
            padding: 12px 16px 12px 40px;
            background: rgba(255, 255, 255, 0.15);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 14px;
            transition: var(--transition);
            backdrop-filter: blur(5px);
        }
        
        #sidebar-search::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        #sidebar-search:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.25);
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.2);
        }
        
        /* Menu Items */
        #sidebar-menu {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1;
        }
        
        #sidebar-menu li a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            border-radius: 10px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            transition: var(--transition);
        }
        
        #sidebar-menu li a:hover,
        #sidebar-menu li a.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }
        
        #sidebar-menu li a i {
            width: 20px;
            text-align: center;
            font-size: 18px;
        }
        
        .menu-icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Footer */
        .sidebar-footer {
            padding: 16px 0 0 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: auto;
        }
        
        .footer-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 10px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 14px;
            transition: var(--transition);
        }
        
        .footer-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        /* Content area */
        .content {
            flex: 1;
            padding: 24px;
            padding-top: calc(var(--header-height) + 24px);
            margin-left: var(--sidebar-width); /* Added margin to avoid overlap with sticky sidebar */
        }
        
        .content-header {
            margin-bottom: 24px;
        }
        
        .content-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .content-header p {
            color: #64748b;
            font-size: 16px;
        }
        
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }
        
        .card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }
        
        .card h3 {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 16px;
        }
        
        .card p {
            color: #64748b;
            line-height: 1.6;
        }
        
        /* Mobile sidebar state */
        .mobile-sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 95;
            opacity: 0;
            transition: var(--transition);
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .mobile-header {
                display: flex;
            }
            
            .sidebar-container {
                position: fixed;
                left: calc(-1 * var(--sidebar-width));
                top: 0;
                height: 100vh;
                transition: var(--transition);
                z-index: 99;
            }
            
            .sidebar-container.active {
                left: 0;
            }
            
            .mobile-sidebar-overlay.active {
                display: block;
                opacity: 1;
            }
            
            .content {
                padding-left: 24px;
                padding-right: 24px;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile header -->
    <div class="mobile-header">
        <div class="logo">
            <div class="logo-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                </svg>
            </div>
            <span>EduPortal</span>
        </div>
        <div class="hamburger" id="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
    
    <!-- Sidebar container -->
    <div class="sidebar-container" id="sidebarContainer">
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="avatar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo $_SESSION['name'] ?? 'User'; ?></div>
                    <div class="user-role"><?php echo ucfirst($role); ?></div>
                </div>
            </div>
            
            <div class="search-container">
                <div class="search-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </div>
                <input 
                    type="text" 
                    id="sidebar-search" 
                    placeholder="Search menu..." 
                />
            </div>
            
            <ul id="sidebar-menu">
                <?php foreach ($menu_items as $name => $item): ?>
                    <li>
                        <a href="<?= htmlspecialchars($item['link']) ?>">
                            <div class="menu-icon">
                                <?php 
                                $icon = $item['icon'];
                                $svg = '';
                                switch($icon) {
                                    case 'grid':
                                        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>';
                                        break;
                                    case 'users':
                                        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>';
                                        break;
                                    case 'book-open':
                                        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>';
                                        break;
                                    case 'book':
                                        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>';
                                        break;
                                    case 'bell':
                                        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>';
                                        break;
                                    case 'settings':
                                        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>';
                                        break;
                                    case 'edit':
                                        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>';
                                        break;
                                    case 'calendar':
                                        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>';
                                        break;
                                    case 'user':
                                        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>';
                                        break;
                                    case 'bar-chart-2':
                                        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>';
                                        break;
                                    case 'folder':
                                        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>';
                                        break;
                                    default:
                                        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';
                                }
                                echo $svg;
                                ?>
                            </div>
                            <span><?= htmlspecialchars($name) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <div class="sidebar-footer">
                <a href="#" class="footer-link">
                    <div class="menu-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10 3H6a2 2 0 0 0-2 2v14c0 1.1.9 2 2 2h4M16 17l5-5-5-5M19.8 12H9"></path>
                        </svg>
                    </div>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Mobile sidebar overlay -->
    <div class="mobile-sidebar-overlay" id="sidebarOverlay"></div>
    
    
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hamburger = document.getElementById('hamburger');
            const sidebarContainer = document.getElementById('sidebarContainer');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            // Toggle sidebar on hamburger click
            hamburger.addEventListener('click', function() {
                sidebarContainer.classList.toggle('active');
                sidebarOverlay.classList.toggle('active');
            });
            
            // Close sidebar when clicking on overlay
            sidebarOverlay.addEventListener('click', function() {
                sidebarContainer.classList.remove('active');
                sidebarOverlay.classList.remove('active');
            });
            
            // Menu search functionality
            const searchInput = document.getElementById('sidebar-search');
            const menuItems = document.querySelectorAll('#sidebar-menu li');
            
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                
                menuItems.forEach(item => {
                    const text = item.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
            
            // Set active menu item based on current page
            const currentPath = window.location.pathname.split('/').pop();
            menuItems.forEach(item => {
                const link = item.querySelector('a').getAttribute('href');
                const linkPath = link.split('/').pop();
                
                if (currentPath === linkPath) {
                    item.querySelector('a').classList.add('active');
                }
            });
        });
    </script>
</body>
</html>