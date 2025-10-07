-- Xóa database cũ nếu có
DROP DATABASE IF EXISTS Payment;
-- Tạo database mới
CREATE DATABASE Payment;
USE Payment;

-- Bảng Payment (thanh toán)
CREATE TABLE Payment (
    PaymentID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    StudentID VARCHAR(50) NOT NULL,
    AvailableBalance DECIMAL(15,2) DEFAULT 0.00,
    Amount DECIMAL(15,2) NOT NULL,
    Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bảng OTPs (sửa: ExpiredAt cho phép NULL để tránh lỗi mặc định không hợp lệ)
CREATE TABLE OTPs (
    OtpID INT AUTO_INCREMENT PRIMARY KEY,
    PaymentID INT NOT NULL,
    Code VARCHAR(10) NOT NULL,
    CreatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ExpiredAt TIMESTAMP NULL,       -- cho phép NULL (không gây lỗi default)
    IsUsed TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (PaymentID) REFERENCES Payment(PaymentID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
