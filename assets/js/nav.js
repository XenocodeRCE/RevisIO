// ===== NAVIGATION =====

function goHome() {
    const url = new URL(window.location);
    url.searchParams.delete('deck');
    window.history.replaceState({}, '', url);
    showView('home');
}

function logout() {
    window.location.href = 'index.php';
}

function toggleLogout() {
    const btn = document.getElementById('logout-btn');
    if (btn) {
        btn.style.display = btn.style.display === 'block' ? 'none' : 'block';
    }
}

function copyUserCode() {
    navigator.clipboard.writeText(userCode).then(() => alert('Code copié !'));
}

function toggleFullscreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(err => {
            console.log(`Erreur plein écran: ${err.message}`);
        });
    } else {
        if (document.exitFullscreen) document.exitFullscreen();
    }
}

// ===== SHARE =====

function shareDeckFromList(deckId, event) {
    event.stopPropagation();
    const shareUrl = `${window.location.origin}${window.location.pathname}?deck=${deckId}`;

    navigator.clipboard.writeText(shareUrl).then(() => {
        const btn = event.target.closest('button');
        if (btn) {
            const originalContent = btn.innerHTML;
            btn.innerHTML = '✅';
            setTimeout(() => btn.innerHTML = originalContent, 1500);
        } else {
            alert('Lien copié !');
        }
    }).catch(() => {
        prompt('Copiez ce lien :', shareUrl);
    });
}

function shareDeck() {
    if (!currentDeck) return;
    const shareUrl = `${window.location.origin}${window.location.pathname}?deck=${currentDeck.id}`;

    navigator.clipboard.writeText(shareUrl).then(() => {
        alert('🔗 Lien copié !\n\nPartagez ce lien pour que d\'autres puissent accéder à ce deck.\n\n' + shareUrl);
    }).catch(() => {
        prompt('Copiez ce lien pour partager le deck :', shareUrl);
    });
}
