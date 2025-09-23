# GKService

## Microservices

- Auth service (`backend/auth`)
  - `POST http://localhost:8080/GKService/backend/auth/login.php` { username, password }
  - DB: `user`

- Tuition service (`backend/tuition`)
  - `GET http://localhost:8080/GKService/backend/tuition/get_tuition.php?studentId=...`
  - DB: `TuitionFee`

- Payment service (`backend/payment`)
  - `POST http://localhost:8080/GKService/backend/payment/create_otp.php` { userId, studentId, amount }
  - `POST http://localhost:8080/GKService/backend/payment/confirm_otp.php` { paymentId, otp }
  - Status flow (TuitionFee.Status): `Unpaid` → `Processing` (sau khi tạo OTP) → `Completed` (sau khi xác nhận OTP thành công)
  - Ràng buộc: chỉ tạo OTP khi trạng thái là `Unpaid`
  - DB: `payment`

## Setup
1. Start XAMPP Apache + MySQL
2. Import SQL files in order:
   - `user.sql`
   - `TuitionFee.sql`
   - `payment.sql`
3. Truy cập frontend:
   - `http://localhost:8080/GKService/frontend/public/login.html`

## Notes
- Passwords are plaintext by request.
- Base path sử dụng `/GKService` trên XAMPP (port 8080). Điều chỉnh nếu thư mục dự án/port khác.
- Email: sử dụng PHPMailer với Gmail SMTP trong `backend/common/PHPmailer.php`. Cần cấu hình `Username` và App Password hợp lệ.