// ===== UI =====

function showView(viewId) {
    document.querySelectorAll('.view').forEach(v => v.classList.remove('active'));
    const targetId = viewId.includes('view') ? viewId : viewId + '-view';
    const targetView = document.getElementById(targetId);
    if (targetView) targetView.classList.add('active');

    document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
    const navMap = { 'home': 0, 'library': 1, 'favorites': 2, 'profile': 3 };

    const bottomNav = document.querySelector('.bottom-nav');
    if (bottomNav) {
        if (navMap[viewId] !== undefined) {
            bottomNav.style.display = 'flex';
            const navItems = document.querySelectorAll('.nav-item');
            if (navItems[navMap[viewId]]) {
                navItems[navMap[viewId]].classList.add('active');
            }
        } else {
            bottomNav.style.display = 'none';
        }
    }

    if (viewId === 'favorites') renderFavorites();
    if (viewId === 'profile') renderTrophies();
}

function filterDecks(filter) {
    currentFilter = filter;
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    event.target.classList.add('active');
    renderDecks();
}

function handleSearch(query) {
    searchQuery = query;
    renderDecks();
}

function renderDecks() {
    const container = document.getElementById('decks-container');
    container.innerHTML = '';

    const filtered = decks.filter(d => {
        const matchesType = currentFilter === 'all' || d.type === currentFilter;
        const matchesSearch = d.title.toLowerCase().includes(searchQuery.toLowerCase());
        return matchesType && matchesSearch;
    });

    if (filtered.length === 0) {
        container.innerHTML = '<p style="text-align:center; color:var(--text-light); margin-top:20px;">Aucun deck trouvé.</p>';
        return;
    }

    const myDecks = filtered.filter(d => d.author === userCode);
    const otherDecks = filtered.filter(d => d.author !== userCode);

    if (myDecks.length > 0) {
        const title = document.createElement('h4');
        title.style.cssText = 'margin:10px 0 10px 5px; opacity:0.6; font-size:0.9rem; text-transform:uppercase; letter-spacing:1px;';
        title.textContent = 'Mes Decks';
        container.appendChild(title);
        myDecks.forEach(deck => container.appendChild(createDeckElement(deck)));
    }

    if (otherDecks.length > 0) {
        const title = document.createElement('h4');
        title.style.cssText = 'margin:25px 0 10px 5px; opacity:0.6; font-size:0.9rem; text-transform:uppercase; letter-spacing:1px;';
        title.textContent = 'Communauté';
        container.appendChild(title);
        otherDecks.forEach(deck => container.appendChild(createDeckElement(deck)));
    }
}

function createDeckElement(deck) {
    const isFav = favorites.includes(deck.id);
    const isAuthor = deck.author === userCode;
    const div = document.createElement('div');
    div.className = 'quiz-item';
    div.innerHTML = `
        <div class="quiz-icon" onclick="startStudy(${deck.id})">${deck.icon || (deck.type === 'qcm' ? '📝' : '🎴')}</div>
        <div class="quiz-info" onclick="startStudy(${deck.id})">
            <h4>${deck.title}</h4>
            <p>${deck.cards.length} ${deck.type === 'qcm' ? 'questions' : 'cartes'}</p>
        </div>
        <div style="display:flex; flex-direction:column; align-items:center; gap:5px;">
            <div class="quiz-status ${deck.progress === 100 ? 'completed' : 'incomplete'}">
                ${deck.progress || 0}%
            </div>
            <div style="display:flex; gap:5px;">
                <button onclick="toggleFavorite(${deck.id}, event)" style="background:none; border:none; font-size:1.2rem; cursor:pointer; color:${isFav ? '#EF4444' : '#E5E7EB'};">
                    ${isFav ? '❤️' : '🤍'}
                </button>
                <button onclick="shareDeckFromList(${deck.id}, event)" style="background:none; border:none; font-size:1.2rem; cursor:pointer;" title="Partager">
                    🔗
                </button>
                ${isAuthor ? `
                <button onclick="viewDeckStats(${deck.id}, event)" style="background:none; border:none; font-size:1.2rem; cursor:pointer;" title="Statistiques">
                    📊
                </button>
                <button onclick="deleteDeck(${deck.id}, event)" style="background:none; border:none; font-size:1.2rem; cursor:pointer;" title="Supprimer">
                    🗑️
                </button>` : ''}
            </div>
        </div>
    `;
    return div;
}

function viewDeckStats(deckId, event) {
    if (event) event.stopPropagation();
    const deck = decks.find(d => d.id == deckId);
    if (!deck) return;

    const ds = deck.stats || {};
    const cardCount = Array.isArray(deck.cards) ? deck.cards.length : '?';
    const typeLabel = deck.type === 'qcm' ? 'QCM' : 'Flashcards';
    const typeEmoji = deck.type === 'qcm' ? '📝' : '🎴';
    const createdLabel = deck.created ? new Date(deck.created).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) : null;

    // Header
    const dsIcon = document.getElementById('ds-icon');
    const dsTitle = document.getElementById('ds-title');
    const dsMeta = document.getElementById('ds-meta');
    if (!dsIcon || !dsTitle) return;
    dsIcon.textContent = typeEmoji;
    dsTitle.textContent = deck.title;
    if (dsMeta) dsMeta.textContent = [
        typeLabel,
        `${cardCount} carte${cardCount > 1 ? 's' : ''}`,
        createdLabel ? `Créé le ${createdLabel}` : null
    ].filter(Boolean).join(' · ');

    // KPIs
    const totalPlays = ds.totalPlays ?? 0;
    const avg = ds.averageScore != null ? Math.round(ds.averageScore) + ' %' : '—';
    const best = ds.bestScore != null ? ds.bestScore + ' %' : '—';
    const myPlays = (ds.playsByUser && ds.playsByUser[userCode]) ? ds.playsByUser[userCode] : 0;

    const dsPlays = document.getElementById('ds-plays');
    const dsAvg = document.getElementById('ds-avg');
    const dsBest = document.getElementById('ds-best');
    const dsMine = document.getElementById('ds-mine');
    const dsLastDate = document.getElementById('ds-last-date');
    const dsLastScore = document.getElementById('ds-last-score');
    const historyEl = document.getElementById('ds-history');
    const backdrop = document.getElementById('deck-stats-backdrop');
    const sheet = document.getElementById('deck-stats-sheet');
    if (!backdrop || !sheet) return;

    if (dsPlays) dsPlays.textContent = totalPlays;
    if (dsAvg) dsAvg.textContent = avg;
    if (dsBest) dsBest.textContent = best;
    if (dsMine) dsMine.textContent = myPlays;

    // Last session
    if (ds.lastPlayed) {
        const d = new Date(ds.lastPlayed);
        if (dsLastDate) dsLastDate.textContent = d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
        if (dsLastScore) dsLastScore.textContent = ds.lastScore != null ? ds.lastScore + ' %' : '—';
    } else {
        if (dsLastDate) dsLastDate.textContent = 'Aucune partie jouée';
        if (dsLastScore) dsLastScore.textContent = '';
    }

    // My recent sessions from global stats history
    const mySessions = (stats.history || [])
        .filter(h => h.deckId == deckId || h.deck == deck.title)
        .slice(-6)
        .reverse();

    if (historyEl) {
        if (mySessions.length === 0) {
            historyEl.innerHTML = '<p style="color:var(--text-muted);font-size:0.85rem;padding:4px 0;">Aucune session enregistrée pour ce deck.</p>';
        } else {
            historyEl.innerHTML = mySessions.map(s => {
                const score = s.score ?? 0;
                const dateStr = s.date ? new Date(s.date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' }) : '';
                return `<div class="ds-history-item">
                    <div class="ds-history-bar-wrap"><div class="ds-history-bar" style="width:${score}%"></div></div>
                    <span class="ds-history-score">${score} %</span>
                    <span class="ds-history-date">${dateStr}</span>
                </div>`;
            }).join('');
        }
    }

    backdrop.classList.add('active');
    sheet.classList.add('active');
}

function closeDeckStats() {
    document.getElementById('deck-stats-backdrop').classList.remove('active');
    document.getElementById('deck-stats-sheet').classList.remove('active');
}

function toggleFavorite(deckId, event) {
    event.stopPropagation();
    const index = favorites.indexOf(deckId);
    if (index === -1) favorites.push(deckId);
    else favorites.splice(index, 1);
    saveData();
    renderDecks();
    renderFavorites();
}

async function deleteDeck(deckId, event) {
    event.stopPropagation();
    if (!confirm("Es-tu sûr de vouloir supprimer ce deck ? Cette action est irréversible.")) return;

    try {
        const response = await fetch(`${API_BACKEND}?action=delete_deck`, {
            method: 'POST',
            body: JSON.stringify({ deckId: deckId, userCode: userCode })
        });
        const data = await response.json();

        if (data.status === 'success') {
            decks = decks.filter(d => d.id !== deckId);
            favorites = favorites.filter(id => id !== deckId);
            renderDecks();
            renderFavorites();
            alert("Deck supprimé avec succès.");
        } else {
            alert("Erreur : " + (data.message || "Impossible de supprimer le deck."));
        }
    } catch (e) {
        console.error(e);
        alert("Erreur de connexion.");
    }
}

function renderFavorites() {
    const container = document.getElementById('favorites-list');
    container.innerHTML = '';

    const favDecks = decks.filter(d => favorites.includes(d.id));

    if (favDecks.length === 0) {
        container.innerHTML = '<p style="text-align:center; color:var(--text-light); margin-top:20px;">Aucun favori pour le moment.</p>';
        return;
    }

    favDecks.forEach((deck) => {
        const div = document.createElement('div');
        div.className = 'quiz-item';
        div.innerHTML = `
            <div class="quiz-icon" onclick="startStudy(${deck.id})">${deck.icon || (deck.type === 'qcm' ? '📝' : '🎴')}</div>
            <div class="quiz-info" onclick="startStudy(${deck.id})">
                <h4>${deck.title}</h4>
                <p>${deck.cards.length} ${deck.type === 'qcm' ? 'questions' : 'cartes'}</p>
            </div>
            <div style="display:flex; flex-direction:column; align-items:center; gap:5px;">
                <div class="quiz-status ${deck.progress === 100 ? 'completed' : 'incomplete'}">
                    ${deck.progress || 0}%
                </div>
                <button onclick="toggleFavorite(${deck.id}, event)" style="background:none; border:none; font-size:1.2rem; cursor:pointer; color:'#EF4444';">
                    ❤️
                </button>
            </div>
        `;
        container.appendChild(div);
    });
}

function updateStatsUI() {
    const pointsEl = document.getElementById('total-points');
    if (pointsEl) pointsEl.textContent = stats.points;

    const statPoints = document.getElementById('stat-points');
    if (statPoints) statPoints.textContent = stats.points;

    const statStreak = document.getElementById('stat-streak');
    if (statStreak) statStreak.textContent = stats.streak;

    const statCards = document.getElementById('stat-cards');
    if (statCards) statCards.textContent = stats.cardsLearned;

    const statTime = document.getElementById('stat-time');
    if (statTime) statTime.textContent = Math.round(stats.timeSpent / 60) + 'h';

    updateBannerProgress();
    renderRecentActivity();
}

function renderRecentActivity() {
    const container = document.getElementById('recent-activity-list');
    if (!container) return;

    // --- Événements : sessions terminées ---
    const sessionEvents = (stats.history || []).map(s => ({
        type: 'session',
        date: s.date,
        title: s.deckTitle || 'Quiz',
        deckType: s.deckType || 'qcm',
        score: s.score,
        total: s.total
    }));

    // --- Événements : decks créés par l'utilisateur ---
    const deckEvents = decks
        .filter(d => d.author === userCode && d.created)
        .map(d => ({
            type: 'deck_created',
            date: d.created,
            title: d.title,
            deckType: d.type,
            cardCount: d.cards ? d.cards.length : 0
        }));

    const all = [...sessionEvents, ...deckEvents]
        .sort((a, b) => new Date(b.date) - new Date(a.date))
        .slice(0, 8);

    if (all.length === 0) {
        container.innerHTML = '<div style="text-align: center; color: var(--text-muted); padding: 20px;">Aucune activité récente. Lance un quiz !</div>';
        return;
    }

    container.innerHTML = all.map(event => {
        const dateStr = new Date(event.date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });

        if (event.type === 'deck_created') {
            const typeLabel = event.deckType === 'qcm' ? 'QCM' : 'Flashcards';
            const icon = event.deckType === 'qcm' ? '📝' : '🎴';
            return `
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border);">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 1.4rem;">${icon}</span>
                        <div>
                            <div style="font-weight: 600; font-size: 0.95rem;">${event.title}</div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">${dateStr}</div>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 0.8rem; font-weight: 600; color: var(--primary);">Créé</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">${event.cardCount} ${event.deckType === 'qcm' ? 'questions' : 'cartes'}</div>
                    </div>
                </div>
            `;
        }

        // session
        const scorePercent = event.total > 0 ? Math.round((event.score / event.total) * 100) : 0;
        const scoreColor = scorePercent >= 80 ? 'var(--success)' : (scorePercent >= 50 ? 'var(--warning)' : 'var(--danger)');
        const icon = event.deckType === 'flashcards' ? '🎴' : '📝';
        return `
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 1.4rem;">${icon}</span>
                    <div>
                        <div style="font-weight: 600; font-size: 0.95rem;">${event.title}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">${dateStr}</div>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-weight: 700; color: ${scoreColor};">${scorePercent}%</div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">${event.score}/${event.total}</div>
                </div>
            </div>
        `;
    }).join('');
}

function updateBannerProgress() {
    const level = Math.floor(stats.points / 100) + 1;
    const xpInLevel = stats.points % 100;
    const xpNeeded = 100;
    const progressPercent = (xpInLevel / xpNeeded) * 100;

    const levelEl = document.getElementById('user-level');
    if (levelEl) levelEl.textContent = `Niveau ${level}`;

    const progressTextEl = document.getElementById('level-progress-text');
    if (progressTextEl) progressTextEl.textContent = `${xpInLevel} / ${xpNeeded} XP`;

    const progressBarEl = document.getElementById('xp-progress-bar');
    if (progressBarEl) progressBarEl.style.width = progressPercent + '%';

    const streakEl = document.getElementById('banner-streak');
    if (streakEl) streakEl.textContent = `${stats.streak} jour${stats.streak > 1 ? 's' : ''}`;

    const cardsEl = document.getElementById('banner-cards');
    if (cardsEl) cardsEl.textContent = `${stats.cardsLearned} carte${stats.cardsLearned > 1 ? 's' : ''}`;

    const timeEl = document.getElementById('banner-time');
    if (timeEl) timeEl.textContent = Math.round(stats.timeSpent / 60) + 'h';
}

// ===== TROPHÉES =====
function getTrophies() {
    return [
        { id: 'first_quiz', icon: '🎓', name: 'Premier Pas', desc: 'Terminer ton premier quiz', condition: () => (stats.history || []).length >= 1 },
        { id: 'scorer', icon: '🎯', name: 'Tireur d élite', desc: 'Obtenir 100% à un quiz', condition: () => (stats.history || []).some(s => s.score === s.total) },
        { id: 'streak_3', icon: '🔥', name: 'En Feu', desc: '3 parties consécutives', condition: () => stats.streak >= 3 },
        { id: 'streak_7', icon: '⚡', name: 'Inarrêtable', desc: '7 parties consécutives', condition: () => stats.streak >= 7 },
        { id: 'points_100', icon: '💰', name: 'Centurion', desc: 'Atteindre 100 points', condition: () => stats.points >= 100 },
        { id: 'points_500', icon: '👑', name: 'Champion', desc: 'Atteindre 500 points', condition: () => stats.points >= 500 },
        { id: 'cards_50', icon: '📚', name: 'Érudit', desc: 'Apprendre 50 cartes', condition: () => stats.cardsLearned >= 50 },
        { id: 'cards_200', icon: '🎓', name: 'Maître', desc: 'Apprendre 200 cartes', condition: () => stats.cardsLearned >= 200 },
        { id: 'creator', icon: '✨', name: 'Créateur', desc: 'Créer ton premier deck', condition: () => decks.some(d => d.author === userCode) }
    ];
}

function renderTrophies() {
    const container = document.getElementById('trophies-container');
    if (!container) return;

    // Stats
    const level = Math.floor(stats.points / 100) + 1;
    const xpInLevel = stats.points % 100;

    const pPoints = document.getElementById('p-total-points');
    if (pPoints) pPoints.textContent = stats.points;
    const pStreak = document.getElementById('p-streak');
    if (pStreak) pStreak.textContent = stats.streak;
    const pCards = document.getElementById('p-cards');
    if (pCards) pCards.textContent = stats.cardsLearned;
    const pSessions = document.getElementById('p-sessions');
    if (pSessions) pSessions.textContent = (stats.history || []).length;
    const pLevel = document.getElementById('profile-level');
    if (pLevel) pLevel.textContent = level;
    const xpFill = document.getElementById('profile-xp-fill');
    if (xpFill) xpFill.style.width = xpInLevel + '%';
    const xpCur = document.getElementById('profile-xp-cur');
    if (xpCur) xpCur.textContent = xpInLevel;

    // Trophies
    const trophies = getTrophies();
    const unlockedTrophies = JSON.parse(localStorage.getItem('unlockedTrophies') || '[]');
    const unlockedCount = trophies.filter(t => t.condition()).length;

    const countEl = document.getElementById('trophy-count');
    if (countEl) countEl.textContent = `${unlockedCount}/${trophies.length}`;

    container.innerHTML = '';

    trophies.forEach(trophy => {
        const isUnlocked = trophy.condition();
        const wasUnlocked = unlockedTrophies.includes(trophy.id);

        if (isUnlocked && !wasUnlocked) {
            unlockedTrophies.push(trophy.id);
            localStorage.setItem('unlockedTrophies', JSON.stringify(unlockedTrophies));
            setTimeout(() => alert(`🏆 Trophée débloqué : ${trophy.name} !\n${trophy.desc}`), 500);
        }

        const div = document.createElement('div');
        div.className = `trophy-card ${isUnlocked ? 'trophy-unlocked' : 'trophy-locked'}`;
        div.title = isUnlocked ? trophy.desc : `🔒 ${trophy.desc}`;
        div.innerHTML = isUnlocked
            ? `<div class="trophy-icon">${trophy.icon}</div>
               <div class="trophy-name">${trophy.name}</div>
               <div class="trophy-desc">${trophy.desc}</div>`
            : `<div class="trophy-icon trophy-icon-locked">${trophy.icon}<span class="trophy-lock">🔒</span></div>
               <div class="trophy-name">???</div>
               <div class="trophy-desc">${trophy.desc}</div>`;
        container.appendChild(div);
    });
}

function renderCharts() {
    const ctx = document.getElementById('progressChart');
    if (!ctx) return;

    const last7Days = [];
    for (let i = 6; i >= 0; i--) {
        const d = new Date();
        d.setDate(d.getDate() - i);
        last7Days.push(d.toISOString().split('T')[0]);
    }

    const dataPoints = last7Days.map(date => {
        const sessions = (stats.history || []).filter(s => s.date.startsWith(date));
        if (sessions.length === 0) return 0;
        const totalScore = sessions.reduce((acc, s) => acc + (s.score / s.total), 0);
        return Math.round((totalScore / sessions.length) * 100);
    });

    if (window.myChart) window.myChart.destroy();

    window.myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: last7Days.map(d => d.split('-').slice(1).join('/')),
            datasets: [{
                label: 'Score moyen (%)',
                data: dataPoints,
                borderColor: '#3D2C8D',
                backgroundColor: 'rgba(61, 44, 141, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, max: 100 } }
        }
    });
}
