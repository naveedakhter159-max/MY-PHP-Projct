-- ============================================================
-- Payroll Management System - Database Schema (v2)
-- ============================================================

CREATE DATABASE IF NOT EXISTS `payroll_management` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `payroll_management`;

-- Users
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `full_name` VARCHAR(150) NOT NULL,
  `role` ENUM('admin','hr','accountant') DEFAULT 'admin',
  `avatar` VARCHAR(255) DEFAULT NULL,
  `status` TINYINT DEFAULT 1,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Companies
CREATE TABLE IF NOT EXISTS `companies` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `industry` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `state` VARCHAR(100) DEFAULT NULL,
  `zip` VARCHAR(20) DEFAULT NULL,
  `country` VARCHAR(100) DEFAULT 'USA',
  `website` VARCHAR(200) DEFAULT NULL,
  `status` ENUM('Active','Inactive','Suspended','Pending') DEFAULT 'Active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Departments
CREATE TABLE IF NOT EXISTS `departments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT DEFAULT NULL,
  `name` VARCHAR(100) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Employees
CREATE TABLE IF NOT EXISTS `employees` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` VARCHAR(20) NOT NULL UNIQUE,
  `company_id` INT DEFAULT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(30) DEFAULT NULL,
  `gender` ENUM('Male','Female','Other') DEFAULT 'Male',
  `date_of_birth` DATE DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `state` VARCHAR(100) DEFAULT NULL,
  `department` VARCHAR(100) DEFAULT NULL,
  `position` VARCHAR(100) DEFAULT NULL,
  `salary` DECIMAL(12,2) DEFAULT 0,
  `start_date` DATE DEFAULT NULL,
  `employment_type` ENUM('Full-Time','Part-Time','Contract','Intern') DEFAULT 'Full-Time',
  `bank_name` VARCHAR(150) DEFAULT NULL,
  `account_number` VARCHAR(50) DEFAULT NULL,
  `routing_number` VARCHAR(30) DEFAULT NULL,
  `ssn_last4` VARCHAR(4) DEFAULT NULL,
  `status` ENUM('Active','Inactive','Terminated') DEFAULT 'Active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tax Settings
CREATE TABLE IF NOT EXISTS `tax_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tax_name` VARCHAR(100) NOT NULL,
  `tax_type` ENUM('Federal','State','Local','FICA','Medicare') DEFAULT 'Federal',
  `rate` DECIMAL(6,3) NOT NULL DEFAULT 0,
  `description` TEXT DEFAULT NULL,
  `applies_to` ENUM('All Employees','Employer','Employee') DEFAULT 'All Employees',
  `status` TINYINT DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payroll Runs
CREATE TABLE IF NOT EXISTS `payroll` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `employee_id` INT NOT NULL,
  `period` VARCHAR(50) NOT NULL,
  `pay_month` TINYINT NOT NULL,
  `pay_year` YEAR NOT NULL,
  `basic_salary` DECIMAL(12,2) DEFAULT 0,
  `overtime` DECIMAL(12,2) DEFAULT 0,
  `bonus` DECIMAL(12,2) DEFAULT 0,
  `allowances` DECIMAL(12,2) DEFAULT 0,
  `gross_salary` DECIMAL(12,2) DEFAULT 0,
  `federal_tax` DECIMAL(12,2) DEFAULT 0,
  `state_tax` DECIMAL(12,2) DEFAULT 0,
  `social_security` DECIMAL(12,2) DEFAULT 0,
  `medicare` DECIMAL(12,2) DEFAULT 0,
  `other_deductions` DECIMAL(12,2) DEFAULT 0,
  `total_deductions` DECIMAL(12,2) DEFAULT 0,
  `net_salary` DECIMAL(12,2) DEFAULT 0,
  `payment_date` DATE DEFAULT NULL,
  `payment_mode` ENUM('Direct Deposit','Check','Cash') DEFAULT 'Direct Deposit',
  `status` ENUM('Generated','Paid','Pending','Cancelled') DEFAULT 'Generated',
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `emp_period` (`employee_id`,`pay_month`,`pay_year`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Leave Types
CREATE TABLE IF NOT EXISTS `leave_types` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `days_allowed` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Leaves
CREATE TABLE IF NOT EXISTS `leaves` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `leave_type_id` INT NOT NULL,
  `from_date` DATE NOT NULL,
  `to_date` DATE NOT NULL,
  `total_days` INT DEFAULT 1,
  `reason` TEXT DEFAULT NULL,
  `status` ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Attendance
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `attendance_date` DATE NOT NULL,
  `check_in` TIME DEFAULT NULL,
  `check_out` TIME DEFAULT NULL,
  `status` ENUM('Present','Absent','Half Day','On Leave','Holiday') DEFAULT 'Present',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `emp_date` (`employee_id`,`attendance_date`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Settings
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `key_name` VARCHAR(100) NOT NULL UNIQUE,
  `key_value` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Per-Employee Tax Settings
CREATE TABLE IF NOT EXISTS `employee_tax_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL UNIQUE,
  -- Federal Tax
  `filing_status` ENUM('Single','Married Filing Jointly','Married Filing Separately','Head of Household') DEFAULT 'Single',
  `multiple_jobs` TINYINT DEFAULT 0,
  `tax_exempt` TINYINT DEFAULT 0,
  `dependents_count` INT DEFAULT 0,
  `extra_withholding` DECIMAL(10,2) DEFAULT 0,
  `other_income` DECIMAL(10,2) DEFAULT 0,
  `fed_deductions` DECIMAL(10,2) DEFAULT 0,
  -- State Tax
  `state_code` VARCHAR(10) DEFAULT '',
  `state_filing_status` ENUM('Single','Married') DEFAULT 'Single',
  `state_allowances` INT DEFAULT 0,
  `state_extra_withholding` DECIMAL(10,2) DEFAULT 0,
  `disability_insurance` TINYINT DEFAULT 0,
  -- Local Tax
  `local_tax_enabled` TINYINT DEFAULT 0,
  `local_city` VARCHAR(100) DEFAULT '',
  `local_tax_rate` DECIMAL(6,3) DEFAULT 0,
  -- Deductions
  `contrib_401k_pct` DECIMAL(6,3) DEFAULT 0,
  `health_insurance` DECIMAL(10,2) DEFAULT 0,
  `other_deduction_name` VARCHAR(100) DEFAULT '',
  `other_deduction_amount` DECIMAL(10,2) DEFAULT 0,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Default Data
-- ============================================================

-- Admin user (password: admin123)
INSERT INTO `users` (`username`,`password`,`email`,`full_name`,`role`) VALUES
('admin','$2y$12$R6CJs7mORtOl83c9ijwftulSIKfy9To439SRq185qm6IAr2HfRoM2','admin@payroll.com','Administrator','admin');

-- Sample Companies
INSERT INTO `companies` (`company_id`,`name`,`industry`,`email`,`phone`,`city`,`state`,`status`) VALUES
('Comp-001','AeroMax Manufacturing','Textile','contact@aeromax.com','555-0101','New York','NY','Active'),
('Comp-002','UrbanNest Properties','IT Firm','info@urbannest.com','555-0102','Los Angeles','CA','Active'),
('Comp-003','HealthSphere Services','Pharmaceutical','admin@healthsphere.com','555-0103','Chicago','IL','Active'),
('Comp-004','HyperLink Digital','Textile','hello@hyperlink.com','555-0104','Houston','TX','Suspended'),
('Comp-005','SkyNet Retail Corp','Retail','contact@skynet.com','555-0105','Phoenix','AZ','Active'),
('Comp-006','PrimeCore Industries','Manufacturing','info@primecore.com','555-0106','Philadelphia','PA','Active'),
('Comp-007','Google LLC','Technology','admin@google.com','555-0107','San Francisco','CA','Active'),
('Comp-008','NextGen Finance','Finance','contact@nextgen.com','555-0108','San Antonio','TX','Pending');

-- Sample Employees
INSERT INTO `employees` (`employee_id`,`company_id`,`first_name`,`last_name`,`email`,`phone`,`department`,`position`,`salary`,`start_date`,`status`) VALUES
('EMP-001',1,'John','Smith','john.smith@aeromax.com','555-1001','Production','Senior Engineer',45000.00,'2021-01-15','Active'),
('EMP-002',1,'Sarah','Johnson','sarah.j@aeromax.com','555-1002','HR','HR Manager',38000.00,'2020-06-01','Active'),
('EMP-003',1,'Michael','Williams','michael.w@aeromax.com','555-1003','Finance','Accountant',35000.00,'2021-03-20','Active'),
('EMP-004',5,'Emily','Brown','emily.b@skynet.com','555-1004','Sales','Sales Executive',32000.00,'2022-07-10','Active'),
('EMP-005',7,'David','Jones','david.j@google.com','555-1005','IT','Developer',52000.00,'2019-11-01','Active');

-- Tax Settings
INSERT INTO `tax_settings` (`tax_name`,`tax_type`,`rate`,`description`,`applies_to`) VALUES
('Federal Income Tax','Federal',22.000,'Standard federal income tax rate','All Employees'),
('Social Security','FICA',6.200,'Social Security tax (OASDI)','All Employees'),
('Medicare','Medicare',1.450,'Medicare tax rate','All Employees'),
('State Income Tax','State',5.000,'State income tax (average)','All Employees'),
('Employer FICA Match','FICA',6.200,'Employer Social Security match','Employer');

-- Leave Types
INSERT INTO `leave_types` (`name`,`days_allowed`) VALUES
('Annual Leave',15),('Sick Leave',10),('Casual Leave',7),('Maternity Leave',90),('Unpaid Leave',0);

-- Settings
INSERT INTO `settings` (`key_name`,`key_value`) VALUES
('company_name','Payroll System'),
('currency','USD'),
('currency_symbol','$'),
('date_format','M d, Y'),
('working_days',26),
('fiscal_year_start','January'),
('payroll_frequency','Monthly'),
('overtime_rate','1.5');
