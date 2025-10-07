-- Xóa nếu tồn tại database TuitionFee
DROP DATABASE IF EXISTS TuitionFee;
-- Tạo database
CREATE DATABASE TuitionFee;

-- Sử dụng database vừa tạo
USE TuitionFee;

-- Bảng TuitionFee
CREATE TABLE TuitionFee (
    StudentID VARCHAR(50) PRIMARY KEY,
    StudentName VARCHAR(100) NOT NULL,
    Amount DECIMAL(15,2) NOT NULL,
    DueDate DATE NOT NULL,
    Status ENUM('Completed','Processing','Unpaid') DEFAULT 'Unpaid'
);

-- Sample tuition data
USE TuitionFee;

INSERT INTO TuitionFee (StudentID, StudentName, Amount, DueDate, Status)
VALUES
('SV001', 'Tran Thi B', 250000.00, '2025-10-31', 'Unpaid'),
('SV002', 'Le Van C', 350000.00, '2025-10-31', 'Unpaid'),
('SV003', 'Pham D', 150000.00, '2025-10-31', 'Unpaid'),
('SV004', 'Pham H', 150000.00, '2025-10-31', 'Unpaid'),
('SV005', 'Pham K', 150000.00, '2025-10-31', 'Unpaid')
ON DUPLICATE KEY UPDATE
StudentName = VALUES(StudentName), Amount = VALUES(Amount), DueDate = VALUES(DueDate), Status = VALUES(Status);