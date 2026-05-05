// ===== API CLIENT =====

async function loadData() {
    try {
        const response = await fetch(`${API_BACKEND}?action=load&user=${userCode}`);
        const data = await response.json();

        decks = data.decks || [];
        stats = data.stats || { points: 0, streak: 0, cardsLearned: 0, timeSpent: 0 };
        stats.history = data.history || [];
        favorites = data.favorites || [];

        renderDecks();
        renderFavorites();
        updateStatsUI();
        renderCharts();
        checkReminders();
    } catch (e) {
        console.error("Erreur chargement:", e);
    }
}

async function saveData(sessionData = null) {
    try {
        const payload = {
            userCode,
            stats,
            progress: currentDeck ? { [currentDeck.id]: Math.round((currentSessionScore / activeDeckCards.length) * 100) } : {},
            favorites
        };

        if (sessionData) {
            payload.session = sessionData;
            if (!stats.history) stats.history = [];
            stats.history.push(sessionData);
        }

        await fetch(`${API_BACKEND}?action=save_progress`, {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        if (sessionData) renderCharts();
    } catch (e) {
        console.error("Erreur sauvegarde:", e);
    }
}
