
(function() {
    const userJson = localStorage.getItem('user');
    if (!userJson) { window.location.href = 'login.html'; return; }

    let user = null;
    try { user = JSON.parse(userJson); } catch { user = null; }
    if (!user || !user.username) { window.location.href = 'login.html'; return; }

    const nameInput = document.getElementById('submitterName');
    const phoneInput = document.getElementById('submitterPhone');
    const emailInput = document.getElementById('submitterEmail');
    const balanceInput = document.getElementById('payerBalance');
    const tuitionFeeInput = document.getElementById('tuitionFee');
    const feeAmountInput = document.getElementById('feeAmount');
    const totalAmountEl = document.getElementById('totalAmount');
    const submitBtn = document.getElementById('submitBtn');
    const studentIdInput = document.getElementById('studentId');
    const studentNameInput = document.getElementById('studentName');
    const searchTuitionBtn = document.getElementById('searchTuitionBtn');
    const tuitionStatusInput = document.getElementById('tuitionStatus');
    const viewHistoryBtn = document.getElementById('viewHistoryBtn');

    const statusMap = { 'Completed': 'Hoàn thành', 'Processing': 'Đang xử lý', 'Unpaid': 'Chưa nộp' };
    let currentTuitionStatus = null;

    nameInput.value = user.fullname || '';
    phoneInput.value = user.phone || '';
    emailInput.value = user.email || '';
    balanceInput.value = typeof user.balance === 'number' ? Math.floor(user.balance) : 0;

    [nameInput, phoneInput, emailInput].forEach(el => { el.readOnly = true; el.style.background = '#f5f5f5'; el.style.color = '#666'; });

    viewHistoryBtn.addEventListener('click', () => { window.location.href = 'history.html'; });

    const updateTotals = () => {
        const amount = parseInt(feeAmountInput.value, 10) || 0;
        tuitionFeeInput.value = amount;
        totalAmountEl.textContent = amount.toLocaleString('vi-VN') + ' VND';
        const balance = parseInt(balanceInput.value, 10) || 0;
        const canPay = amount > 0 && amount <= balance && currentTuitionStatus === 'Unpaid';
        submitBtn.disabled = !canPay;
        if (currentTuitionStatus && currentTuitionStatus !== 'Unpaid') {
            submitBtn.style.background = '#bdc3c7';
            submitBtn.textContent = 'Không thể thanh toán';
        } else if (amount > balance) {
            submitBtn.style.background = '#e74c3c';
            submitBtn.textContent = 'Số dư không đủ';
        } else {
            submitBtn.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
            submitBtn.textContent = 'Xác nhận giao dịch';
        }
    };

    feeAmountInput.addEventListener('input', updateTotals);

    async function fetchTuitionByStudentId(studentId) {
        try {
            const res = await fetch(`http://localhost:8080/GKService/backend/tuition/get_tuition.php?studentId=${encodeURIComponent(studentId)}`);
            const data = await res.json();
            if (data.status === 'success') {
                const t = data.tuition;
                studentNameInput.value = t.StudentName || '';
                currentTuitionStatus = t.Status || null;
                tuitionStatusInput.value = statusMap[currentTuitionStatus] || currentTuitionStatus || '';
                feeAmountInput.value = parseInt(t.Amount, 10) || 0;
                feeAmountInput.readOnly = true;
                feeAmountInput.style.background = '#f5f5f5';
                updateTotals();
            } else {
                alert(data.message || 'Không tìm thấy học phí');
                studentNameInput.value = '';
                currentTuitionStatus = null;
                tuitionStatusInput.value = '';
                feeAmountInput.value = '';
                feeAmountInput.readOnly = false;
                feeAmountInput.style.background = '';
                updateTotals();
            }
        } catch (e) {
            console.error(e);
            alert('Lỗi kết nối dịch vụ học phí');
        }
    }

    searchTuitionBtn.addEventListener('click', () => {
        const sid = studentIdInput.value.trim();
        if (!sid) { alert('Vui lòng nhập mã số sinh viên'); return; }
        fetchTuitionByStudentId(sid);
    });

    updateTotals();

    document.getElementById('registrationForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const amount = parseInt(feeAmountInput.value, 10) || 0;
        const balance = parseInt(balanceInput.value, 10) || 0;
        const studentId = studentIdInput.value.trim();
        if (amount <= 0 || amount > balance || !studentId) { return; }

        try {
            submitBtn.textContent = 'Đang gửi OTP qua email...';
            submitBtn.disabled = true;
            const res = await fetch('http://localhost:8080/GKService/backend/payment/create_otp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ userId: user.id, studentId, amount, userEmail: user.email })
            });
            const data = await res.json();
            if (data.status !== 'success') {
                alert(data.message || 'Không tạo được OTP');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Xác nhận giao dịch';
                return;
            }

            // Cập nhật trạng thái ban đầu
            currentTuitionStatus = 'Processing';
            tuitionStatusInput.value = statusMap[currentTuitionStatus];
            submitBtn.disabled = true;
            submitBtn.textContent = 'Đang xử lý';
            submitBtn.style.background = '#bdc3c7';

            // Cho phép nhập lại OTP nếu sai
            let otpValid = false;
            let attempts = 0;
            const maxAttempts = 3;

                while (!otpValid && attempts < maxAttempts) {
                const otpInput = prompt('Một mã OTP đã được gửi tới email của bạn. Vui lòng nhập OTP:') || '';
                if (!otpInput) {
                    alert('Bạn chưa nhập OTP');
                    break;
                }

                submitBtn.textContent = 'Đang xác nhận OTP...';

                try {
                    const res2 = await fetch('http://localhost:8080/GKService/backend/payment/confirm_otp.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ paymentId: data.paymentId, otp: otpInput.trim() })
                    });
                    const data2 = await res2.json();

                    if (data2.status === 'success') {
                        otpValid = true;

                        // Cập nhật số dư
                        if (data2.user) {
                            localStorage.setItem('user', JSON.stringify(data2.user));
                            balanceInput.value = Math.floor(data2.user.balance || 0);
                            updateTotals();
                        }

                        // Hoàn tất giao dịch
                        currentTuitionStatus = 'Completed';
                        tuitionStatusInput.value = statusMap[currentTuitionStatus];

                        const success = document.getElementById('successMessage');
                        success.style.display = 'block';
                        success.scrollIntoView({ behavior: 'smooth' });
                        submitBtn.textContent = 'Giao dịch thành công';
                        submitBtn.style.background = '#27ae60';
                    } else {
                        alert(data2.message || 'OTP không hợp lệ');
                        attempts++;
                    }
                } catch (err) {
                    alert('Lỗi mạng hoặc máy chủ không phản hồi');
                    break;
                }
            }

            if (!otpValid) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Xác nhận giao dịch';
                submitBtn.style.background = '#3498db';
            }
            else{
        
            // load lại form sau 0.6s
            setTimeout(() => {
                studentIdInput.value = '';
                studentNameInput.value = '';
                tuitionStatusInput.value = '';
                feeAmountInput.value = '';
                feeAmountInput.readOnly = false;
                feeAmountInput.style.background = '';
                tuitionFeeInput.value = '';
                totalAmountEl.textContent = '0 VND';
                currentTuitionStatus = null;
                updateTotals();
            }, 600);}
        } catch (err) {
            console.error(err);
            alert('Lỗi kết nối dịch vụ thanh toán');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Xác nhận giao dịch';
        }
    });
})();
        const logoutBtn = document.getElementById('logoutBtn');
        logoutBtn.addEventListener('click', () => {
            if (confirm('Bạn có chắc muốn đăng xuất không?')) {
                localStorage.removeItem('user');
                window.location.href = 'login.html';
            }
        });
    