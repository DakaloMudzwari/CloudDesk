/**
 * CloudDesk Core Application Service
 * Senior Software Developer implementation
 */

class CloudDesk {
    constructor() {
        this.init();
    }

    init() {
        this.setupData();
        this.handleAuth();
    }

    // Seed initial data if localStorage is empty
    setupData() {
        if (!localStorage.getItem('cd_users')) {
            const initialUsers = [
                { id: 1, name: 'Admin User', email: 'admin@clouddesk.com', password: 'Admin@123', role: 'admin' },
                { id: 2, name: 'Jane Doe', email: 'user@clouddesk.com', password: 'User@123', role: 'user' },
                { id: 3, name: 'John Tech', email: 'tech@clouddesk.com', password: 'Tech@123', role: 'technician' }
            ];
            localStorage.setItem('cd_users', JSON.stringify(initialUsers));
        }

        if (!localStorage.getItem('cd_tickets')) {
            const initialTickets = [
                { id: 'TKT-1001', userId: 2, title: 'VPN Connection Issues', category: 'Network', priority: 'High', status: 'open', created: new Date().toISOString(), desc: 'Cannot connect to the office VPN since this morning.' },
                { id: 'TKT-1002', userId: 2, title: 'Laptop Battery Overheating', category: 'Hardware', priority: 'Medium', status: 'progress', created: new Date().toISOString(), desc: 'The battery gets extremely hot after 20 minutes of use.' }
            ];
            localStorage.setItem('cd_tickets', JSON.stringify(initialTickets));
        }
    }

    // Auth Handler
    async handleAuth() {
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;

                try {
                    const response = await fetch('api/login.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ email, password })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.handleLoginSuccess(result.user);
                        return;
                    } else {
                        this.showToast(result.error || 'Invalid credentials', 'danger');
                        return;
                    }
                } catch (err) {
                    console.warn('API unavailable, falling back to local storage auth.');
                }

                // Fallback to LocalStorage
                const users = JSON.parse(localStorage.getItem('cd_users'));
                const user = users.find(u => u.email === email && u.password === password);

                if (user) {
                    this.handleLoginSuccess(user);
                } else {
                    this.showToast('Invalid credentials. Please try again.', 'danger');
                }
            });
        }
    }



    handleLoginSuccess(user) {
        localStorage.setItem('cd_current_user', JSON.stringify(user));
        this.showToast('Login successful! Redirecting...', 'success');
        
        setTimeout(() => {
            if (user.role === 'admin') {
                window.location.href = 'admin-dashboard.html';
            } else if (user.role === 'technician') {
                window.location.href = 'technician-dashboard.html';
            } else {
                window.location.href = 'user-dashboard.html';
            }
        }, 1000);
    }

    async fetchTickets() {
        const user = this.getSession();
        if (!user) return [];

        try {
            const url = user.role === 'user' ? `api/tickets.php?user_id=${user.id}` : 'api/tickets.php';
            const response = await fetch(url);
            const data = await response.json();
            if (Array.isArray(data)) return data;
        } catch (err) {
            console.warn('API fetch failed, using local storage.');
        }

        // Fallback
        const tickets = JSON.parse(localStorage.getItem('cd_tickets') || '[]');
        return user.role === 'user' ? tickets.filter(t => t.userId === user.id) : tickets;
    }

    // Utility: Toast Notification (Premium Glassmorphism)
    showToast(message, type = 'info') {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        const icon = type === 'success' ? 'fa-check-circle' : 
                     type === 'danger' ? 'fa-exclamation-triangle' : 
                     type === 'warning' ? 'fa-exclamation-circle' : 'fa-info-circle';
        
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <i class="fas ${icon}"></i>
            <span>${message}</span>
        `;

        container.appendChild(toast);

        // Auto-remove after 4 seconds
        setTimeout(() => {
            toast.classList.add('toast-out');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    // Smart Home Redirect
    goHome() {
        const user = this.getSession();
        if (!user) {
            window.location.href = 'index.html';
            return;
        }
        
        if (user.role === 'admin' || user.role === 'technician') {
            window.location.href = 'admin-dashboard.html';
        } else {
            window.location.href = 'user-dashboard.html';
        }
    }

    // Custom Confirmation Modal (Premium replacement for window.confirm)
    confirm(title, message, confirmText = 'Confirm', type = 'danger') {
        return new Promise((resolve) => {
            let overlay = document.querySelector('.modal-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'modal-overlay';
                document.body.appendChild(overlay);
            }

            overlay.innerHTML = `
                <div class="modal-card">
                    <div class="modal-icon">
                        <i class="fas fa-${type === 'danger' ? 'trash-alt' : 'exclamation-circle'}"></i>
                    </div>
                    <div class="modal-title">${title}</div>
                    <div class="modal-text">${message}</div>
                    <div class="modal-actions">
                        <button id="modalCancel" class="btn" style="background: rgba(255,255,255,0.05); color: white;">Cancel</button>
                        <button id="modalConfirm" class="btn btn-${type === 'danger' ? 'danger' : 'primary'}" style="background: ${type === 'danger' ? 'var(--danger)' : 'var(--primary)'}">${confirmText}</button>
                    </div>
                </div>
            `;

            setTimeout(() => overlay.classList.add('active'), 10);

            const cleanup = (value) => {
                overlay.classList.remove('active');
                setTimeout(() => {
                    overlay.innerHTML = '';
                    resolve(value);
                }, 300);
            };

            document.getElementById('modalConfirm').onclick = () => cleanup(true);
            document.getElementById('modalCancel').onclick = () => cleanup(false);
            overlay.onclick = (e) => { if(e.target === overlay) cleanup(false); };
        });
    }

    // Get current user session
    getSession() {
        const session = localStorage.getItem('cd_current_user');
        return session ? JSON.parse(session) : null;
    }

    logout() {
        localStorage.removeItem('cd_current_user');
        window.location.href = 'index.html';
    }
}

export const app = new CloudDesk();
