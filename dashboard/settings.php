<?php
require_once '../includes/auth.php';
require_role('admin');
require_once '../includes/db.php';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

// Fetch current settings from database
$currentSettings = [];
$stmt = $pdo->query('SELECT setting_key, setting_value FROM settings');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $currentSettings[$row['setting_key']] = $row['setting_value'];
}

$settingsMetadata = [];
$settingsOptions = [];
$settingsCategories = [];

// Fetch settings metadata from database
$stmtMeta = $pdo->query('SELECT setting_key, label, type, options, category, default_value FROM settings_metadata ORDER BY category, setting_key');
while ($row = $stmtMeta->fetch(PDO::FETCH_ASSOC)) {
    $key = $row['setting_key'];
    $category = $row['category'];
    $settingsMetadata[$category][$key] = [
        'label' => $row['label'],
        'type' => $row['type'],
        'default' => $row['default_value'],
    ];
    if ($row['options']) {
        $settingsMetadata[$category][$key]['options'] = json_decode($row['options'], true);
    }
    $settingsCategories[$category] = true;
}

// Set default values if not set
foreach ($settingsMetadata as $category => $settings) {
    foreach ($settings as $key => $meta) {
        if (!isset($currentSettings[$key])) {
            if ($meta['type'] === 'checkbox') {
                $currentSettings[$key] = $meta['default'] === '1' || $meta['default'] === 1 ? '1' : '0';
            } else {
                $currentSettings[$key] = $meta['default'];
            }
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request";
    } else {
        try {
            $pdo->beginTransaction();

            // Prepare statements
            $stmtSelect = $pdo->prepare('SELECT id FROM settings WHERE setting_key = ?');
            $stmtUpdate = $pdo->prepare('UPDATE settings SET setting_value = ? WHERE id = ?');
            $stmtInsert = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)');

            // Process all settings dynamically based on metadata
            foreach ($settingsMetadata as $category => $settings) {
                foreach ($settings as $key => $meta) {
                    if ($meta['type'] === 'checkbox') {
                        $value = isset($_POST[$key]) ? '1' : '0';
                    } else {
                        $value = $_POST[$key] ?? '';
                    }

                    // Check if setting exists
                    $stmtSelect->execute([$key]);
                    $existing = $stmtSelect->fetch(PDO::FETCH_ASSOC);

                    if ($existing) {
                        $stmtUpdate->execute([$value, $existing['id']]);
                    } else {
                        $stmtInsert->execute([$key, $value]);
                    }

                    $currentSettings[$key] = $value;
                }
            }

            $pdo->commit();
            $success = "Settings saved successfully.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error saving settings: " . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - EduAdmin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching functionality
            document.querySelectorAll('.tab-button').forEach(button => {
                button.addEventListener('click', () => {
                    // Remove active class from all buttons
                    document.querySelectorAll('.tab-button').forEach(btn => {
                        btn.classList.remove('tab-active', 'text-indigo-600', 'font-semibold');
                        btn.classList.add('text-gray-500', 'hover:text-gray-700');
                        
                        const span = btn.querySelector('span');
                        if (span) span.remove();
                    });
                    
                    // Add active class to clicked button
                    button.classList.remove('text-gray-500', 'hover:text-gray-700');
                    button.classList.add('tab-active', 'text-indigo-600', 'font-semibold');
                    
                    // Add indicator span if not present
                    if (!button.querySelector('span')) {
                        const span = document.createElement('span');
                        span.className = 'absolute bottom-0 left-0 w-full h-0.5 bg-indigo-600';
                        button.appendChild(span);
                    }
                    
                    // Hide all tab content
                    document.querySelectorAll('.tab-content').forEach(content => {
                        content.classList.add('hidden');
                    });
                    
                    // Show selected tab content
                    const tabId = button.getAttribute('data-tab') + '-tab';
                    document.getElementById(tabId).classList.remove('hidden');
                });
            });
            
            // Color picker functionality
            const colorPicker = document.getElementById('primary_color');
            const colorPreview = document.getElementById('color-preview');
            
            if (colorPicker && colorPreview) {
                colorPicker.addEventListener('input', function() {
                    colorPreview.style.backgroundColor = this.value;
                });
                
                // Set initial preview color
                colorPreview.style.backgroundColor = colorPicker.value;
            }
            
            // Password visibility toggle
            document.querySelectorAll('.password-toggle').forEach(button => {
                button.addEventListener('click', function() {
                    const input = this.previousElementSibling;
                    const icon = this.querySelector('i');
                    
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            });
            
            // Analytics chart
            const ctx = document.getElementById('analyticsChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Active Users',
                        data: [1200, 1900, 1500, 1800, 2200, 2400, 2800, 2500, 2300, 2600, 3000, 3200],
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
        }
        
        .tab-button {
            transition: all 0.2s ease;
            position: relative;
            white-space: nowrap;
        }
        
        .color-preview {
            width: 30px;
            height: 30px;
            border-radius: 4px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .color-preview:hover {
            transform: scale(1.05);
        }
        
        .password-field {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #6b7280;
        }
        
        .setting-card {
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .setting-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border-left-color: #3b82f6;
        }
        
        .stat-card {
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .gradient-header {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
        }
    </style>
</head>
<?php include '../includes/header.php'; ?>
<div class="flex flex-col lg:flex-row">
    <?php include '../includes/sidebar.php'; ?>

    <main class="flex-1 p-6 bg-gradient-to-br from-gray-50 to-gray-100">
            <div class="max-w-5xl mx-auto">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">System Settings</h1>
                        <p class="text-gray-600 mt-2">Configure platform preferences and behavior</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button class="bg-white border border-gray-300 rounded-lg px-4 py-2 text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-download mr-2"></i> Export
                        </button>
                        <div class="relative">
                            <button class="bg-indigo-600 text-white rounded-lg px-4 py-2 hover:bg-indigo-700">
                                <i class="fas fa-cog mr-2"></i> Settings
                            </button>
                        </div>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-400 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800"><?= htmlspecialchars($error) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-lg">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-400 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800"><?= htmlspecialchars($success) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                    <div class="stat-card bg-white rounded-xl shadow p-5 flex items-center">
                        <div class="bg-blue-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-users text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Total Users</p>
                            <p class="text-2xl font-bold">1,248</p>
                        </div>
                    </div>
                    <div class="stat-card bg-white rounded-xl shadow p-5 flex items-center">
                        <div class="bg-green-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-book text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Active Courses</p>
                            <p class="text-2xl font-bold">42</p>
                        </div>
                    </div>
                    <div class="stat-card bg-white rounded-xl shadow p-5 flex items-center">
                        <div class="bg-amber-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-chart-line text-amber-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Avg. Engagement</p>
                            <p class="text-2xl font-bold">78%</p>
                        </div>
                    </div>
                    <div class="stat-card bg-white rounded-xl shadow p-5 flex items-center">
                        <div class="bg-purple-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-server text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">System Status</p>
                            <p class="text-2xl font-bold text-green-600">Operational</p>
                        </div>
                    </div>
                </div>

                <!-- Settings Panel -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="border-b border-gray-200">
                        <nav class="flex overflow-x-auto py-4 px-6">
                            <div class="flex space-x-8">
                                <button type="button" data-tab="general" class="tab-button tab-active relative py-2 px-1 font-medium text-sm focus:outline-none text-indigo-600 font-semibold">
                                    <span class="absolute bottom-0 left-0 w-full h-0.5 bg-indigo-600"></span>
                                    <i class="fas fa-cog mr-2"></i>General
                                </button>
                                <button type="button" data-tab="appearance" class="tab-button relative py-2 px-1 font-medium text-sm text-gray-500 hover:text-gray-700 focus:outline-none">
                                    <i class="fas fa-paint-brush mr-2"></i>Appearance
                                </button>
                                <button type="button" data-tab="users" class="tab-button relative py-2 px-1 font-medium text-sm text-gray-500 hover:text-gray-700 focus:outline-none">
                                    <i class="fas fa-users mr-2"></i>Users & Roles
                                </button>
                                <button type="button" data-tab="email" class="tab-button relative py-2 px-1 font-medium text-sm text-gray-500 hover:text-gray-700 focus:outline-none">
                                    <i class="fas fa-envelope mr-2"></i>Email
                                </button>
                                <button type="button" data-tab="security" class="tab-button relative py-2 px-1 font-medium text-sm text-gray-500 hover:text-gray-700 focus:outline-none">
                                    <i class="fas fa-shield-alt mr-2"></i>Security
                                </button>
                                <button type="button" data-tab="advanced" class="tab-button relative py-2 px-1 font-medium text-sm text-gray-500 hover:text-gray-700 focus:outline-none">
                                    <i class="fas fa-microchip mr-2"></i>Advanced
                                </button>
                            </div>
                        </nav>
                    </div>
                    
                    <form method="post" action="settings.php" class="p-6">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        
                        <!-- General Settings Tab -->
                        <div class="tab-content" id="general-tab">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="setting-card bg-gray-50 rounded-lg p-5">
                                    <h3 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                                        <i class="fas fa-globe mr-2 text-indigo-600"></i> Site Information
                                    </h3>
                                    <div class="space-y-4">
                                        <div>
                                            <label for="site_name" class="block text-sm font-medium text-gray-700 mb-1">Site Name</label>
                                            <input type="text" name="site_name" id="site_name" value="<?= htmlspecialchars($currentSettings['site_name']) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition" placeholder="Enter site name" />
                                        </div>
                                        
                                        <div>
                                            <label for="admin_email" class="block text-sm font-medium text-gray-700 mb-1">Admin Email</label>
                                            <input type="email" name="admin_email" id="admin_email" value="<?= htmlspecialchars($currentSettings['admin_email']) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition" placeholder="admin@example.com" />
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="setting-card bg-gray-50 rounded-lg p-5">
                                    <h3 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                                        <i class="fas fa-clock mr-2 text-indigo-600"></i> Date & Time
                                    </h3>
                                    <div class="space-y-4">
                                        <div>
                                            <label for="timezone" class="block text-sm font-medium text-gray-700 mb-1">Timezone</label>
                                            <select id="timezone" name="timezone" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm appearance-none bg-white">
                                                <?php foreach ($timezones as $value => $label): ?>
                                                    <option value="<?= $value ?>" <?= $currentSettings['timezone'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Date Format</label>
                                                <select name="date_format" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                                                    <option value="F j, Y" <?= $currentSettings['date_format'] === 'F j, Y' ? 'selected' : '' ?>>Month Day, Year (January 1, 2023)</option>
                                                    <option value="m/d/Y" <?= $currentSettings['date_format'] === 'm/d/Y' ? 'selected' : '' ?>>MM/DD/YYYY (01/01/2023)</option>
                                                    <option value="d/m/Y" <?= $currentSettings['date_format'] === 'd/m/Y' ? 'selected' : '' ?>>DD/MM/YYYY (01/01/2023)</option>
                                                    <option value="Y-m-d" <?= $currentSettings['date_format'] === 'Y-m-d' ? 'selected' : '' ?>>YYYY-MM-DD (2023-01-01)</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Time Format</label>
                                                <select name="time_format" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                                                    <option value="g:i a" <?= $currentSettings['time_format'] === 'g:i a' ? 'selected' : '' ?>>12-hour (9:30 pm)</option>
                                                    <option value="H:i" <?= $currentSettings['time_format'] === 'H:i' ? 'selected' : '' ?>>24-hour (21:30)</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Appearance Settings Tab -->
                        <div class="tab-content hidden" id="appearance-tab">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="setting-card bg-gray-50 rounded-lg p-5">
                                    <h3 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                                        <i class="fas fa-palette mr-2 text-indigo-600"></i> Theme & Colors
                                    </h3>
                                    <div class="space-y-4">
                                        <div>
                                            <label for="theme" class="block text-sm font-medium text-gray-700 mb-1">Theme</label>
                                            <select id="theme" name="theme" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                                                <?php foreach ($themes as $value => $label): ?>
                                                    <option value="<?= $value ?>" <?= $currentSettings['theme'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div>
                                            <label for="primary_color" class="block text-sm font-medium text-gray-700 mb-1">Primary Color</label>
                                            <div class="flex items-center space-x-3">
                                                <input type="color" id="primary_color" name="primary_color" value="<?= htmlspecialchars($currentSettings['primary_color']) ?>" class="w-16 h-10 border-0 rounded cursor-pointer" />
                                                <div id="color-preview" class="color-preview"></div>
                                                <input type="text" value="<?= htmlspecialchars($currentSettings['primary_color']) ?>" class="px-3 py-2 border border-gray-300 rounded-lg" readonly />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="setting-card bg-gray-50 rounded-lg p-5">
                                    <h3 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                                        <i class="fas fa-image mr-2 text-indigo-600"></i> Branding
                                    </h3>
                                    <div class="space-y-4">
                                        <div>
                                            <label for="logo_url" class="block text-sm font-medium text-gray-700 mb-1">Logo URL</label>
                                            <input type="text" name="logo_url" id="logo_url" value="<?= htmlspecialchars($currentSettings['logo_url']) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition" placeholder="https://example.com/logo.png" />
                                        </div>
                                        
                                        <div class="mt-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Logo Preview</label>
                                            <div class="border border-dashed border-gray-300 rounded-lg p-6 flex items-center justify-center h-32">
                                                <?php if ($currentSettings['logo_url']): ?>
                                                    <img src="<?= htmlspecialchars($currentSettings['logo_url']) ?>" alt="Logo Preview" class="max-h-16">
                                                <?php else: ?>
                                                    <div class="text-center text-gray-500">
                                                        <i class="fas fa-image text-3xl mb-2"></i>
                                                        <p>No logo uploaded</p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <button type="button" class="mt-2 w-full px-4 py-2 bg-gray-100 rounded-lg text-gray-700 hover:bg-gray-200">
                                                Upload New Logo
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Users & Roles Settings Tab -->
                        <div class="tab-content hidden" id="users-tab">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="setting-card bg-gray-50 rounded-lg p-5">
                                    <h3 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                                        <i class="fas fa-user-plus mr-2 text-indigo-600"></i> Registration
                                    </h3>
                                    <div class="space-y-4">
                                        <div class="flex items-center">
                                            <input type="checkbox" name="registration_enabled" id="registration_enabled" <?= $currentSettings['registration_enabled'] === '1' ? 'checked' : '' ?> class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                            <label for="registration_enabled" class="ml-2 block text-sm text-gray-900">Enable user registration</label>
                                        </div>
                                        
                                        <div class="flex items-center">
                                            <input type="checkbox" name="email_verification" id="email_verification" <?= $currentSettings['email_verification'] === '1' ? 'checked' : '' ?> class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                            <label for="email_verification" class="ml-2 block text-sm text-gray-900">Require email verification</label>
                                        </div>
                                        
                                        <div>
                                            <label for="default_user_role" class="block text-sm font-medium text-gray-700 mb-1">Default User Role</label>
                                            <select id="default_user_role" name="default_user_role" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                                                <?php foreach ($userRoles as $value => $label): ?>
                                                    <option value="<?= $value ?>" <?= $currentSettings['default_user_role'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="setting-card bg-gray-50 rounded-lg p-5">
                                    <h3 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                                        <i class="fas fa-shield-alt mr-2 text-indigo-600"></i> Roles & Permissions
                                    </h3>
                                    <div class="space-y-4">
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-100">
                                                    <tr>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Users</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Permissions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200">
                                                    <tr>
                                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">Administrator</td>
                                                        <td class="px-4 py-3 text-sm text-gray-500">5</td>
                                                        <td class="px-4 py-3 text-sm text-gray-500">Full access</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">Teacher</td>
                                                        <td class="px-4 py-3 text-sm text-gray-500">42</td>
                                                        <td class="px-4 py-3 text-sm text-gray-500">Course management</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">Student</td>
                                                        <td class="px-4 py-3 text-sm text-gray-500">1,201</td>
                                                        <td class="px-4 py-3 text-sm text-gray-500">Course access</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <button type="button" class="w-full mt-4 px-4 py-2 bg-gray-100 rounded-lg text-gray-700 hover:bg-gray-200">
                                            Manage Roles
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Email Settings Tab -->
                        <div class="tab-content hidden" id="email-tab">
                            <div class="grid grid-cols-1 gap-6">
                                <div class="setting-card bg-gray-50 rounded-lg p-5">
                                    <h3 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                                        <i class="fas fa-server mr-2 text-indigo-600"></i> SMTP Configuration
                                    </h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="smtp_host" class="block text-sm font-medium text-gray-700 mb-1">SMTP Host</label>
                                            <input type="text" name="smtp_host" id="smtp_host" value="<?= htmlspecialchars($currentSettings['smtp_host']) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition" placeholder="smtp.example.com" />
                                        </div>
                                        
                                        <div>
                                            <label for="smtp_port" class="block text-sm font-medium text-gray-700 mb-1">SMTP Port</label>
                                            <input type="number" name="smtp_port" id="smtp_port" value="<?= htmlspecialchars($currentSettings['smtp_port']) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition" placeholder="587" />
                                        </div>
                                        
                                        <div>
                                            <label for="smtp_username" class="block text-sm font-medium text-gray-700 mb-1">SMTP Username</label>
                                            <input type="text" name="smtp_username" id="smtp_username" value="<?= htmlspecialchars($currentSettings['smtp_username']) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition" placeholder="user@example.com" />
                                        </div>
                                        
                                        <div class="password-field">
                                            <label for="smtp_password" class="block text-sm font-medium text-gray-700 mb-1">SMTP Password</label>
                                            <input type="password" name="smtp_password" id="smtp_password" value="<?= htmlspecialchars($currentSettings['smtp_password']) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition" placeholder="••••••••" />
                                            <button type="button" class="password-toggle">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        
                                        <div>
                                            <label for="smtp_encryption" class="block text-sm font-medium text-gray-700 mb-1">Encryption</label>
                                            <select id="smtp_encryption" name="smtp_encryption" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                                                <?php foreach ($encryptionOptions as $value => $label): ?>
                                                    <option value="<?= $value ?>" <?= $currentSettings['smtp_encryption'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="md:col-span-2 mt-4">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label for="from_email" class="block text-sm font-medium text-gray-700 mb-1">From Email</label>
                                                    <input type="email" name="from_email" id="from_email" value="<?= htmlspecialchars($currentSettings['from_email']) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition" placeholder="noreply@example.com" />
                                                </div>
                                                
                                                <div>
                                                    <label for="from_name" class="block text-sm font-medium text-gray-700 mb-1">From Name</label>
                                                    <input type="text" name="from_name" id="from_name" value="<?= htmlspecialchars($currentSettings['from_name']) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition" placeholder="System Name" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="setting-card bg-gray-50 rounded-lg p-5">
                                    <h3 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                                        <i class="fas fa-paper-plane mr-2 text-indigo-600"></i> Test Email Configuration
                                    </h3>
                                    <div class="space-y-4">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label for="test_email" class="block text-sm font-medium text-gray-700 mb-1">Recipient Email</label>
                                                <input type="email" id="test_email" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="test@example.com" />
                                            </div>
                                            <div class="flex items-end">
                                                <button type="button" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                                    Send Test Email
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Security Settings Tab -->
                        <div class="tab-content hidden" id="security-tab">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="setting-card bg-gray-50 rounded-lg p-5">
                                    <h3 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                                        <i class="fas fa-lock mr-2 text-indigo-600"></i> Password & Authentication
                                    </h3>
                                    <div class="space-y-4">
                                        <div>
                                            <label for="password_strength" class="block text-sm font-medium text-gray-700 mb-1">Password Strength</label>
                                            <select id="password_strength" name="password_strength" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                                                <?php foreach ($passwordStrengths as $value => $label): ?>
                                                    <option value="<?= $value ?>" <?= $currentSettings['password_strength'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="flex items-center">
                                            <input type="checkbox" name="two_factor_auth" id="two_factor_auth" <?= $currentSettings['two_factor_auth'] === '1' ? 'checked' : '' ?> class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                            <label for="two_factor_auth" class="ml-2 block text-sm text-gray-900">Enable Two-Factor Authentication</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="setting-card bg-gray-50 rounded-lg p-5">
                                    <h3 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                                        <i class="fas fa-user-shield mr-2 text-indigo-600"></i> Login Security
                                    </h3>
                                    <div class="space-y-4">
                                        <div>
                                            <label for="login_attempts" class="block text-sm font-medium text-gray-700 mb-1">Max Login Attempts</label>
                                            <input type="number" name="login_attempts" id="login_attempts" value="<?= htmlspecialchars($currentSettings['login_attempts']) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition" min="1" max="20" />
                                        </div>
                                        
                                        <div>
                                            <label for="lockout_duration" class="block text-sm font-medium text-gray-700 mb-1">Lockout Duration (minutes)</label>
                                            <input type="number" name="lockout_duration" id="lockout_duration" value="<?= htmlspecialchars($currentSettings['lockout_duration']) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition" min="1" max="1440" />
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="setting-card bg-gray-50 rounded-lg p-5 md:col-span-2">
                                    <h3 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                                        <i class="fas fa-shield-alt mr-2 text-indigo-600"></i> Security Activity
                                    </h3>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                <tr>
                                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">Login Attempt</td>
                                                    <td class="px-4 py-3 text-sm text-gray-500">admin@example.com</td>
                                                    <td class="px-4 py-3 text-sm text-gray-500">192.168.1.1</td>
                                                    <td class="px-4 py-3 text-sm text-gray-500">2 min ago</td>
                                                    <td class="px-4 py-3 text-sm text-green-600">Success</td>
                                                </tr>
                                                <tr>
                                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">Password Change</td>
                                                    <td class="px-4 py-3 text-sm text-gray-500">jane.doe@example.com</td>
                                                    <td class="px-4 py-3 text-sm text-gray-500">10.0.0.5</td>
                                                    <td class="px-4 py-3 text-sm text-gray-500">15 min ago</td>
                                                    <td class="px-4 py-3 text-sm text-green-600">Success</td>
                                                </tr>
                                                <tr>
                                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">Login Attempt</td>
                                                    <td class="px-4 py-3 text-sm text-gray-500">unknown</td>
                                                    <td class="px-4 py-3 text-sm text-gray-500">45.76.123.98</td>
                                                    <td class="px-4 py-3 text-sm text-gray-500">1 hour ago</td>
                                                    <td class="px-4 py-3 text-sm text-red-600">Failed</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Advanced Settings Tab -->
                        <div class="tab-content hidden" id="advanced-tab">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="setting-card bg-gray-50 rounded-lg p-5">
                                    <h3 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                                        <i class="fas fa-tools mr-2 text-indigo-600"></i> System Configuration
                                    </h3>
                                    <div class="space-y-4">
                                        <div class="flex items-center">
                                            <input type="checkbox" name="maintenance_mode" id="maintenance_mode" <?= $currentSettings['maintenance_mode'] === '1' ? 'checked' : '' ?> class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                            <label for="maintenance_mode" class="ml-2 block text-sm text-gray-900">Enable Maintenance Mode</label>
                                        </div>
                                        
                                        <div class="flex items-center">
                                            <input type="checkbox" name="debug_mode" id="debug_mode" <?= $currentSettings['debug_mode'] === '1' ? 'checked' : '' ?> class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                            <label for="debug_mode" class="ml-2 block text-sm text-gray-900">Enable Debug Mode</label>
                                        </div>
                                        
                                        <div>
                                            <label for="google_analytics_id" class="block text-sm font-medium text-gray-700 mb-1">Google Analytics ID</label>
                                            <input type="text" name="google_analytics_id" id="google_analytics_id" value="<?= htmlspecialchars($currentSettings['google_analytics_id']) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition" placeholder="UA-XXXXX-Y" />
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="setting-card bg-gray-50 rounded-lg p-5">
                                    <h3 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                                        <i class="fas fa-chart-bar mr-2 text-indigo-600"></i> Analytics
                                    </h3>
                                    <div>
                                        <canvas id="analyticsChart" height="200"></canvas>
                                    </div>
                                    <div class="mt-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Data Retention</label>
                                        <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                                            <option>30 days</option>
                                            <option>90 days</option>
                                            <option>1 year</option>
                                            <option>Indefinitely</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Save button -->
                        <div class="pt-8 border-t border-gray-200 mt-8">
                            <button type="submit" class="relative px-6 py-3 border border-transparent rounded-lg shadow-sm text-white bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 font-medium transition group">
                                <span class="relative z-10">Save Settings</span>
                                <span class="absolute inset-0 bg-gradient-to-r from-indigo-700 to-blue-600 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity"></span>
                            </button>
                            
                            <button type="button" class="ml-4 px-6 py-3 border border-gray-300 rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                                Reset to Defaults
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Danger Zone -->
                <div class="mt-8 bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-red-600 to-rose-700">
                        <h2 class="text-xl font-semibold text-white">Danger Zone</h2>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Reset All Settings</h3>
                                <p class="mt-1 text-sm text-gray-500">Reset all settings to their default values. This cannot be undone.</p>
                            </div>
                            <button type="button" class="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition">
                                Reset Settings
                            </button>
                        </div>
                        
                        <div class="mt-6 pt-6 border-t border-gray-200 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Export System Data</h3>
                                <p class="mt-1 text-sm text-gray-500">Export all system data as a backup file</p>
                            </div>
                            <button type="button" class="px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                                Export Data
                            </button>
                        </div>
                        
                        <div class="mt-6 pt-6 border-t border-gray-200 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Clear All Data</h3>
                                <p class="mt-1 text-sm text-gray-500">Permanently delete all data and reset the system</p>
                            </div>
                            <button type="button" class="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition">
                                Clear Data
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>