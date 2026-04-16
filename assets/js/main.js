/* Kamyon Garajı - Ana JavaScript */

// Mobile Menu Toggle
function toggleMobileMenu() {
    toggleDrawer();
}

// Bottom Nav Drawer
function toggleDrawer() {
    const drawer = document.getElementById('drawer');
    if (!drawer) return;
    drawer.classList.toggle('open');
    
    if (drawer.classList.contains('open')) {
        const close = (e) => {
            if (!drawer.contains(e.target) && !e.target.closest('.bottom-nav-item')) {
                drawer.classList.remove('open');
                document.removeEventListener('click', close);
            }
        };
        setTimeout(() => document.addEventListener('click', close), 100);
    }
}

// AJAX Helper
async function ajaxPost(url, data = {}) {
    const formData = new FormData();
    formData.append('csrf_token', window.CSRF_TOKEN || '');
    
    for (const key in data) {
        if (data[key] instanceof File) {
            formData.append(key, data[key]);
        } else if (Array.isArray(data[key])) {
            data[key].forEach(v => formData.append(key + '[]', v));
        } else {
            formData.append(key, data[key]);
        }
    }
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('JSON Parse Error. Response:', text);
            return { success: false, message: 'Sunucu yanıtı çözümlenemedi: ' + text.substring(0, 200) };
        }
    } catch (err) {
        console.error('AJAX Error:', err);
        return { success: false, message: 'Bağlantı hatası: ' + err.message };
    }
}

// Toast Notification
function showToast(message, type = 'info', duration = 3500) {
    const colors = {
        success: '#10B981',
        error: '#EF4444',
        warning: '#F59E0B',
        info: '#3B82F6'
    };
    const icons = {
        success: 'fa-circle-check',
        error: 'fa-circle-xmark',
        warning: 'fa-triangle-exclamation',
        info: 'fa-circle-info'
    };
    
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed; top: 20px; right: 20px; z-index: 10000;
        background: ${colors[type]}; color: white;
        padding: 14px 20px; border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        display: flex; align-items: center; gap: 10px;
        min-width: 280px; max-width: 400px;
        font-size: 0.9375rem; font-weight: 500;
        animation: slideInRight 0.3s ease;
    `;
    toast.innerHTML = `<i class="fa-solid ${icons[type]}"></i> <span>${message}</span>`;
    
    if (!document.getElementById('toast-styles')) {
        const style = document.createElement('style');
        style.id = 'toast-styles';
        style.textContent = `
            @keyframes slideInRight { from { transform: translateX(120%); } to { transform: translateX(0); } }
            @keyframes slideOutRight { from { transform: translateX(0); } to { transform: translateX(120%); } }
            @media (max-width: 600px) {
                .kg-toast { left: 10px !important; right: 10px !important; min-width: auto !important; }
            }
        `;
        document.head.appendChild(style);
    }
    toast.classList.add('kg-toast');
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// Modal Helper
function openModal(title, bodyHtml, footerHtml = '') {
    const existing = document.getElementById('dynamicModal');
    if (existing) existing.remove();
    
    const modal = document.createElement('div');
    modal.id = 'dynamicModal';
    modal.className = 'modal-backdrop';
    modal.innerHTML = `
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">${title}</h3>
                <button class="modal-close" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">${bodyHtml}</div>
            ${footerHtml ? `<div class="modal-footer">${footerHtml}</div>` : ''}
        </div>
    `;
    modal.onclick = (e) => { if (e.target === modal) closeModal(); };
    document.body.appendChild(modal);
}

function closeModal() {
    const m = document.getElementById('dynamicModal');
    if (m) m.remove();
}

// Confirm Modal
function confirmAction(message, onConfirm, title = 'Emin misiniz?') {
    openModal(title,
        `<p>${message}</p>`,
        `<button class="btn btn-ghost" onclick="closeModal()">İptal</button>
         <button class="btn btn-danger" id="confirmYes">Evet, Onayla</button>`
    );
    setTimeout(() => {
        document.getElementById('confirmYes').onclick = () => {
            closeModal();
            onConfirm();
        };
    }, 50);
}

// Form Validation Helper
function validateForm(form) {
    let valid = true;
    form.querySelectorAll('[required]').forEach(el => {
        if (!el.value.trim()) {
            el.style.borderColor = '#EF4444';
            valid = false;
        } else {
            el.style.borderColor = '';
        }
    });
    return valid;
}

// DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    // Telefon input mask
    document.querySelectorAll('input[type="tel"]').forEach(el => {
        el.addEventListener('input', e => {
            e.target.value = e.target.value.replace(/[^0-9]/g, '').substring(0, 11);
        });
    });
    
    // Otomatik flash dismiss
    document.querySelectorAll('.alert').forEach(alert => {
        if (!alert.dataset.permanent) {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        }
    });
});
