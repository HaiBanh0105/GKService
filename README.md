# GKService

## Microservices

- Auth service (`backend/auth`)
  - `POST /service/GKService/backend/auth/login.php` { username, password }
  - `GET  /service/GKService/backend/auth/user_info.php?id=...|username=...`
  - DB: `user`

- Tuition service (`backend/tuition`)
  - `GET /service/GKService/backend/tuition/get_tuition.php?studentId=...`
  - DB: `TuitionFee`

- Payment service (`backend/payment`)
  - `POST /service/GKService/backend/payment/create_otp.php` { userId, studentId, amount }
  - `POST /service/GKService/backend/payment/confirm_otp.php` { paymentId, otp }
  - DB: `Paymen`

## Setup
1. Start XAMPP Apache + MySQL
2. Import SQL files in order:
   - `user.sql`
   - `TuitionFee.sql`
   - `payment.sql`
3. Open `frontend/public/login.html` in browser

## Notes
- Passwords are plaintext by request.
- Adjust base path `/service/GKService/` if your XAMPP alias differs.