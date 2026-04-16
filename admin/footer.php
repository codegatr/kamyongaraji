        </div>
    </div>
</div>

<script>
function toggleAdminSidebar() {
    document.getElementById('adminSidebar').classList.toggle('open');
}

// User menu dropdown
function toggleUserMenu() {
    const d = document.getElementById('userMenuDropdown');
    if (d) d.style.display = d.style.display === 'block' ? 'none' : 'block';
}

// Dropdown disarda tiklayinca kapat
document.addEventListener('click', (e) => {
    const menu = document.querySelector('.admin-user-menu');
    const dropdown = document.getElementById('userMenuDropdown');
    if (menu && dropdown && !menu.contains(e.target)) {
        dropdown.style.display = 'none';
    }
});

// AJAX helper
async function aAjax(url, data = {}) {
    const fd = new FormData();
    fd.append('csrf_token', window.CSRF_TOKEN);
    for (const k in data) {
        if (data[k] instanceof File) fd.append(k, data[k]);
        else fd.append(k, data[k]);
    }
    try {
        const r = await fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' });
        const t = await r.text();
        try { return JSON.parse(t); }
        catch(e) { console.error('Bad JSON:', t); return {success:false, message:'Sunucu yanıtı: ' + t.substring(0,200)}; }
    } catch (e) {
        return { success: false, message: 'Bağlantı hatası: ' + e.message };
    }
}

function aToast(msg, type = 'info') {
    const colors = { success:'#10B981', error:'#EF4444', warning:'#F59E0B', info:'#3B82F6' };
    const t = document.createElement('div');
    t.style.cssText = `position:fixed;top:20px;right:20px;z-index:10000;background:${colors[type]};color:white;padding:12px 18px;border-radius:10px;font-size:0.875rem;box-shadow:0 8px 24px rgba(0,0,0,0.15);max-width:400px;`;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

function aConfirm(msg, cb) {
    if (confirm(msg)) cb();
}
</script>
</body>
</html>
