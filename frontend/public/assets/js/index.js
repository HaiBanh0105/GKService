
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
    const resendOtpBtn = document.getElementById('resendOtpBtn');

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
        
        if (amount > balance) {
            submitBtn.style.background = '#e74c3c';
            submitBtn.textContent = 'Số dư không đủ';
            resendOtpBtn.style.display = 'none';
        } 
        else if(currentTuitionStatus === 'Processing'){
            submitBtn.disabled = false;
            submitBtn.style.background = '#c59328ff';
            submitBtn.textContent = 'Nhập otp';
            resendOtpBtn.style.display = 'inline-block';
            resendOtpBtn.disabled = false;
        }
        else if (currentTuitionStatus && currentTuitionStatus !== 'Unpaid') {
            submitBtn.style.background = '#bdc3c7';
            submitBtn.textContent = 'Không thể thanh toán';  
            resendOtpBtn.style.display = 'none';
        } 
        else {
            submitBtn.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
            submitBtn.textContent = 'Xác nhận giao dịch';
            resendOtpBtn.style.display = 'none';
        }
    };  

    feeAmountInput.addEventListener('input', updateTotals);

    async function fetchTuitionByStudentId(studentId) {
        try {
            const res = await fetch(`http://localhost/GKService/getway/tuition/get?studentId=${encodeURIComponent(studentId)}`);
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
            } 
            
            else {
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
        // if(currentTuitionStatus === 'Processing' && currentTuitionStatus !== null){
        //     resendOtpBtn.style.display = 'inline-block';
        //     }   
        // else{
        //     resendOtpBtn.style.display = 'none';}
    });

    updateTotals();

    resendOtpBtn.addEventListener('click', async function (e) {
    e.preventDefault();
    const amount = parseInt(feeAmountInput.value, 10) || 0;
    const studentId = studentIdInput.value.trim();
    

    try {
        resendOtpBtn.textContent = 'Đang gửi lại OTP...';
        resendOtpBtn.disabled = true;
        submitBtn.disabled = true;
        const res = await fetch('http://localhost/GKService/getway/payment/resend_otp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                userId: user.id,
                studentId,
                amount,
                userEmail: user.email
            })
        });

        
        const data = await res.json();
        if (data.status === 'success') {
            resendOtpBtn.style.background = '#3ce76dff';
            resendOtpBtn.textContent = 'OTP đã được gửi lại';
            submitBtn.disabled = false;
            
            setTimeout(() => { 
                resendOtpBtn.textContent = 'Gửi lại OTP'; 
                resendOtpBtn.disabled = false;
                resendOtpBtn.style.background = '#e67e22';
            }, 9000);
            
            localStorage.setItem('pendingPaymentId', data.paymentId);
            alert('OTP mới đã được gửi đến email.');
        } else {
            alert(data.message || 'Không gửi được OTP mới');
        }
    } catch (err) {
        console.error(err);
        alert('Lỗi kết nối dịch vụ thanh toán');
    }
});


    document.getElementById('registrationForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const amount = parseInt(feeAmountInput.value, 10) || 0;
    const balance = parseInt(balanceInput.value, 10) || 0;
    const studentId = studentIdInput.value.trim();
    let otpInput = '';
    let otpValid = false;

    if (amount <= 0 || amount > balance || !studentId) return;

    try {
        // -------------------------------
        // B1: TRẠNG THÁI CHƯA THANH TOÁN → GỬI OTP
        // -------------------------------
        if (currentTuitionStatus === 'Unpaid') {
            submitBtn.textContent = 'Đang gửi OTP qua email...';
            submitBtn.disabled = true;

            const res = await fetch('http://localhost/GKService/getway/payment/create_otp', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    userId: user.id,
                    studentId,
                    amount,
                    userEmail: user.email
                })
            });

            const data = await res.json();
            if (data.status !== 'success') {
                alert(data.message || 'Không tạo được OTP');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Xác nhận giao dịch';
                return;
            }

            // Lưu paymentId vào localStorage để xác nhận OTP sau
            localStorage.setItem('pendingPaymentId', data.paymentId);

            // Cập nhật trạng thái học phí sang "Đang xử lý"
            resendOtpBtn.style.display = 'inline-block';
            currentTuitionStatus = 'Processing';
            tuitionStatusInput.value = statusMap[currentTuitionStatus];
            submitBtn.disabled = false;
            submitBtn.style.background = '#c59328ff';
            submitBtn.textContent = 'Nhập OTP';
            alert('Một mã OTP đã được gửi đến email của bạn. Vui lòng nhấn lại "Nhập OTP" để xác nhận.');
            return; // kết thúc ở bước tạo OTP
        }

        // -------------------------------
        // B2: TRẠNG THÁI ĐANG XỬ LÝ → NHẬP OTP VÀ XÁC NHẬN
        // -------------------------------
        if (currentTuitionStatus === 'Processing') {
            const paymentId = localStorage.getItem('pendingPaymentId');
            if (!paymentId) {
                alert('Không tìm thấy mã giao dịch. Vui lòng thực hiện lại.');
                return;
            }

            otpInput = prompt('Vui lòng nhập mã OTP được gửi đến email của bạn:') || '';
            if (!otpInput.trim()) {
                alert('Bạn chưa nhập OTP');
                return;
            }

            submitBtn.textContent = 'Đang xác nhận OTP...';
            submitBtn.disabled = true;
            resendOtpBtn.disabled = true;

            const res2 = await fetch('http://localhost/GKService/getway/payment/confirm_otp', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ paymentId: paymentId, otp: otpInput.trim() })
            });

            const data2 = await res2.json();

            if (data2.status === 'success') {
                otpValid = true;
                localStorage.removeItem('pendingPaymentId');
                resendOtpBtn.style.display = 'none';
                resendOtpBtn.textContent = 'Gửi lại OTP';

                // Cập nhật số dư mới
                if (data2.user) {
                    localStorage.setItem('user', JSON.stringify(data2.user));
                    balanceInput.value = Math.floor(data2.user.balance || 0);
                }

                // Cập nhật trạng thái học phí hoàn thành
                currentTuitionStatus = 'Completed';
                tuitionStatusInput.value = statusMap[currentTuitionStatus];

                const success = document.getElementById('successMessage');
                success.style.display = 'block';
                success.scrollIntoView({ behavior: 'smooth' });
                
                submitBtn.textContent = 'Giao dịch thành công';
                submitBtn.style.background = '#27ae60';

                // Reset form sau 0.6s
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
                }, 600);
                setTimeout(() => { 
                    success.style.display = 'none';
                }, 5000);
            } else {
                alert(data2.message || 'OTP không hợp lệ hoặc hết hạn');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Nhập OTP lại';
                submitBtn.style.background = '#c59328ff';
            }
        }
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
    