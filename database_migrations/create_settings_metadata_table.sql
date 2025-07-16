-- Migration to create settings_metadata table
CREATE TABLE settings_metadata (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    label VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    options TEXT NULL,
    category VARCHAR(100) NOT NULL,
    default_value TEXT NULL
);

-- Insert metadata for existing settings
INSERT INTO settings_metadata (setting_key, label, type, options, category, default_value) VALUES
('site_name', 'Site Name', 'text', NULL, 'general', 'EduAdmin'),
('admin_email', 'Admin Email', 'email', NULL, 'general', 'admin@example.com'),
('timezone', 'Timezone', 'select', '["America/New_York","America/Chicago","America/Denver","America/Los_Angeles","Europe/London","Europe/Paris","Asia/Tokyo","Asia/Dubai","Australia/Sydney"]', 'general', 'America/New_York'),
('date_format', 'Date Format', 'select', '["F j, Y","m/d/Y","d/m/Y","Y-m-d"]', 'general', 'F j, Y'),
('time_format', 'Time Format', 'select', '["g:i a","H:i"]', 'general', 'g:i a'),
('theme', 'Theme', 'select', '["light","dark","system"]', 'appearance', 'light'),
('primary_color', 'Primary Color', 'color', NULL, 'appearance', '#3b82f6'),
('logo_url', 'Logo URL', 'text', NULL, 'appearance', ''),
('registration_enabled', 'Enable user registration', 'checkbox', NULL, 'users', '1'),
('email_verification', 'Require email verification', 'checkbox', NULL, 'users', '1'),
('default_user_role', 'Default User Role', 'select', '["admin","teacher","student","user"]', 'users', 'student'),
('smtp_host', 'SMTP Host', 'text', NULL, 'email', 'smtp.example.com'),
('smtp_port', 'SMTP Port', 'number', NULL, 'email', '587'),
('smtp_username', 'SMTP Username', 'text', NULL, 'email', 'user@example.com'),
('smtp_password', 'SMTP Password', 'password', NULL, 'email', ''),
('smtp_encryption', 'Encryption', 'select', '["none","ssl","tls"]', 'email', 'tls'),
('from_email', 'From Email', 'email', NULL, 'email', 'noreply@example.com'),
('from_name', 'From Name', 'text', NULL, 'email', 'EduAdmin System'),
('password_strength', 'Password Strength', 'select', '["low","medium","high"]', 'security', 'medium'),
('two_factor_auth', 'Enable Two-Factor Authentication', 'checkbox', NULL, 'security', '0'),
('login_attempts', 'Max Login Attempts', 'number', NULL, 'security', '5'),
('lockout_duration', 'Lockout Duration (minutes)', 'number', NULL, 'security', '15'),
('maintenance_mode', 'Enable Maintenance Mode', 'checkbox', NULL, 'advanced', '0'),
('debug_mode', 'Enable Debug Mode', 'checkbox', NULL, 'advanced', '0'),
('google_analytics_id', 'Google Analytics ID', 'text', NULL, 'advanced', '');
