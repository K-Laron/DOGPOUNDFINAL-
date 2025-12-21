-- =====================================================
-- SEED DATA FOR CATARMAN DOG POUND MANAGEMENT SYSTEM
-- =====================================================

USE catarman_dog_pound_db;

-- =====================================================
-- 1. INSERT ROLES
-- =====================================================

INSERT INTO Roles (Role_Name) VALUES 
('Admin'),
('Staff'),
('Veterinarian'),
('Adopter');

-- =====================================================
-- 2. INSERT DEFAULT USERS
-- =====================================================

-- Admin User (Password: admin123)
INSERT INTO Users (RoleID, FirstName, LastName, Email, Contact_Number, Password_Hash, Account_Status) 
VALUES (
    1, 
    'System', 
    'Administrator', 
    'admin@catarmandogpound.com', 
    '09170000001', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    'Active'
);

-- Staff User (Password: staff123)
INSERT INTO Users (RoleID, FirstName, LastName, Email, Contact_Number, Password_Hash, Account_Status) 
VALUES (
    2, 
    'Juan', 
    'Dela Cruz', 
    'staff@catarmandogpound.com', 
    '09170000002', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    'Active'
);

-- Veterinarian User (Password: vet123)
INSERT INTO Users (RoleID, FirstName, LastName, Email, Contact_Number, Password_Hash, Account_Status) 
VALUES (
    3, 
    'Maria', 
    'Santos', 
    'vet@catarmandogpound.com', 
    '09170000003', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    'Active'
);

-- Adopter User (Password: adopter123)
INSERT INTO Users (RoleID, FirstName, LastName, Email, Contact_Number, Password_Hash, Account_Status) 
VALUES (
    4, 
    'Pedro', 
    'Reyes', 
    'adopter@example.com', 
    '09170000004', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    'Active'
);

-- =====================================================
-- 3. INSERT VETERINARIAN PROFILE
-- =====================================================

INSERT INTO Veterinarians (UserID, License_Number, Specialization, Years_Experience)
VALUES (3, 'VET-2024-001', 'General Practice', 5);

-- =====================================================
-- 4. INSERT SAMPLE ANIMALS
-- =====================================================

INSERT INTO Animals (Name, Type, Breed, Gender, Age_Group, Weight, Intake_Date, Intake_Status, Current_Status) VALUES
('Buddy', 'Dog', 'Aspin', 'Male', 'Adult', 15.50, NOW() - INTERVAL 30 DAY, 'Stray', 'Available'),
('Luna', 'Dog', 'Labrador Mix', 'Female', 'Puppy', 5.20, NOW() - INTERVAL 25 DAY, 'Surrendered', 'Available'),
('Max', 'Dog', 'Aspin', 'Male', 'Senior', 12.00, NOW() - INTERVAL 20 DAY, 'Stray', 'Available'),
('Bella', 'Cat', 'Puspin', 'Female', 'Adult', 3.50, NOW() - INTERVAL 15 DAY, 'Stray', 'Available'),
('Charlie', 'Dog', 'Shih Tzu Mix', 'Male', 'Adult', 8.00, NOW() - INTERVAL 10 DAY, 'Confiscated', 'In Treatment'),
('Milo', 'Cat', 'Persian Mix', 'Male', 'Kitten', 1.20, NOW() - INTERVAL 5 DAY, 'Surrendered', 'Available'),
('Brownie', 'Dog', 'Aspin', 'Female', 'Puppy', 4.50, NOW() - INTERVAL 3 DAY, 'Stray', 'Quarantine'),
('Kitty', 'Cat', 'Puspin', 'Female', 'Adult', 4.00, NOW() - INTERVAL 45 DAY, 'Stray', 'Adopted'),
('Rocky', 'Dog', 'German Shepherd Mix', 'Male', 'Adult', 25.00, NOW() - INTERVAL 60 DAY, 'Surrendered', 'Adopted'),
('Coco', 'Dog', 'Poodle Mix', 'Female', 'Senior', 6.50, NOW() - INTERVAL 40 DAY, 'Surrendered', 'Reclaimed');

-- =====================================================
-- 5. INSERT IMPOUND RECORDS
-- =====================================================

INSERT INTO Impound_Records (AnimalID, Capture_Date, Location_Found, Impounding_Officer, Condition_On_Arrival) VALUES
(1, NOW() - INTERVAL 30 DAY, 'Barangay Centro, near public market', 'Officer Garcia', 'Good condition, slightly underweight'),
(2, NOW() - INTERVAL 25 DAY, 'Surrendered by owner at shelter', 'Officer Santos', 'Healthy, vaccinated'),
(3, NOW() - INTERVAL 20 DAY, 'Barangay San Jose, residential area', 'Officer Garcia', 'Senior dog, some dental issues'),
(4, NOW() - INTERVAL 15 DAY, 'Barangay Poblacion, near school', 'Officer Cruz', 'Good condition'),
(5, NOW() - INTERVAL 10 DAY, 'Confiscated from illegal breeder', 'Officer Santos', 'Skin infection, needs treatment'),
(7, NOW() - INTERVAL 3 DAY, 'Barangay Rawis, near highway', 'Officer Garcia', 'Young puppy, possible parvo exposure');

-- =====================================================
-- 6. INSERT MEDICAL RECORDS
-- =====================================================

INSERT INTO Medical_Records (AnimalID, VetID, Date_Performed, Diagnosis_Type, Vaccine_Name, Treatment_Notes, Next_Due_Date) VALUES
(1, 1, NOW() - INTERVAL 28 DAY, 'Checkup', NULL, 'Initial health assessment. Animal is in good health.', NULL),
(1, 1, NOW() - INTERVAL 27 DAY, 'Vaccination', 'Anti-Rabies', 'Rabies vaccination administered.', DATE_ADD(CURDATE(), INTERVAL 1 YEAR)),
(1, 1, NOW() - INTERVAL 26 DAY, 'Deworming', NULL, 'Deworming treatment completed.', DATE_ADD(CURDATE(), INTERVAL 3 MONTH)),
(2, 1, NOW() - INTERVAL 23 DAY, 'Checkup', NULL, 'Healthy puppy, good weight for age.', NULL),
(2, 1, NOW() - INTERVAL 22 DAY, 'Vaccination', '5-in-1', 'First dose of 5-in-1 vaccine.', DATE_ADD(CURDATE(), INTERVAL 3 WEEK)),
(3, 1, NOW() - INTERVAL 18 DAY, 'Checkup', NULL, 'Senior dog with mild arthritis. Dental cleaning recommended.', NULL),
(4, 1, NOW() - INTERVAL 13 DAY, 'Vaccination', 'Anti-Rabies', 'Rabies vaccination administered.', DATE_ADD(CURDATE(), INTERVAL 1 YEAR)),
(5, 1, NOW() - INTERVAL 8 DAY, 'Treatment', NULL, 'Started treatment for skin infection. Medicated bath twice weekly.', DATE_ADD(CURDATE(), INTERVAL 7 DAY)),
(5, 1, NOW() - INTERVAL 5 DAY, 'Checkup', NULL, 'Skin condition improving. Continue treatment.', DATE_ADD(CURDATE(), INTERVAL 7 DAY)),
(7, 1, NOW() - INTERVAL 2 DAY, 'Checkup', NULL, 'Under observation for possible parvo. Isolated in quarantine.', DATE_ADD(CURDATE(), INTERVAL 5 DAY));

-- =====================================================
-- 7. INSERT INVENTORY ITEMS
-- =====================================================

INSERT INTO Inventory (Item_Name, Category, Quantity_On_Hand, Reorder_Level, Expiration_Date, Supplier_Name) VALUES
-- Medical supplies
('Anti-Rabies Vaccine', 'Medical', 50, 20, DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 'PetVax Philippines'),
('5-in-1 Vaccine', 'Medical', 30, 15, DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 'PetVax Philippines'),
('Deworming Tablets', 'Medical', 100, 30, DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 'VetMed Supplies'),
('Antibiotics (Amoxicillin)', 'Medical', 25, 10, DATE_ADD(CURDATE(), INTERVAL 8 MONTH), 'VetMed Supplies'),
('Wound Disinfectant', 'Medical', 15, 5, DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 'VetMed Supplies'),
('Surgical Gloves (Box)', 'Medical', 8, 10, NULL, 'MedSupply Co.'),
('Syringes (Box of 100)', 'Medical', 5, 5, NULL, 'MedSupply Co.'),

-- Food supplies
('Dog Food - Adult (kg)', 'Food', 100, 50, DATE_ADD(CURDATE(), INTERVAL 3 MONTH), 'PetFood Distributor'),
('Dog Food - Puppy (kg)', 'Food', 50, 25, DATE_ADD(CURDATE(), INTERVAL 3 MONTH), 'PetFood Distributor'),
('Cat Food - Adult (kg)', 'Food', 40, 20, DATE_ADD(CURDATE(), INTERVAL 3 MONTH), 'PetFood Distributor'),
('Cat Food - Kitten (kg)', 'Food', 20, 10, DATE_ADD(CURDATE(), INTERVAL 3 MONTH), 'PetFood Distributor'),
('Canned Dog Food', 'Food', 60, 30, DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 'PetFood Distributor'),

-- Cleaning supplies
('Disinfectant (Liters)', 'Cleaning', 20, 10, DATE_ADD(CURDATE(), INTERVAL 2 YEAR), 'CleanCo Supplies'),
('Medicated Shampoo', 'Cleaning', 12, 5, DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 'PetCare Products'),
('Floor Cleaner (Liters)', 'Cleaning', 15, 8, NULL, 'CleanCo Supplies'),
('Trash Bags (Pack)', 'Cleaning', 25, 15, NULL, 'General Supplies Inc.'),

-- General supplies
('Dog Leashes', 'Supplies', 20, 10, NULL, 'PetCare Products'),
('Dog Collars (Assorted)', 'Supplies', 30, 15, NULL, 'PetCare Products'),
('Cat Carriers', 'Supplies', 5, 3, NULL, 'PetCare Products'),
('Dog Crates - Medium', 'Supplies', 3, 2, NULL, 'PetCare Products'),
('Food Bowls', 'Supplies', 40, 20, NULL, 'General Supplies Inc.'),
('Water Dispensers', 'Supplies', 10, 5, NULL, 'General Supplies Inc.');

-- =====================================================
-- 8. INSERT SAMPLE ADOPTION REQUESTS
-- =====================================================

INSERT INTO Adoption_Requests (AnimalID, Adopter_UserID, Request_Date, Status, Staff_Comments, Processed_By_UserID) VALUES
(8, 4, NOW() - INTERVAL 40 DAY, 'Completed', 'Adoption completed successfully. Follow-up scheduled in 2 weeks.', 2),
(9, 4, NOW() - INTERVAL 55 DAY, 'Completed', 'Great match! Adopter has experience with large dogs.', 2),
(1, 4, NOW() - INTERVAL 5 DAY, 'Pending', NULL, NULL),
(2, 4, NOW() - INTERVAL 3 DAY, 'Interview Scheduled', 'Interview scheduled for next week.', 2);

-- =====================================================
-- 9. INSERT SAMPLE INVOICES
-- =====================================================

INSERT INTO Invoices (Payer_UserID, Issued_By_UserID, Transaction_Type, Total_Amount, Status, Related_AnimalID, Related_RequestID) VALUES
(4, 2, 'Adoption Fee', 500.00, 'Paid', 8, 1),
(4, 2, 'Adoption Fee', 500.00, 'Paid', 9, 2),
(4, 2, 'Reclaim Fee', 350.00, 'Paid', 10, NULL),
(4, 2, 'Adoption Fee', 500.00, 'Unpaid', 1, 3);

-- =====================================================
-- 10. INSERT SAMPLE PAYMENTS
-- =====================================================

INSERT INTO Payments (InvoiceID, Received_By_UserID, Payment_Date, Amount_Paid, Payment_Method, Reference_Number) VALUES
(1, 2, NOW() - INTERVAL 38 DAY, 500.00, 'Cash', NULL),
(2, 2, NOW() - INTERVAL 53 DAY, 500.00, 'GCash', 'GC-2024-001234'),
(3, 2, NOW() - INTERVAL 35 DAY, 350.00, 'Bank Transfer', 'BT-2024-005678');

-- =====================================================
-- 11. INSERT SAMPLE FEEDING RECORDS
-- =====================================================

INSERT INTO Feeding_Records (AnimalID, Fed_By_UserID, Feeding_Time, Food_Type, Quantity_Used) VALUES
(1, 2, NOW() - INTERVAL 1 DAY + INTERVAL 7 HOUR, 'Dog Food - Adult', 0.30),
(1, 2, NOW() - INTERVAL 1 DAY + INTERVAL 17 HOUR, 'Dog Food - Adult', 0.30),
(2, 2, NOW() - INTERVAL 1 DAY + INTERVAL 7 HOUR, 'Dog Food - Puppy', 0.20),
(2, 2, NOW() - INTERVAL 1 DAY + INTERVAL 12 HOUR, 'Dog Food - Puppy', 0.20),
(2, 2, NOW() - INTERVAL 1 DAY + INTERVAL 17 HOUR, 'Dog Food - Puppy', 0.20),
(3, 2, NOW() - INTERVAL 1 DAY + INTERVAL 7 HOUR, 'Dog Food - Adult', 0.25),
(3, 2, NOW() - INTERVAL 1 DAY + INTERVAL 17 HOUR, 'Dog Food - Adult', 0.25),
(4, 2, NOW() - INTERVAL 1 DAY + INTERVAL 8 HOUR, 'Cat Food - Adult', 0.10),
(4, 2, NOW() - INTERVAL 1 DAY + INTERVAL 18 HOUR, 'Cat Food - Adult', 0.10),
(5, 2, NOW() - INTERVAL 1 DAY + INTERVAL 7 HOUR, 'Dog Food - Adult', 0.20),
(5, 2, NOW() - INTERVAL 1 DAY + INTERVAL 17 HOUR, 'Dog Food - Adult', 0.20),
(6, 2, NOW() - INTERVAL 1 DAY + INTERVAL 8 HOUR, 'Cat Food - Kitten', 0.08),
(6, 2, NOW() - INTERVAL 1 DAY + INTERVAL 13 HOUR, 'Cat Food - Kitten', 0.08),
(6, 2, NOW() - INTERVAL 1 DAY + INTERVAL 18 HOUR, 'Cat Food - Kitten', 0.08),
(7, 2, NOW() - INTERVAL 1 DAY + INTERVAL 7 HOUR, 'Dog Food - Puppy', 0.15),
(7, 2, NOW() - INTERVAL 1 DAY + INTERVAL 17 HOUR, 'Dog Food - Puppy', 0.15);

-- =====================================================
-- 12. INSERT SAMPLE ACTIVITY LOGS
-- =====================================================

INSERT INTO Activity_Logs (UserID, Action_Type, Description, IP_Address, Log_Date) VALUES
(1, 'LOGIN', 'Admin logged in successfully', '127.0.0.1', NOW() - INTERVAL 1 HOUR),
(2, 'LOGIN', 'Staff logged in successfully', '127.0.0.1', NOW() - INTERVAL 2 HOUR),
(2, 'CREATE_ANIMAL', 'Created animal ID: 7 (Brownie)', '127.0.0.1', NOW() - INTERVAL 3 DAY),
(3, 'CREATE_MEDICAL_RECORD', 'Created medical record for animal ID: 7', '127.0.0.1', NOW() - INTERVAL 2 DAY),
(2, 'PROCESS_ADOPTION', 'Processed adoption request ID: 4 - Status: Interview Scheduled', '127.0.0.1', NOW() - INTERVAL 1 DAY),
(2, 'RECORD_FEEDING', 'Recorded feeding for animal ID: 1', '127.0.0.1', NOW() - INTERVAL 1 DAY),
(2, 'ADJUST_INVENTORY', 'Adjusted Dog Food - Adult: subtract 5 (was: 105, now: 100)', '127.0.0.1', NOW() - INTERVAL 1 DAY),
(4, 'LOGIN', 'Adopter logged in successfully', '127.0.0.1', NOW() - INTERVAL 5 HOUR),
(4, 'CREATE_ADOPTION_REQUEST', 'Submitted adoption request for animal: Buddy', '127.0.0.1', NOW() - INTERVAL 5 DAY);

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Verify data was inserted
SELECT 'Roles' as TableName, COUNT(*) as RecordCount FROM Roles
UNION ALL SELECT 'Users', COUNT(*) FROM Users
UNION ALL SELECT 'Veterinarians', COUNT(*) FROM Veterinarians
UNION ALL SELECT 'Animals', COUNT(*) FROM Animals
UNION ALL SELECT 'Impound_Records', COUNT(*) FROM Impound_Records
UNION ALL SELECT 'Medical_Records', COUNT(*) FROM Medical_Records
UNION ALL SELECT 'Feeding_Records', COUNT(*) FROM Feeding_Records
UNION ALL SELECT 'Adoption_Requests', COUNT(*) FROM Adoption_Requests
UNION ALL SELECT 'Inventory', COUNT(*) FROM Inventory
UNION ALL SELECT 'Invoices', COUNT(*) FROM Invoices
UNION ALL SELECT 'Payments', COUNT(*) FROM Payments
UNION ALL SELECT 'Activity_Logs', COUNT(*) FROM Activity_Logs;