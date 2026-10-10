CREATE DATABASE IF NOT EXISTS `car_rental_db`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `car_rental_db`;

CREATE TABLE IF NOT EXISTS admins (
  admin_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cars (
  car_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  brand VARCHAR(80) NOT NULL,
  model VARCHAR(80) NOT NULL,
  license_plate VARCHAR(32) NOT NULL UNIQUE,
  daily_rate DECIMAL(10,2) NOT NULL,
  status ENUM('available','rented') NOT NULL DEFAULT 'available'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS clients (
  client_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(80) NOT NULL,
  last_name VARCHAR(80) NOT NULL,
  phone VARCHAR(40) NOT NULL,
  driver_license VARCHAR(60) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS employees (
  employee_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(80) NOT NULL,
  last_name VARCHAR(80) NOT NULL,
  position VARCHAR(80) NOT NULL,
  phone VARCHAR(40) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rentals (
  rental_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id INT UNSIGNED NOT NULL,
  car_id INT UNSIGNED NOT NULL,
  employee_id INT UNSIGNED NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  status ENUM('active','completed','cancelled') NOT NULL DEFAULT 'active',
  CONSTRAINT fk_rentals_client FOREIGN KEY (client_id) REFERENCES clients(client_id) ON DELETE RESTRICT,
  CONSTRAINT fk_rentals_car FOREIGN KEY (car_id) REFERENCES cars(car_id) ON DELETE RESTRICT,
  CONSTRAINT fk_rentals_employee FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
  payment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rental_id INT UNSIGNED NOT NULL,
  payment_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  amount DECIMAL(10,2) NOT NULL,
  payment_method ENUM('card','cash','transfer') NOT NULL,
  CONSTRAINT fk_payments_rental FOREIGN KEY (rental_id) REFERENCES rentals(rental_id) ON DELETE RESTRICT
) ENGINE=InnoDB;
