// ===== UTILS =====

// --- Theme ---
function applyTheme(theme) {
    if (theme === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
    else document.documentElement.removeAttribute('data-theme');
    localStorage.setItem('theme', theme);
    const icon = document.getElementById('theme-icon');
    if (icon) {
        icon.textContent = theme === 'dark' ? '☀️' : '🌘';
    }
}

function toggleTheme() {
    const current = localStorage.getItem('theme') || 'light';
    const btn = document.getElementById('theme-toggle-btn');
    if (btn) {
        btn.classList.add('theme-switching');
        btn.addEventListener('animationend', function handler() {
            btn.classList.remove('theme-switching');
            btn.removeEventListener('animationend', handler);
        });
    }
    applyTheme(current === 'dark' ? 'light' : 'dark');
}

// Initialize theme immediately (prevents FOUC)
(function () {
    const saved = localStorage.getItem('theme') || 'light';
    if (saved === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
    // Update icon once DOM is ready
    document.addEventListener('DOMContentLoaded', function () {
        applyTheme(saved);
    });
})();

// --- WAF sanitizer ---
function sanitizeForWaf(text) {
    if (!text) return text;
    return text
        .replace(/-->/g, "→")
        .replace(/<!--/g, "←")
        .replace(/-- /g, "— ")
        .replace(/UNION SELECT/gi, "UNION_SELECT")
        .replace(/DROP TABLE/gi, "DROP_TABLE")
        .replace(/<script/gi, "‹script")
        .replace(/javascript:/gi, "javascript_:")
        .replace(/\.\.\//g, "./")
        .replace(/etc\/passwd/gi, "etc_passwd")
        .replace(/system\(/gi, "system_(")
        .replace(/exec\(/gi, "exec_(");
}

// --- Misc helpers ---
function shuffleArray(array) {
    for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]];
    }
}

function formatTime(seconds) {
    const m = Math.floor(seconds / 60).toString().padStart(2, '0');
    const s = (seconds % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
}

function checkReminders() {
    if (!("Notification" in window)) return;

    const lastStudy = localStorage.getItem('lastStudyDate');
    const today = new Date().toDateString();

    if (lastStudy && lastStudy !== today) {
        if (Notification.permission === "granted") {
            new Notification("Revisio", {
                body: "C'est l'heure de réviser ! 📚 Garde le rythme !",
                icon: "https://philo-lycee.fr/favicon.ico"
            });
        } else if (Notification.permission !== "denied") {
            Notification.requestPermission();
        }
    }

    localStorage.setItem('lastStudyDate', today);
}
