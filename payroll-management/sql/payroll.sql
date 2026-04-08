-- ============================================
-- Payroll Management System - Database Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS `payroll_management` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `payroll_management`;

-- Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `full_name` VARCHAR(150) NOT NULL,
  `role` ENUM('admin','hr','accountant') NOT NULL DEFAULT 'hr',
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Departments Table
CREATE TABLE IF NOT EXISTS `departments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Designations Table
CREATE TABLE IF NOT EXISTS `designations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(100) NOT NULL,
  `department_id` INT(11) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Employees Table
CREATE TABLE IF NOT EXISTS `employees` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `employee_id` VARCHAR(20) NOT NULL UNIQUE,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(20) DEFAULT NULL,
  `gender` ENUM('Male','Female','Other') DEFAULT 'Male',
  `date_of_birth` DATE DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `state` VARCHAR(100) DEFAULT NULL,
  `zip_code` VARCHAR(20) DEFAULT NULL,
  `department_id` INT(11) DEFAULT NULL,
  `designation_id` INT(11) DEFAULT NULL,
  `join_date` DATE DEFAULT NULL,
  `employment_type` ENUM('Full-Time','Part-Time','Contract','Intern') DEFAULT 'Full-Time',
  `bank_name` VARCHAR(150) DEFAULT NULL,
  `account_number` VARCHAR(50) DEFAULT NULL,
  `ifsc_code` VARCHAR(30) DEFAULT NULL,
  `pan_number` VARCHAR(20) DEFAULT NULL,
  `photo` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('Active','Inactive','Terminated') DEFAULT 'Active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`designation_id`) REFERENCES `designations`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Salary Structures Table
CREATE TABLE IF NOT EXISTS `salary_structures` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `employee_id` INT(11) NOT NULL,
  `basic_salary` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `hra` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `medical_allowance` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `transport_allowance` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `other_allowance` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `pf_deduction` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `esi_deduction` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `income_tax` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `other_deductions` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `effective_date` DATE NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Attendance Table
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `employee_id` INT(11) NOT NULL,
  `attendance_date` DATE NOT NULL,
  `check_in` TIME DEFAULT NULL,
  `check_out` TIME DEFAULT NULL,
  `status` ENUM('Present','Absent','Half Day','On Leave','Holiday') DEFAULT 'Present',
  `remarks` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `emp_date` (`employee_id`, `attendance_date`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Leave Types Table
CREATE TABLE IF NOT EXISTS `leave_types` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `days_allowed` INT(11) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Leaves Table
CREATE TABLE IF NOT EXISTS `leaves` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `employee_id` INT(11) NOT NULL,
  `leave_type_id` INT(11) NOT NULL,
  `from_date` DATE NOT NULL,
  `to_date` DATE NOT NULL,
  `total_days` INT(11) NOT NULL DEFAULT 1,
  `reason` TEXT DEFAULT NULL,
  `status` ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
  `approved_by` INT(11) DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payroll Table
CREATE TABLE IF NOT EXISTS `payroll` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `employee_id` INT(11) NOT NULL,
  `pay_month` TINYINT(2) NOT NULL,
  `pay_year` YEAR NOT NULL,
  `working_days` INT(11) NOT NULL DEFAULT 26,
  `present_days` INT(11) NOT NULL DEFAULT 26,
  `basic_salary` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `hra` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `medical_allowance` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `transport_allowance` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `other_allowance` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `gross_salary` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `pf_deduction` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `esi_deduction` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `income_tax` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `other_deductions` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `total_deductions` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `net_salary` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `payment_date` DATE DEFAULT NULL,
  `payment_mode` ENUM('Bank Transfer','Cash','Cheque') DEFAULT 'Bank Transfer',
  `status` ENUM('Generated','Paid','Cancelled') DEFAULT 'Generated',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `emp_month_year` (`employee_id`, `pay_month`, `pay_year`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Settings Table
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `key_name` VARCHAR(100) NOT NULL UNIQUE,
  `key_value` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Default Data
-- ============================================

-- Admin user (password: admin123)
INSERT INTO `users` (`username`, `password`, `email`, `full_name`, `role`) VALUES
('admin', '$2y$12$R6CJs7mORtOl83c9ijwftulSIKfy9To439SRq185qm6IAr2HfRoM2', 'admin@payroll.com', 'Administrator', 'admin');

-- Note: The above password hash is for 'admin123'
-- Generated with: password_hash('admin123', PASSWORD_DEFAULT)

-- Departments
INSERT INTO `departments` (`name`, `description`) VALUES
('Human Resources', 'Manages employee relations and recruitment'),
('Information Technology', 'Handles technology infrastructure and development'),
('Finance', 'Manages financial operations and accounts'),
('Marketing', 'Handles marketing and brand promotion'),
('Operations', 'Manages day-to-day business operations'),
('Sales', 'Handles sales and client relations');

-- Designations
INSERT INTO `designations` (`title`, `department_id`) VALUES
('HR Manager', 1),
('HR Executive', 1),
('Software Engineer', 2),
('Senior Developer', 2),
('Team Lead', 2),
('Finance Manager', 3),
('Accountant', 3),
('Marketing Manager', 4),
('Marketing Executive', 4),
('Operations Manager', 5),
('Operations Executive', 5),
('Sales Manager', 6),
('Sales Executive', 6);

-- Leave Types
INSERT INTO `leave_types` (`name`, `days_allowed`) VALUES
('Casual Leave', 12),
('Sick Leave', 10),
('Earned Leave', 15),
('Maternity Leave', 180),
('Paternity Leave', 15),
('Unpaid Leave', 0);

-- Settings
INSERT INTO `settings` (`key_name`, `key_value`) VALUES
('company_name', 'PayRoll Pro Inc.'),
('company_address', '123 Business Street, City, State - 000000'),
('company_phone', '+1 (555) 000-0000'),
('company_email', 'hr@company.com'),
('company_website', 'www.company.com'),
('currency_symbol', '$'),
('working_days_per_month', '26'),
('financial_year_start', '04'),
('pf_percentage', '12'),
('esi_percentage', '1.75');

-- Sample Employees
INSERT INTO `employees` (`employee_id`, `first_name`, `last_name`, `email`, `phone`, `gender`, `date_of_birth`, `address`, `city`, `state`, `department_id`, `designation_id`, `join_date`, `employment_type`, `bank_name`, `account_number`, `ifsc_code`, `pan_number`, `status`) VALUES
('EMP001', 'John', 'Smith', 'john.smith@company.com', '9876543210', 'Male', '1990-05-15', '45 Oak Street', 'New York', 'NY', 2, 4, '2020-01-15', 'Full-Time', 'City Bank', '1234567890', 'CITI0001234', 'ABCDE1234F', 'Active'),
('EMP002', 'Sarah', 'Johnson', 'sarah.johnson@company.com', '9876543211', 'Female', '1992-08-22', '12 Pine Avenue', 'Los Angeles', 'CA', 1, 1, '2019-06-01', 'Full-Time', 'Chase Bank', '2345678901', 'CHAS0002345', 'FGHIJ5678K', 'Active'),
('EMP003', 'Michael', 'Williams', 'michael.w@company.com', '9876543212', 'Male', '1988-03-10', '78 Maple Drive', 'Chicago', 'IL', 3, 7, '2021-03-20', 'Full-Time', 'Wells Fargo', '3456789012', 'WELL0003456', 'LMNOP9012L', 'Active'),
('EMP004', 'Emily', 'Brown', 'emily.brown@company.com', '9876543213', 'Female', '1995-11-30', '23 Elm Court', 'Houston', 'TX', 4, 8, '2022-07-10', 'Full-Time', 'Bank of America', '4567890123', 'BOFA0004567', 'QRSTU3456M', 'Active'),
('EMP005', 'David', 'Jones', 'david.jones@company.com', '9876543214', 'Male', '1987-07-05', '56 Cedar Lane', 'Phoenix', 'AZ', 6, 12, '2018-11-01', 'Full-Time', 'City Bank', '5678901234', 'CITI0005678', 'VWXYZ7890N', 'Active');

-- Salary Structures for sample employees
INSERT INTO `salary_structures` (`employee_id`, `basic_salary`, `hra`, `medical_allowance`, `transport_allowance`, `other_allowance`, `pf_deduction`, `esi_deduction`, `income_tax`, `other_deductions`, `effective_date`) VALUES
(1, 5000.00, 2000.00, 500.00, 300.00, 200.00, 600.00, 87.50, 250.00, 0, '2023-01-01'),
(2, 6000.00, 2400.00, 500.00, 300.00, 300.00, 720.00, 105.00, 350.00, 0, '2023-01-01'),
(3, 4500.00, 1800.00, 500.00, 300.00, 200.00, 540.00, 78.75, 180.00, 0, '2023-01-01'),
(4, 4000.00, 1600.00, 500.00, 300.00, 100.00, 480.00, 70.00, 150.00, 0, '2023-01-01'),
(5, 7000.00, 2800.00, 500.00, 300.00, 400.00, 840.00, 122.50, 500.00, 0, '2023-01-01');
