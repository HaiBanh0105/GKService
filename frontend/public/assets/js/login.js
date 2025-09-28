
const passwordInput = document.getElementById("password");
const passwordToggle = document.getElementById("passwordToggle");
passwordToggle.addEventListener("click", () => {
    passwordInput.type = passwordInput.type === "password" ? "text" : "password";
});

const loginForm = document.getElementById("loginForm");
const errorMessage = document.getElementById("errorMessage");
const successMessage = document.getElementById("successMessage");
const loginBtn = document.getElementById("loginBtn");
const loginBtnText = document.getElementById("loginBtnText");

function showError(msg) {
    errorMessage.style.display = 'block';
    errorMessage.innerText = msg;
    successMessage.style.display = 'none';
}

function showSuccess(msg) {
    successMessage.style.display = 'block';
    successMessage.innerText = msg;
    errorMessage.style.display = 'none';
}

loginForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    showError("");
    errorMessage.style.display = 'none';
    showSuccess("");
    successMessage.style.display = 'none';

    const username = document.getElementById("username").value.trim();
    const password = document.getElementById("password").value;

    if (!username || !password) {
        showError("Vui lòng nhập đủ thông tin!");
        return;
    }

    try {
        loginBtn.disabled = true;
        loginBtnText.textContent = 'Đang đăng nhập...';

        // Điều chỉnh URL theo cấu hình XAMPP của bạn nếu khác
        const response = await fetch("http://localhost:8080/GKService/backend/auth/login.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ username, password })
        });

        let data = null;
        const text = await response.text();
        try { data = JSON.parse(text); } catch { /* ignore */ }

        if (!response.ok) {
            const msg = (data && data.message) ? data.message : `Lỗi máy chủ (${response.status})`;
            showError(msg);
            return;
        }

        if (data && data.status === "success") {
            showSuccess("Đăng nhập thành công!");
            localStorage.setItem("user", JSON.stringify(data.user));
            setTimeout(() => {
                window.location.href = "index.html";
            }, 800);
        } else {
            showError((data && data.message) ? data.message : "Đăng nhập thất bại!");
        }
    } catch (err) {
        console.error(err);
        showError("Lỗi kết nối API!");
    } finally {
        loginBtn.disabled = false;
        loginBtnText.textContent = 'Đăng nhập';
    }
});
