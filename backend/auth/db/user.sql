-- Xóa nếu tồn tại database user
DROP DATABASE IF EXISTS user;
-- Tạo database
CREATE DATABASE user;

-- Sử dụng database vừa tạo
USE user;

-- Bảng User
CREATE TABLE User (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(50) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    FullName VARCHAR(100) NOT NULL,
    Phone VARCHAR(20),
    Email VARCHAR(100) UNIQUE,
    Address VARCHAR(255),
    AvailableBalance DECIMAL(15,2) DEFAULT 0.00
);

-- Bảng TransactionHistory
CREATE TABLE TransactionHistory (
    TransactionID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    StudentID VARCHAR(50),
    StudentName VARCHAR(100),
    Amount DECIMAL(15,2) NOT NULL,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES User(UserID)
);


-- Seed sample data for testing login (PLAINTEXT PASSWORDS)
USE user;

-- Ensure a plaintext user exists
INSERT INTO User (Username, Password, FullName, Phone, Email, Address, AvailableBalance)
SELECT 'vodathai', '123456', 'Vo Dat Hai', '0772663776', 'vodathai91thcsduclap@gmail.com', '700 Nguyen Van Linh, District 7', 5000000.00
WHERE NOT EXISTS (
    SELECT 1 FROM User WHERE Username = 'tdtu_user'
);

-- Legacy sample user
INSERT INTO User (Username, Password, FullName, Phone, Email, Address, AvailableBalance)
SELECT 'legacy_user', '123456', 'Legacy User', '0900000000', 'legacy@example.com', 'Somewhere', 3000000.00
WHERE NOT EXISTS (
    SELECT 1 FROM User WHERE Username = 'legacy_user'
);

-- Additional sample users (PLAINTEXT)
USE user;

INSERT INTO User (Username, Password, FullName, Phone, Email, Address, AvailableBalance)
SELECT 'tester1', '111111', 'Tester One', '0911111111', 'tester1@example.com', 'District 1', 2000000.00
WHERE NOT EXISTS (SELECT 1 FROM User WHERE Username = 'tester1');

INSERT INTO User (Username, Password, FullName, Phone, Email, Address, AvailableBalance)
SELECT 'tester2', '222222', 'Tester Two', '0922222222', 'tester2@example.com', 'District 2', 8000000.00
WHERE NOT EXISTS (SELECT 1 FROM User WHERE Username = 'tester2');