// ===== STUDY =====

function startStudy(deckId) {
    currentDeck = decks.find(d => d.id === deckId);
    if (!currentDeck) return;
    document.getElementById('study-mode-overlay').classList.add('active');
}

function closeStudyModeWizard() {
    document.getElementById('study-mode-overlay').classList.remove('active');
}

function selectStudyMode(mode) {
    currentStudyMode = mode;
    closeStudyModeWizard();
    launchSession();
}

function launchSession(isReview = false) {
    currentCardIndex = 0;
    currentSessionScore = 0;
    incorrectItems = [];
    sessionStartTime = Date.now();

    if (!isReview) {
        if (!currentDeck || !currentDeck.cards || currentDeck.cards.length === 0) {
            alert("Ce deck ne contient aucune carte.");
            goHome();
            return;
        }

        activeDeckCards = JSON.parse(JSON.stringify(currentDeck.cards));

        if (currentDeck.type === 'qcm') {
            activeDeckCards.forEach(card => {
                const isBoolean = card.options.length === 2 &&
                    ['vrai', 'faux'].includes(card.options[0].toLowerCase()) &&
                    ['vrai', 'faux'].includes(card.options[1].toLowerCase());

                if (!isBoolean) {
                    const correctVal = card.options[card.correct];
                    shuffleArray(card.options);
                    card.correct = card.options.indexOf(correctVal);
                }
            });
        }

        if (currentStudyMode === 'revision') {
            shuffleArray(activeDeckCards);
        }
    }

    if (studyTimer) clearInterval(studyTimer);
    const timerEl = document.getElementById('timer');

    if (currentStudyMode === 'evaluation') {
        let totalTime = activeDeckCards.length * 5;
        timerEl.style.display = 'flex';
        timerEl.textContent = formatTime(totalTime);

        studyTimer = setInterval(() => {
            totalTime--;
            timerEl.textContent = formatTime(totalTime);
            if (totalTime <= 0) {
                clearInterval(studyTimer);
                finishDeck(true);
            }
        }, 1000);
    } else {
        timerEl.style.display = 'none';
    }

    if (currentDeck.type === 'qcm') {
        startQCM();
    } else {
        startFlashcards();
    }
}

function reviewErrors() {
    const errorCards = incorrectItems.map(index => activeDeckCards[index]);
    if (errorCards.length === 0) return;
    activeDeckCards = errorCards;
    currentStudyMode = 'training';
    launchSession(true);
}

// --- QCM ---
function startQCM() {
    document.getElementById('quiz-category-title').textContent = currentDeck.title;
    document.getElementById('total-questions').textContent = activeDeckCards.length;
    showView('quiz-view');
    loadQuestion();
}

function loadQuestion() {
    const question = activeDeckCards[currentCardIndex];
    document.getElementById('current-question').textContent = currentCardIndex + 1;
    document.getElementById('question-text').innerHTML = question.question;

    const progress = ((currentCardIndex + 1) / activeDeckCards.length) * 100;
    document.getElementById('progress-bar').style.width = progress + '%';

    const container = document.getElementById('options-container');
    const letters = ['a', 'b', 'c', 'd'];
    container.innerHTML = question.options.map((opt, i) => `
        <div class="option" onclick="selectOption(this, ${i})">
            <div class="option-letter">${letters[i]}</div>
            <div class="option-text">${opt}</div>
        </div>
    `).join('');

    document.getElementById('next-btn').disabled = true;
}

function selectOption(element, index) {
    if (document.querySelector('.option.selected')) return;

    const correct = activeDeckCards[currentCardIndex].correct;
    element.classList.add('selected');

    if (index === correct) {
        element.classList.add('correct');
        stats.points += 10;
        currentSessionScore++;

        const audio = document.getElementById('correct-sound');
        if (audio) {
            audio.currentTime = 0;
            audio.play().catch(e => console.log('Audio play failed:', e));
        }
    } else {
        element.classList.add('incorrect');
        document.querySelectorAll('.option')[correct].classList.add('correct');
        incorrectItems.push(currentCardIndex);
    }

    // Compter la carte vue
    stats.cardsLearned++;
    const statCards = document.getElementById('stat-cards');
    if (statCards) statCards.textContent = stats.cardsLearned;

    document.getElementById('next-btn').disabled = false;
}

function nextQuestion() {
    currentCardIndex++;
    if (currentCardIndex < activeDeckCards.length) {
        loadQuestion();
    } else {
        finishDeck();
    }
}

// --- Flashcards ---
function startFlashcards() {
    document.getElementById('flashcard-title').textContent = currentDeck.title;
    document.getElementById('fc-total').textContent = activeDeckCards.length;
    showView('flashcard-view');
    loadFlashcard();
}

function loadFlashcard() {
    const card = activeDeckCards[currentCardIndex];
    document.getElementById('fc-front').textContent = card.front;
    document.getElementById('fc-back').textContent = card.back;
    document.getElementById('fc-current').textContent = currentCardIndex + 1;
    document.getElementById('flashcard-element').classList.remove('flipped');
}

function flipCard() {
    document.getElementById('flashcard-element').classList.toggle('flipped');
}

function rateCard(quality) {
    if (quality === 1) {
        stats.points += 5;
        currentSessionScore++;
    } else {
        incorrectItems.push(currentCardIndex);
    }

    // Compter la carte vue
    stats.cardsLearned++;
    const statCards = document.getElementById('stat-cards');
    if (statCards) statCards.textContent = stats.cardsLearned;

    currentCardIndex++;
    if (currentCardIndex < activeDeckCards.length) {
        loadFlashcard();
    } else {
        finishDeck();
    }
}

// --- Finish ---
function finishDeck(timeOut = false) {
    if (studyTimer) clearInterval(studyTimer);

    const duration = Math.floor((Date.now() - sessionStartTime) / 1000);
    stats.timeSpent += duration;

    if (activeDeckCards.length === currentDeck.cards.length) {
        stats.points += currentSessionScore * 10;
        stats.streak++;
    }

    const sessionData = {
        date: new Date().toISOString(),
        deckId: currentDeck.id,
        deckTitle: currentDeck.title,
        deckType: currentDeck.type,
        score: currentSessionScore,
        total: activeDeckCards.length,
        time: duration
    };

    saveData(sessionData);
    updateStatsUI();

    let scoreDisplay = "";
    let msg = "";
    let pointsEarned = 0;

    if (timeOut) msg = "Temps écoulé ! ⏱️";

    if (currentDeck.type === 'qcm') {
        scoreDisplay = `${currentSessionScore}/${activeDeckCards.length}`;
        pointsEarned = currentSessionScore * 10;

        const ratio = currentSessionScore / activeDeckCards.length;
        if (!timeOut) {
            if (ratio >= 0.8) msg = `Excellent travail, ${userName} ! Tu as très bien réussi.`;
            else if (ratio >= 0.5) msg = `Bien joué, ${userName} ! Continue comme ça.`;
            else msg = `Ne lâche rien ${userName}, la persévérance est la clé !`;
        }
    } else {
        scoreDisplay = "Terminé !";
        pointsEarned = currentSessionScore * 5;
        if (!timeOut) msg = `Session de révision terminée, ${userName} !`;
    }

    document.getElementById('final-score').textContent = scoreDisplay;
    document.getElementById('result-message').textContent = msg;
    document.getElementById('earned-points').textContent = `+ ${pointsEarned} Points`;

    const audio = document.getElementById('finish-sound');
    if (audio) {
        audio.currentTime = 0;
        audio.play().catch(e => console.log('Audio play failed:', e));
    }

    const reviewBtn = document.getElementById('review-errors-btn');
    if (incorrectItems.length > 0) {
        reviewBtn.style.display = 'block';
        reviewBtn.textContent = `Revoir les ${incorrectItems.length} erreurs ↺`;
    } else {
        reviewBtn.style.display = 'none';
    }

    showView('result-view');
}
