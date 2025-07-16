CREATE TABLE fee_types (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE fees (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fee_type_id INT NOT NULL,
  class_id INT DEFAULT NULL,
  amount DECIMAL(10,2) NOT NULL,
  due_date DATE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (fee_type_id) REFERENCES fee_types(id) ON DELETE CASCADE,
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL
);

CREATE TABLE student_fees (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  fee_id INT NOT NULL,
  amount_paid DECIMAL(10,2) DEFAULT 0,
  payment_status ENUM('pending', 'paid', 'partial') DEFAULT 'pending',
  payment_date TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES student_profiles(id) ON DELETE CASCADE,
  FOREIGN KEY (fee_id) REFERENCES fees(id) ON DELETE CASCADE
);
