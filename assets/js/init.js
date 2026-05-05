// ===== INIT =====

document.addEventListener('DOMContentLoaded', () => {
    loadData();
    showView('home');

    // Vérifier si un deck est partagé via URL
    const urlParams = new URLSearchParams(window.location.search);
    const sharedDeckId = urlParams.get('deck');
    if (sharedDeckId) {
        setTimeout(() => {
            const deck = decks.find(d => d.id == sharedDeckId);
            if (deck) {
                startStudy(deck.id);
            } else {
                alert('Deck introuvable. Il a peut-être été supprimé.');
            }
        }, 500);
    }

    // Double-tap pour plein écran
    let lastTap = 0;
    const header = document.getElementById('home-header');
    if (header) {
        header.addEventListener('touchend', () => {
            const currentTime = new Date().getTime();
            const tapLength = currentTime - lastTap;
            if (tapLength < 300 && tapLength > 0) toggleFullscreen();
            lastTap = currentTime;
        });
    }

    // Fermer le panel stats avec Échap
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeDeckStats();
    });
});
