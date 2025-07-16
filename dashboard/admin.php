<?php
require_once '../includes/auth.php';
require_role('admin');
require_once '../includes/db.php';

// Fetch counts for dashboard
$userCount = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$classCount = $pdo->query('SELECT COUNT(*) FROM classes')->fetchColumn();
$subjectCount = $pdo->query('SELECT COUNT(*) FROM subjects')->fetchColumn();
$announcementCount = $pdo->query('SELECT COUNT(*) FROM announcements')->fetchColumn();

// Fetch recent users
$recentUsers = $pdo->query('SELECT id, name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent announcements
$recentAnnouncements = $pdo->query('SELECT id, title, created_at FROM announcements ORDER BY created_at DESC LIMIT 3')->fetchAll(PDO::FETCH_ASSOC);

// Calculate percentage changes (mock data for demonstration)
$userChange = rand(2, 15);
$classChange = rand(-5, 10);
$subjectChange = rand(1, 8);
$announcementChange = rand(3, 12);
?>

<?php include '../includes/header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --info: #4895ef;
            --warning: #f72585;
            --dark: #1d3557;
            --light: #f8f9fa;
            --gray: #6c757d;
            --light-gray: #e9ecef;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            background-color: #f5f7fb;
            color: #333;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--dark) 0%, #0d1b2a 100%);
            color: white;
            padding: 1.5rem 0;
            position: fixed;
            height: 100vh;
            width: 260px;
            overflow-y: auto;
            box-shadow: 0 0 30px rgba(0,0,0,0.1);
            z-index: 100;
        }
        
        .sidebar-header {
            padding: 0 1.5rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-logo {
            font-weight: 700;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sidebar-logo i {
            color: var(--info);
            font-size: 1.8rem;
        }
        
        .sidebar-menu {
            padding: 1.5rem 0;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            padding: 0.8rem 1.5rem;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .menu-item:hover, .menu-item.active {
            background: rgba(255,255,255,0.05);
            color: white;
            border-left: 4px solid var(--info);
        }
        
        .menu-item i {
            margin-right: 12px;
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }
        
        .main-content {
            grid-column: 2;
            padding: 2rem;
        }
        
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        
        .welcome-box h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .welcome-box p {
            color: var(--gray);
            margin: 0;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .notification-btn {
            position: relative;
            background: var(--light-gray);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .notification-btn:hover {
            background: var(--info);
            color: white;
        }
        
        .notification-badge {
            position: absolute;
            top: -3px;
            right: -3px;
            background: var(--warning);
            color: white;
            font-size: 0.7rem;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }
        
        .user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--info), var(--success));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .user-info {
            line-height: 1.4;
        }
        
        .user-info .name {
            font-weight: 600;
        }
        
        .user-info .role {
            font-size: 0.85rem;
            color: var(--gray);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        
        .stat-card.users::before {
            background: linear-gradient(90deg, #4361ee, #4cc9f0);
        }
        
        .stat-card.classes::before {
            background: linear-gradient(90deg, #7209b7, #f72585);
        }
        
        .stat-card.subjects::before {
            background: linear-gradient(90deg, #f8961e, #f9c74f);
        }
        
        .stat-card.announcements::before {
            background: linear-gradient(90deg, #2a9d8f, #90e0ef);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .users .stat-icon {
            background: rgba(67, 97, 238, 0.15);
            color: #4361ee;
        }
        
        .classes .stat-icon {
            background: rgba(114, 9, 183, 0.15);
            color: #7209b7;
        }
        
        .subjects .stat-icon {
            background: rgba(248, 150, 30, 0.15);
            color: #f8961e;
        }
        
        .announcements .stat-icon {
            background: rgba(42, 157, 143, 0.15);
            color: #2a9d8f;
        }
        
        .stat-content h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0.5rem 0;
        }
        
        .stat-title {
            color: var(--gray);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .stat-change {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .change-positive {
            color: #2a9d8f;
        }
        
        .change-negative {
            color: #e63946;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .chart-header h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
        }
        
        .chart-actions {
            display: flex;
            gap: 10px;
        }
        
        .chart-btn {
            background: var(--light);
            border: none;
            border-radius: 8px;
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .chart-btn.active, .chart-btn:hover {
            background: var(--primary);
            color: white;
        }
        
        #mainChart {
            height: 300px;
        }
        
        .recent-box {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        
        .recent-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .recent-header h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
        }
        
        .recent-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .recent-item {
            display: flex;
            align-items: center;
            padding: 0.8rem;
            border-radius: 8px;
            transition: background 0.3s;
        }
        
        .recent-item:hover {
            background: var(--light);
        }
        
        .recent-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 1.2rem;
        }
        
        .users .recent-icon {
            background: rgba(67, 97, 238, 0.15);
            color: #4361ee;
        }
        
        .announcements .recent-icon {
            background: rgba(42, 157, 143, 0.15);
            color: #2a9d8f;
        }
        
        .recent-info {
            flex: 1;
        }
        
        .recent-info h4 {
            margin: 0 0 4px;
            font-size: 0.95rem;
            font-weight: 600;
        }
        
        .recent-info p {
            margin: 0;
            font-size: 0.85rem;
            color: var(--gray);
        }
        
        .recent-time {
            font-size: 0.8rem;
            color: var(--gray);
        }
        
        .tables-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        .table-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .table-header h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
        }
        
        .view-all {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            text-align: left;
            padding: 0.8rem 0.5rem;
            font-weight: 600;
            color: var(--gray);
            border-bottom: 1px solid var(--light-gray);
            font-size: 0.9rem;
        }
        
        .data-table td {
            padding: 0.8rem 0.5rem;
            border-bottom: 1px solid var(--light-gray);
            font-size: 0.95rem;
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-active {
            background: rgba(76, 201, 240, 0.15);
            color: #4cc9f0;
        }
        
        .status-pending {
            background: rgba(248, 150, 30, 0.15);
            color: #f8961e;
        }
        
        @media (max-width: 1024px) {
            .content-grid, .tables-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                grid-column: 1;
            }
        }
        
        @media (max-width: 768px) {
            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .user-menu {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-grid">
        <!-- Sidebar -->
      <?php include '../includes/sidebar.php' ?>
        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <div class="topbar">
                <div class="welcome-box">
                    <h1>Dashboard</h1>
                    <p>Welcome back, <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong>! Here's what's happening today.</p>
                </div>
                
                <div class="user-menu">
                    <div class="notification-btn">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </div>
                    
                    <div class="user-profile">
                        <div class="user-avatar">
                            <?php 
                                $nameParts = explode(' ', $_SESSION['name']);
                                $initials = '';
                                foreach ($nameParts as $part) {
                                    $initials .= strtoupper(substr($part, 0, 1));
                                }
                                echo substr($initials, 0, 2);
                            ?>
                        </div>
                        <div class="user-info">
                            <div class="name"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
                            <div class="role">Administrator</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card users">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-change <?php echo $userChange > 0 ? 'change-positive' : 'change-negative'; ?>">
                            <i class="fas <?php echo $userChange > 0 ? 'fa-arrow-up' : 'fa-arrow-down'; ?>"></i>
                            <span><?php echo abs($userChange); ?>%</span>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $userCount; ?></h3>
                        <div class="stat-title">Total Users</div>
                    </div>
                </div>
                
                <div class="stat-card classes">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-chalkboard"></i>
                        </div>
                        <div class="stat-change <?php echo $classChange > 0 ? 'change-positive' : 'change-negative'; ?>">
                            <i class="fas <?php echo $classChange > 0 ? 'fa-arrow-up' : 'fa-arrow-down'; ?>"></i>
                            <span><?php echo abs($classChange); ?>%</span>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $classCount; ?></h3>
                        <div class="stat-title">Total Classes</div>
                    </div>
                </div>
                
                <div class="stat-card subjects">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-change <?php echo $subjectChange > 0 ? 'change-positive' : 'change-negative'; ?>">
                            <i class="fas <?php echo $subjectChange > 0 ? 'fa-arrow-up' : 'fa-arrow-down'; ?>"></i>
                            <span><?php echo abs($subjectChange); ?>%</span>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $subjectCount; ?></h3>
                        <div class="stat-title">Total Subjects</div>
                    </div>
                </div>
                
                <div class="stat-card announcements">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <div class="stat-change <?php echo $announcementChange > 0 ? 'change-positive' : 'change-negative'; ?>">
                            <i class="fas <?php echo $announcementChange > 0 ? 'fa-arrow-up' : 'fa-arrow-down'; ?>"></i>
                            <span><?php echo abs($announcementChange); ?>%</span>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $announcementCount; ?></h3>
                        <div class="stat-title">Announcements</div>
                    </div>
                </div>
            </div>
            
            <!-- Charts and Recent Activity -->
            <div class="content-grid">
                <!-- Main Chart -->
                <div class="chart-container">
                    <div class="chart-header">
                        <h3>Activity Overview</h3>
                        <div class="chart-actions">
                            <button class="chart-btn active">Week</button>
                            <button class="chart-btn">Month</button>
                            <button class="chart-btn">Year</button>
                        </div>
                    </div>
                    <div id="mainChart"></div>
                </div>
                
                <!-- Recent Activity -->
                <div class="recent-box">
                    <div class="recent-header">
                        <h3>Recent Activity</h3>
                    </div>
                    <div class="recent-list">
                        <?php foreach ($recentAnnouncements as $announcement): ?>
                        <div class="recent-item announcements">
                            <div class="recent-icon">
                                <i class="fas fa-bullhorn"></i>
                            </div>
                            <div class="recent-info">
                                <h4><?php echo htmlspecialchars($announcement['title']); ?></h4>
                                <p>New announcement published</p>
                            </div>
                            <div class="recent-time">
                                <?php 
                                    $date = new DateTime($announcement['created_at']);
                                    echo $date->format('M d');
                                ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php foreach ($recentUsers as $user): ?>
                        <div class="recent-item users">
                            <div class="recent-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="recent-info">
                                <h4><?php echo htmlspecialchars($user['name']); ?></h4>
                                <p>New user registered</p>
                            </div>
                            <div class="recent-time">
                                <?php 
                                    $date = new DateTime($user['created_at']);
                                    echo $date->format('M d');
                                ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Data Tables -->
            <div class="tables-grid">
                <!-- Recent Users Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h3>Recent Users</h3>
                        <a href="#" class="view-all">View All</a>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Joined</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentUsers as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php 
                                        $date = new DateTime($user['created_at']);
                                        echo $date->format('M d, Y');
                                    ?>
                                </td>
                                <td><span class="status-badge status-active">Active</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Announcements Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h3>Latest Announcements</h3>
                        <a href="#" class="view-all">View All</a>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentAnnouncements as $announcement): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($announcement['title']); ?></td>
                                <td>
                                    <?php 
                                        $date = new DateTime($announcement['created_at']);
                                        echo $date->format('M d, Y');
                                    ?>
                                </td>
                                <td><span class="status-badge status-active">Published</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize main chart
        document.addEventListener('DOMContentLoaded', function() {
            // Chart data (mock data for demonstration)
            const chartData = {
                series: [{
                    name: 'Users',
                    data: [30, 40, 35, 50, 49, 60, 70, 91, 125]
                }, {
                    name: 'Classes',
                    data: [23, 32, 27, 38, 44, 46, 53, 59, 65]
                }, {
                    name: 'Announcements',
                    data: [15, 25, 20, 30, 27, 35, 42, 48, 52]
                }],
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep']
            };
            
            // Chart options
            const options = {
                chart: {
                    height: '100%',
                    type: 'area',
                    toolbar: {
                        show: false
                    },
                    zoom: {
                        enabled: false
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                series: chartData.series,
                xaxis: {
                    categories: chartData.categories,
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                yaxis: {
                    show: false
                },
                grid: {
                    show: false,
                    padding: {
                        left: 0,
                        right: 0
                    }
                },
                colors: ['#4361ee', '#7209b7', '#2a9d8f'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.1,
                        stops: [0, 90, 100]
                    }
                },
                tooltip: {
                    x: {
                        format: 'MMM'
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    markers: {
                        radius: 12
                    }
                }
            };
            
            // Render chart
            const chart = new ApexCharts(document.querySelector("#mainChart"), options);
            chart.render();
            
            // Chart period buttons
            const periodButtons = document.querySelectorAll('.chart-btn');
            periodButtons.forEach(button => {
                button.addEventListener('click', function() {
                    periodButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    // In a real app, you would fetch new data based on the selected period
                });
            });
            
            // Stat card animations
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Add animation class
                    this.classList.add('animate-pulse');
                    
                    // Remove animation class after it completes
                    setTimeout(() => {
                        this.classList.remove('animate-pulse');
                    }, 500);
                    
                    // In a real app, this would navigate to the relevant section
                    console.log('Navigating to:', this.classList[1]);
                });
            });
        });
    </script>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>