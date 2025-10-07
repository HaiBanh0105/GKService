
(function() {
    const userJson = localStorage.getItem('user');
    if (!userJson) { window.location.href = 'login.html'; return; }
    let user = null; try { user = JSON.parse(userJson); } catch { user = null; }
    if (!user || !user.id) { window.location.href = 'login.html'; return; }

    document.getElementById('uFullname').textContent = user.fullname || '';
    document.getElementById('uEmail').textContent = user.email || '';
    document.getElementById('uBalance').textContent = (user.balance || 0).toLocaleString('vi-VN') + ' VND';

    //load lịch sử giao dịch từ database theo user id
    async function loadHistory() {
        try {
            const res = await fetch(`http://localhost/GKService/getway/auth/transactions?userId=${user.id}`);
            const data = await res.json();
            const listEl = document.getElementById('txList');
            listEl.innerHTML = '';
            if (data.status !== 'success' || !Array.isArray(data.transactions) || data.transactions.length === 0) {
                listEl.innerHTML = '<div class="transaction-item">Chưa có giao dịch</div>';
                return;
            }
            data.transactions.forEach(t => {
                const div = document.createElement('div');
                div.className = 'transaction-item';
                const amt = Number(t.Amount || 0);
                const created = t.CreatedAt ? new Date(t.CreatedAt).toLocaleString('vi-VN') : '';
                div.innerHTML = `<div><strong>${t.StudentID || ''}</strong> - ${t.StudentName || ''}</div>
                                 <div>Số tiền: ${amt.toLocaleString('vi-VN')} VND</div>
                                 <div>Thời gian: ${created}</div>`;
                listEl.appendChild(div);
            });
        } catch (e) {
            console.error(e);
            document.getElementById('txList').innerHTML = '<div class="transaction-item">Lỗi tải lịch sử</div>';
        }
    }

    loadHistory();
})();
 