-- ============================================================
-- Smart Blood Bank Management System
-- Database: blood_bank
-- ============================================================

CREATE DATABASE IF NOT EXISTS blood_bank;
USE blood_bank;

-- ============================================================
-- 1. Doctor Table
-- ============================================================
CREATE TABLE IF NOT EXISTS Doctor (
    doctor_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    specialization VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 2. Donor Table
-- ============================================================
CREATE TABLE IF NOT EXISTS Donor (
    donor_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    weight DECIMAL(5,2) NOT NULL,
    blood_type ENUM('A+','A-','B+','B-','O+','O-','AB+','AB-') NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    status ENUM('ELIGIBLE','DONATED','COOLING_PERIOD') DEFAULT 'ELIGIBLE',
    last_donation_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 3. Hospital Table
-- ============================================================
CREATE TABLE IF NOT EXISTS Hospital (
    hospital_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    contact_person VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 4. BloodUnit Table
-- ============================================================
CREATE TABLE IF NOT EXISTS BloodUnit (
    unit_id INT AUTO_INCREMENT PRIMARY KEY,
    donor_id INT NOT NULL,
    blood_type ENUM('A+','A-','B+','B-','O+','O-','AB+','AB-') NOT NULL,
    collection_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    status ENUM('COLLECTED','TESTED','AVAILABLE','RESERVED','USED','EXPIRED') DEFAULT 'COLLECTED',
    camp_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_id) REFERENCES Donor(donor_id) ON DELETE CASCADE
);

-- ============================================================
-- 5. Recipient Table
-- ============================================================
CREATE TABLE IF NOT EXISTS Recipient (
    recipient_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    age INT,
    blood_type ENUM('A+','A-','B+','B-','O+','O-','AB+','AB-') NOT NULL,
    hospital_id INT,
    phone VARCHAR(20),
    condition_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES Hospital(hospital_id) ON DELETE SET NULL
);

-- ============================================================
-- 6. Request Table
-- ============================================================
CREATE TABLE IF NOT EXISTS Request (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    hospital_id INT NOT NULL,
    recipient_id INT NULL,
    blood_type ENUM('A+','A-','B+','B-','O+','O-','AB+','AB-') NOT NULL,
    units_needed INT NOT NULL DEFAULT 1,
    urgency ENUM('NORMAL','URGENT','CRITICAL') DEFAULT 'NORMAL',
    status ENUM('PENDING','MATCHED','CONFIRMED','FULFILLED') DEFAULT 'PENDING',
    notes TEXT,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fulfilled_date DATETIME NULL,
    FOREIGN KEY (hospital_id) REFERENCES Hospital(hospital_id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES Recipient(recipient_id) ON DELETE SET NULL
);

-- ============================================================
-- 7. DonationCamp Table
-- ============================================================
CREATE TABLE IF NOT EXISTS DonationCamp (
    camp_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    location TEXT NOT NULL,
    camp_date DATE NOT NULL,
    capacity INT DEFAULT 50,
    doctor_id INT NULL,
    status ENUM('UPCOMING','ONGOING','COMPLETED','CANCELLED') DEFAULT 'UPCOMING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES Doctor(doctor_id) ON DELETE SET NULL
);

-- ============================================================
-- 8. Staff Table
-- ============================================================
CREATE TABLE IF NOT EXISTS Staff (
    staff_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    camp_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (camp_id) REFERENCES DonationCamp(camp_id) ON DELETE SET NULL
);

-- ============================================================
-- 9. BloodDemandEvent Table
-- ============================================================
CREATE TABLE IF NOT EXISTS BloodDemandEvent (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_type ENUM('TRAGEDY','SHORTAGE','DISASTER','OTHER') DEFAULT 'SHORTAGE',
    location VARCHAR(200),
    event_date DATE NOT NULL,
    status ENUM('ACTIVE','RESOLVED','CLOSED') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 10. BloodDemand Table
-- ============================================================
CREATE TABLE IF NOT EXISTS BloodDemand (
    demand_id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    blood_type ENUM('A+','A-','B+','B-','O+','O-','AB+','AB-') NOT NULL,
    units_required INT NOT NULL DEFAULT 1,
    units_available INT DEFAULT 0,
    urgency_level ENUM('LOW','MEDIUM','HIGH','CRITICAL') DEFAULT 'HIGH',
    FOREIGN KEY (event_id) REFERENCES BloodDemandEvent(event_id) ON DELETE CASCADE
);

-- ============================================================
-- Linking Table: Camp Donor Registration
-- ============================================================
CREATE TABLE IF NOT EXISTS CampDonorRegistration (
    reg_id INT AUTO_INCREMENT PRIMARY KEY,
    camp_id INT NOT NULL,
    donor_id INT NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    attended BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (camp_id) REFERENCES DonationCamp(camp_id) ON DELETE CASCADE,
    FOREIGN KEY (donor_id) REFERENCES Donor(donor_id) ON DELETE CASCADE
);

-- ============================================================
-- Linking Table: Request to BloodUnit (matched units)
-- ============================================================
CREATE TABLE IF NOT EXISTS RequestBloodUnit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    unit_id INT NOT NULL,
    FOREIGN KEY (request_id) REFERENCES Request(request_id) ON DELETE CASCADE,
    FOREIGN KEY (unit_id) REFERENCES BloodUnit(unit_id) ON DELETE CASCADE
);

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Doctors
INSERT INTO Doctor (name, specialization, phone, email) VALUES
('Dr. Anil Kumar', 'Hematology', '9876543210', 'anil.kumar@bloodbank.org'),
('Dr. Priya Sharma', 'Transfusion Medicine', '9876543211', 'priya.sharma@bloodbank.org'),
('Dr. Rahul Mehta', 'Internal Medicine', '9876543212', 'rahul.mehta@bloodbank.org');

-- Hospitals
INSERT INTO Hospital (name, address, phone, email, contact_person) VALUES
('City General Hospital', '12 MG Road, Delhi', '011-23456789', 'blood@citygeneral.in', 'Dr. Sunita Joshi'),
('Apollo Medical Center', '45 Banjara Hills, Hyderabad', '040-12345678', 'transfusion@apollo.in', 'Dr. Veer Singh'),
('St. Mary Hospital', '78 Park Street, Kolkata', '033-98765432', 'bloodbank@stmary.in', 'Sr. Angela');

-- Donors
INSERT INTO Donor (name, age, weight, blood_type, phone, email, status, last_donation_date) VALUES
('Rajesh Nair', 32, 75.5, 'O+', '9812345670', 'rajesh.nair@email.com', 'ELIGIBLE', '2025-12-01'),
('Suman Verma', 28, 62.0, 'A+', '9812345671', 'suman.verma@email.com', 'ELIGIBLE', '2025-11-15'),
('Anjali Singh', 25, 54.0, 'B+', '9812345672', 'anjali.singh@email.com', 'COOLING_PERIOD', '2026-02-15'),
('Mohan Das', 35, 80.0, 'AB+', '9812345673', 'mohan.das@email.com', 'ELIGIBLE', '2025-10-20'),
('Kavita Patel', 22, 58.0, 'O-', '9812345674', 'kavita.patel@email.com', 'ELIGIBLE', '2025-12-10'),
('Arun Kumar', 40, 70.0, 'A-', '9812345675', 'arun.kumar@email.com', 'ELIGIBLE', '2025-09-05'),
('Preethi Rajan', 30, 65.0, 'B-', '9812345676', 'preethi.rajan@email.com', 'ELIGIBLE', '2025-11-01'),
('Deepak Shetty', 45, 85.0, 'AB-', '9812345677', 'deepak.shetty@email.com', 'ELIGIBLE', '2025-08-20'),
('Nisha Gupta', 27, 55.0, 'O+', '9812345678', 'nisha.gupta@email.com', 'ELIGIBLE', '2025-12-25'),
('Vivek Sharma', 33, 72.0, 'A+', '9812345679', 'vivek.sharma@email.com', 'ELIGIBLE', '2025-10-10');

-- Blood Units (sample inventory)
INSERT INTO BloodUnit (donor_id, blood_type, collection_date, expiry_date, status) VALUES
(1, 'O+', '2026-02-01', '2026-03-08', 'AVAILABLE'),
(1, 'O+', '2026-02-20', '2026-03-27', 'AVAILABLE'),
(2, 'A+', '2026-02-05', '2026-03-12', 'AVAILABLE'),
(2, 'A+', '2026-02-18', '2026-03-25', 'AVAILABLE'),
(3, 'B+', '2026-02-15', '2026-03-22', 'AVAILABLE'),
(4, 'AB+', '2026-01-25', '2026-03-01', 'AVAILABLE'),
(5, 'O-', '2026-02-10', '2026-03-17', 'AVAILABLE'),
(6, 'A-', '2026-02-12', '2026-03-19', 'AVAILABLE'),
(7, 'B-', '2026-02-08', '2026-03-15', 'AVAILABLE'),
(8, 'AB-', '2026-02-03', '2026-03-10', 'AVAILABLE'),
(9, 'O+', '2026-02-22', '2026-03-29', 'AVAILABLE'),
(10, 'A+', '2026-02-14', '2026-03-21', 'TESTED'),
(1, 'O+', '2026-03-01', '2026-04-05', 'COLLECTED'),
(4, 'AB+', '2026-03-02', '2026-04-06', 'TESTED');

-- Donation Camps
INSERT INTO DonationCamp (name, location, camp_date, capacity, doctor_id, status) VALUES
('Spring Life Camp 2026', 'Community Hall, Sector 15, Delhi', '2026-03-20', 100, 1, 'UPCOMING'),
('City Blood Drive', 'Apollo Center Parking, Hyderabad', '2026-03-15', 75, 2, 'UPCOMING'),
('Emergency Camp - March', 'St. Mary Hall, Kolkata', '2026-02-28', 50, 3, 'COMPLETED');

-- Requests
INSERT INTO Request (hospital_id, blood_type, units_needed, urgency, status) VALUES
(1, 'O+', 2, 'URGENT', 'PENDING'),
(2, 'A+', 1, 'NORMAL', 'MATCHED'),
(3, 'AB-', 3, 'CRITICAL', 'PENDING');

-- Blood Demand Events
INSERT INTO BloodDemandEvent (title, description, event_type, location, event_date, status) VALUES
('Road Accident — NH-8 Mass Casualty', 'Multi-vehicle accident on National Highway 8 with 15+ injured.', 'TRAGEDY', 'NH-8, Rajasthan', '2026-03-08', 'ACTIVE'),
('Seasonal O- Shortage', 'Seasonal drop in O- donors leading to critical shortage.', 'SHORTAGE', 'Pan India', '2026-03-05', 'ACTIVE');

-- Blood Demands
INSERT INTO BloodDemand (event_id, blood_type, units_required, urgency_level) VALUES
(1, 'O+', 10, 'CRITICAL'),
(1, 'A+', 5, 'HIGH'),
(1, 'B+', 5, 'HIGH'),
(2, 'O-', 8, 'CRITICAL');
