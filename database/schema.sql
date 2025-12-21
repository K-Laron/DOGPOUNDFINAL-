-- =====================================================
-- CATARMAN DOG POUND MANAGEMENT SYSTEM DATABASE
-- =====================================================

-- Create database
CREATE DATABASE IF NOT EXISTS catarman_dog_pound_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE catarman_dog_pound_db;

-- =====================================================
-- 1. USER MANAGEMENT & SECURITY
-- =====================================================

-- Roles Table
CREATE TABLE IF NOT EXISTS Roles (
    RoleID INT PRIMARY KEY AUTO_INCREMENT,
    Role_Name VARCHAR(50) NOT NULL UNIQUE,
    Created_At DATETIME DEFAULT CURRENT_TIMESTAMP,
    Updated_At DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Users Table
CREATE TABLE IF NOT EXISTS Users (
    UserID INT PRIMARY KEY AUTO_INCREMENT,
    RoleID INT NOT NULL,
    FirstName VARCHAR(50) NOT NULL,
    LastName VARCHAR(50) NOT NULL,
    Username VARCHAR(50) NOT NULL UNIQUE,
    Email VARCHAR(100) NOT NULL UNIQUE,
    Contact_Number VARCHAR(20),
    Address TEXT,
    Avatar_Url VARCHAR(255),
    Password_Hash VARCHAR(255) NOT NULL,
    Account_Status ENUM('Active', 'Inactive', 'Banned') DEFAULT 'Active',
    Is_Deleted BOOLEAN DEFAULT FALSE,
    Created_At DATETIME DEFAULT CURRENT_TIMESTAMP,
    Updated_At DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (RoleID) REFERENCES Roles(RoleID) ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Veterinarians Table
CREATE TABLE IF NOT EXISTS Veterinarians (
    VetID INT PRIMARY KEY AUTO_INCREMENT,
    UserID INT NOT NULL UNIQUE,
    License_Number VARCHAR(50) NOT NULL,
    Specialization VARCHAR(100),
    Years_Experience INT DEFAULT 0,
    Created_At DATETIME DEFAULT CURRENT_TIMESTAMP,
    Updated_At DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 2. ANIMAL PROFILING
-- =====================================================

-- Animals Table
CREATE TABLE IF NOT EXISTS Animals (
    AnimalID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(50) NOT NULL,
    Type ENUM('Dog', 'Cat', 'Other') NOT NULL,
    Breed VARCHAR(50),
    Gender ENUM('Male', 'Female', 'Unknown') DEFAULT 'Unknown',
    Age_Group VARCHAR(20),
    Weight DECIMAL(5,2),
    Intake_Date DATETIME DEFAULT CURRENT_TIMESTAMP,
    Intake_Status ENUM('Stray', 'Surrendered', 'Confiscated') NOT NULL,
    Current_Status ENUM('Available', 'Adopted', 'Deceased', 'In Treatment', 'Quarantine', 'Reclaimed') DEFAULT 'Available',
    Image_URL VARCHAR(255),
    Is_Deleted BOOLEAN DEFAULT FALSE,
    Created_At DATETIME DEFAULT CURRENT_TIMESTAMP,
    Updated_At DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Impound Records Table
CREATE TABLE IF NOT EXISTS Impound_Records (
    ImpoundID INT PRIMARY KEY AUTO_INCREMENT,
    AnimalID INT NOT NULL,
    Capture_Date DATETIME NOT NULL,
    Location_Found VARCHAR(255) NOT NULL,
    Impounding_Officer VARCHAR(100) NOT NULL,
    Condition_On_Arrival TEXT,
    Created_At DATETIME DEFAULT CURRENT_TIMESTAMP,
    Updated_At DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (AnimalID) REFERENCES Animals(AnimalID) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 3. MEDICAL & HEALTH
-- =====================================================

-- Medical Records Table
CREATE TABLE IF NOT EXISTS Medical_Records (
    RecordID INT PRIMARY KEY AUTO_INCREMENT,
    AnimalID INT NOT NULL,
    VetID INT NOT NULL,
    Date_Performed DATETIME DEFAULT CURRENT_TIMESTAMP,
    Diagnosis_Type ENUM('Checkup', 'Vaccination', 'Surgery', 'Treatment', 'Emergency', 'Deworming', 'Spay/Neuter') NOT NULL,
    Vaccine_Name VARCHAR(100),
    Treatment_Notes TEXT,
    Next_Due_Date DATE,
    Created_At DATETIME DEFAULT CURRENT_TIMESTAMP,
    Updated_At DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (AnimalID) REFERENCES Animals(AnimalID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (VetID) REFERENCES Veterinarians(VetID) ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Feeding Records Table
CREATE TABLE IF NOT EXISTS Feeding_Records (
    FeedingID INT PRIMARY KEY AUTO_INCREMENT,
    AnimalID INT NOT NULL,
    Fed_By_UserID INT NOT NULL,
    Feeding_Time DATETIME DEFAULT CURRENT_TIMESTAMP,
    Food_Type VARCHAR(50) NOT NULL,
    Quantity_Used DECIMAL(5,2) NOT NULL,
    Created_At DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (AnimalID) REFERENCES Animals(AnimalID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (Fed_By_UserID) REFERENCES Users(UserID) ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 4. ADOPTION MODULE
-- =====================================================

-- Adoption Requests Table
CREATE TABLE IF NOT EXISTS Adoption_Requests (
    RequestID INT PRIMARY KEY AUTO_INCREMENT,
    AnimalID INT NOT NULL,
    Adopter_UserID INT NOT NULL,
    Request_Date DATETIME DEFAULT CURRENT_TIMESTAMP,
    Status ENUM('Pending', 'Interview Scheduled', 'Approved', 'Rejected', 'Completed', 'Cancelled') DEFAULT 'Pending',
    Staff_Comments TEXT,
    Processed_By_UserID INT,
    Created_At DATETIME DEFAULT CURRENT_TIMESTAMP,
    Updated_At DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (AnimalID) REFERENCES Animals(AnimalID) ON UPDATE CASCADE,
    FOREIGN KEY (Adopter_UserID) REFERENCES Users(UserID) ON UPDATE CASCADE,
    FOREIGN KEY (Processed_By_UserID) REFERENCES Users(UserID) ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 5. INVENTORY MANAGEMENT
-- =====================================================

-- Inventory Table
CREATE TABLE IF NOT EXISTS Inventory (
    ItemID INT PRIMARY KEY AUTO_INCREMENT,
    Item_Name VARCHAR(100) NOT NULL,
    Category ENUM('Medical', 'Food', 'Cleaning', 'Supplies') NOT NULL,
    Quantity_On_Hand INT DEFAULT 0,
    Reorder_Level INT DEFAULT 10,
    Expiration_Date DATE,
    Supplier_Name VARCHAR(100),
    Last_Updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    Created_At DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- 6. BILLING & FINANCE
-- =====================================================

-- Invoices Table
CREATE TABLE IF NOT EXISTS Invoices (
    InvoiceID INT PRIMARY KEY AUTO_INCREMENT,
    Payer_UserID INT NOT NULL,
    Issued_By_UserID INT NOT NULL,
    Transaction_Type ENUM('Adoption Fee', 'Reclaim Fee') NOT NULL,
    Total_Amount DECIMAL(10,2) NOT NULL,
    Status ENUM('Unpaid', 'Paid', 'Cancelled') DEFAULT 'Unpaid',
    Is_Deleted BOOLEAN DEFAULT FALSE,
    Related_AnimalID INT,
    Related_RequestID INT,
    Created_At DATETIME DEFAULT CURRENT_TIMESTAMP,
    Updated_At DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (Payer_UserID) REFERENCES Users(UserID) ON UPDATE CASCADE,
    FOREIGN KEY (Issued_By_UserID) REFERENCES Users(UserID) ON UPDATE CASCADE,
    FOREIGN KEY (Related_AnimalID) REFERENCES Animals(AnimalID) ON UPDATE CASCADE,
    FOREIGN KEY (Related_RequestID) REFERENCES Adoption_Requests(RequestID) ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Payments Table
CREATE TABLE IF NOT EXISTS Payments (
    PaymentID INT PRIMARY KEY AUTO_INCREMENT,
    InvoiceID INT NOT NULL,
    Received_By_UserID INT NOT NULL,
    Payment_Date DATETIME DEFAULT CURRENT_TIMESTAMP,
    Amount_Paid DECIMAL(10,2) NOT NULL,
    Payment_Method ENUM('Cash', 'GCash', 'Bank Transfer') NOT NULL,
    Reference_Number VARCHAR(50),
    Created_At DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (InvoiceID) REFERENCES Invoices(InvoiceID) ON UPDATE CASCADE,
    FOREIGN KEY (Received_By_UserID) REFERENCES Users(UserID) ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 7. SYSTEM LOGS
-- =====================================================

-- Activity Logs Table
CREATE TABLE IF NOT EXISTS Activity_Logs (
    LogID INT PRIMARY KEY AUTO_INCREMENT,
    UserID INT,
    Action_Type VARCHAR(50) NOT NULL,
    Description TEXT,
    IP_Address VARCHAR(45),
    Log_Date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================

CREATE INDEX idx_users_email ON Users(Email);
CREATE INDEX idx_users_status ON Users(Account_Status, Is_Deleted);
CREATE INDEX idx_users_role ON Users(RoleID);

CREATE INDEX idx_animals_status ON Animals(Current_Status, Is_Deleted);
CREATE INDEX idx_animals_type ON Animals(Type);
CREATE INDEX idx_animals_intake_date ON Animals(Intake_Date);

CREATE INDEX idx_medical_animal ON Medical_Records(AnimalID);
CREATE INDEX idx_medical_date ON Medical_Records(Date_Performed);
CREATE INDEX idx_medical_next_due ON Medical_Records(Next_Due_Date);

CREATE INDEX idx_adoption_status ON Adoption_Requests(Status);
CREATE INDEX idx_adoption_animal ON Adoption_Requests(AnimalID);
CREATE INDEX idx_adoption_adopter ON Adoption_Requests(Adopter_UserID);

CREATE INDEX idx_inventory_category ON Inventory(Category);
CREATE INDEX idx_inventory_stock ON Inventory(Quantity_On_Hand, Reorder_Level);
CREATE INDEX idx_inventory_expiry ON Inventory(Expiration_Date);

CREATE INDEX idx_invoices_status ON Invoices(Status, Is_Deleted);
CREATE INDEX idx_invoices_payer ON Invoices(Payer_UserID);

CREATE INDEX idx_payments_invoice ON Payments(InvoiceID);
CREATE INDEX idx_payments_date ON Payments(Payment_Date);

CREATE INDEX idx_activity_user ON Activity_Logs(UserID);
CREATE INDEX idx_activity_date ON Activity_Logs(Log_Date);
CREATE INDEX idx_activity_type ON Activity_Logs(Action_Type);