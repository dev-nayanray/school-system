CREATE TABLE profile_updates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  updated_fields VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
