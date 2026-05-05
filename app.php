<?php
session_start();

// ===== GESTION UTILISATEUR =====
$dbFile = __DIR__ . '/users.json';
$userCode = $_GET['user'] ?? $_SESSION['user_code'] ?? '';
$sharedDeckId = $_GET['deck'] ?? '';
$user = null;

// Si un deck est partagé et pas de user, rediriger vers login avec le deck ID
if ($sharedDeckId && !$userCode) {
    header('Location: index.php?deck=' . $sharedDeckId);
    exit;
}

// Vérifier si l'utilisateur est connecté
if (!$userCode) {
    header('Location: index.php');
    exit;
}

// Sauvegarder le userCode dans la session
$_SESSION['user_code'] = $userCode;

// Charger les données utilisateur
if (file_exists($dbFile)) {
    $db = json_decode(file_get_contents($dbFile), true);
    if (isset($db['users'][$userCode])) {
        $user = $db['users'][$userCode];
    } else {
        header('Location: index.php');
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}

// Fonction pour sauvegarder les données utilisateur
function saveUserData($userCode, $userData)
{
    global $dbFile;
    $db = json_decode(file_get_contents($dbFile), true);
    $db['users'][$userCode] = $userData;
    file_put_contents($dbFile, json_encode($db, JSON_PRETTY_PRINT));
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Revisio - Quiz</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css?v=2">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    </script>
</head>

<body>
    <!-- Bouton déconnexion -->
    <button class="logout-btn" id="logout-btn" onclick="logout()">🚪 Déconnexion</button>

    <div class="app">
        <!-- ===== HOME VIEW ===== -->
        <div class="view active" id="home-view">
            <!-- Header -->
            <header class="home-header" id="home-header">
                <div class="greeting">
                    <div class="greeting-sub">Prêt(e) à réviser</div>
                    <div class="greeting-main">Bonjour, <?= htmlspecialchars($user['name']) ?> <span class="wave">👋</span></div>
                </div>
                <div class="header-right">
                    <button class="theme-toggle-top" onclick="toggleTheme()" id="theme-toggle-btn" aria-label="Changer le thème">
                        <span class="toggle-icon" id="theme-icon">🌘</span>
                    </button>
                    <div class="user-avatar" onclick="showView('profile')">😺</div>
                </div>
            </header>

            <!-- Level Card (Banner) -->
            <div class="banner">
                <span class="level-badge">NIVEAU</span>
                <h3 id="user-level">Niveau 1</h3>
                <p>Continue comme ça, tu progresses ! 🚀</p>
                
                <div class="xp-bar">
                    <div class="xp-fill" id="xp-progress-bar" style="width: 35%"></div>
                </div>
                <div class="xp-label">
                    <span id="level-progress-text">35 XP</span>
                    <span>100 XP</span>
                </div>

                <!-- Banner Stats Row -->
                <div class="banner-stats">
                    <div class="banner-stat">
                        <span class="banner-stat-value" id="banner-streak">0 jour</span>
                        <span class="banner-stat-label">jour</span>
                    </div>
                    <div class="banner-stat">
                        <span class="banner-stat-value" id="banner-cards">0 carte</span>
                        <span class="banner-stat-label">cartes</span>
                    </div>
                    <div class="banner-stat">
                        <span class="banner-stat-value" id="banner-time">0hh</span>
                        <span class="banner-stat-label"></span>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-icon">🏆</span>
                    <div class="stat-value" id="stat-points">60</div>
                    <div class="stat-label">POINTS</div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">🔥</span>
                    <div class="stat-value" id="stat-streak">0</div>
                    <div class="stat-label">STREAK</div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">📚</span>
                    <div class="stat-value" id="stat-cards">0</div>
                    <div class="stat-label">CARTES</div>
                </div>
            </div>

            <!-- Streak Card -->
            <div class="streak-card">
                <div class="streak-icon">🔥</div>
                <div class="streak-info">
                    <h3>Série quotidienne</h3>
                    <p>Révise chaque jour !</p>
                </div>
                <div class="streak-value">
                    <div class="streak-num" id="streak-value">0</div>
                    <div class="streak-label">JOURS</div>
                </div>
            </div>

            <!-- CTA Button -->
            <button class="cta-button" onclick="showView('library')">
                Commencer à réviser →
            </button>

            <!-- Recent Activity -->
            <div class="activity-section">
                <h3>Activité Récente</h3>
                <div id="recent-activity-list">
                    <div style="text-align: center; color: var(--text-muted); padding: 20px;">Aucune activité récente. Lance un quiz !</div>
                </div>
            </div>
        </div>

        <!-- ===== LIBRARY VIEW ===== -->
        <div class="view" id="library-view">
            <header class="home-header library-header">
                <h1>📚 Bibliothèque</h1>
            </header>

            <!-- Search -->
            <div class="search-bar">
                <input type="text" placeholder="Rechercher un quiz..." oninput="handleSearch(this.value)">
            </div>

            <!-- Filter -->
            <div class="section-header">
                <div class="filter-tabs">
                    <button class="filter-tab active" onclick="filterDecks('all')">Tous</button>
                    <button class="filter-tab" onclick="filterDecks('mine')">Mes decks</button>
                    <button class="filter-tab" onclick="filterDecks('shared')">Partagés</button>
                </div>
            </div>

            <!-- Decks List -->
            <div class="recent-list" id="decks-container">
                <p style="text-align:center; color:var(--text-light); margin-top:20px;">
                    Chargement...
                </p>
            </div>
        </div>

        <!-- Global Bottom Nav -->
        <nav class="bottom-nav">
            <button class="nav-item active" onclick="showView('home')">
                <span class="icon">🏠</span>
                <span>Accueil</span>
            </button>
            <button class="nav-item" onclick="showView('library')">
                <span class="icon">📚</span>
                <span>Biblio</span>
            </button>
            
            <button class="nav-item-add" onclick="openCreationModal()">
                <span>+</span>
            </button>
            
            <button class="nav-item" onclick="showView('favorites')">
                <span class="icon">❤️</span>
                <span>Favoris</span>
            </button>
            <button class="nav-item" onclick="showView('profile')">
                <span class="icon">😺</span>
                <span>Profil</span>
            </button>
        </nav>

        <!-- ===== CREATION TYPE SELECTION MODAL ===== -->
        <div class="wizard-overlay" id="creation-type-overlay">
            <div class="wizard-card" style="max-width: 600px; padding: 0; overflow: hidden;">
                <div class="split-modal-container">
                    <div class="split-modal-option" onclick="selectCreationType('flashcards')" style="background: var(--primary-light); color: white;">
                        <div style="font-size: 3rem; margin-bottom: 15px;">🎴</div>
                        <h3 style="font-size: 1.5rem;">Flashcards</h3>
                        <p style="font-size: 0.9rem; opacity: 0.9;">Mémorisation rapide</p>
                    </div>
                    <div class="split-modal-option" onclick="selectCreationType('qcm')" style="background: var(--accent); color: white;">
                        <div style="font-size: 3rem; margin-bottom: 15px;">📝</div>
                        <h3 style="font-size: 1.5rem;">QCM</h3>
                        <p style="font-size: 0.9rem; opacity: 0.9;">Test de connaissances</p>
                    </div>
                </div>
                <button class="split-modal-close" onclick="closeCreationModal()">Annuler</button>
            </div>
        </div>

        <!-- ===== CREATION MODE SELECTION MODAL ===== -->
        <div class="wizard-overlay" id="creation-mode-overlay">
            <div class="wizard-card" style="max-width: 600px; padding: 0; overflow: hidden;">
                <div class="split-modal-container">
                    <div class="split-modal-option" onclick="selectCreationMode('manual')" style="background: #F3F4F6; color: var(--text);">
                        <div style="font-size: 3rem; margin-bottom: 15px;">✍️</div>
                        <h3 style="font-size: 1.5rem;">Manuel</h3>
                        <p style="font-size: 0.9rem; color: var(--text-light);">Créer carte par carte</p>
                    </div>
                    <div class="split-modal-option" onclick="selectCreationMode('ai')" style="background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); color: white;">
                        <div style="font-size: 3rem; margin-bottom: 15px;">✨</div>
                        <h3 style="font-size: 1.5rem;">IA</h3>
                        <p style="font-size: 0.9rem; opacity: 0.9;">Génération automatique</p>
                    </div>
                </div>
                <button class="split-modal-close" onclick="closeCreationModal()">Annuler</button>
            </div>
        </div>

        <!-- ===== CREATION SOURCE SELECTION MODAL (AI) ===== -->
        <div class="wizard-overlay" id="creation-source-overlay">
            <div class="wizard-card" style="max-width: 600px; padding: 0; overflow: hidden;">
                <div class="split-modal-container">
                    <div class="split-modal-option" onclick="selectSource('text')" style="background: #EEF2FF; color: var(--primary);">
                        <div style="font-size: 3rem; margin-bottom: 15px;">📝</div>
                        <h3 style="font-size: 1.5rem;">Saisir du texte</h3>
                        <p style="font-size: 0.9rem; opacity: 0.8;">Coller vos notes</p>
                    </div>
                    <div class="split-modal-option" onclick="selectSource('import')" style="background: #F0FDF4; color: #15803D;">
                        <div style="font-size: 3rem; margin-bottom: 15px;">📥</div>
                        <h3 style="font-size: 1.5rem;">Importer</h3>
                        <p style="font-size: 0.9rem; opacity: 0.8;">PDF ou URL</p>
                    </div>
                </div>
                <button class="split-modal-close" onclick="closeCreationModal()">Annuler</button>
            </div>
        </div>

        <!-- ===== CREATION IMPORT SELECTION MODAL (AI) ===== -->
        <div class="wizard-overlay" id="creation-import-overlay">
            <div class="wizard-card" style="max-width: 600px; padding: 0; overflow: hidden;">
                <div class="split-modal-container">
                    <div class="split-modal-option" onclick="selectImport('pdf')" style="background: #FFF1F2; color: #BE123C;">
                        <div style="font-size: 3rem; margin-bottom: 15px;">📄</div>
                        <h3 style="font-size: 1.5rem;">PDF</h3>
                        <p style="font-size: 0.9rem; opacity: 0.8;">Document PDF</p>
                    </div>
                    <div class="split-modal-option" onclick="selectImport('url')" style="background: #F0F9FF; color: #0369A1;">
                        <div style="font-size: 3rem; margin-bottom: 15px;">🔗</div>
                        <h3 style="font-size: 1.5rem;">URL</h3>
                        <p style="font-size: 0.9rem; opacity: 0.8;">Page Web</p>
                    </div>
                </div>
                <button class="split-modal-close" onclick="closeCreationModal()">Annuler</button>
            </div>
        </div>

        <!-- ===== DECK STATS PANEL (bottom sheet) ===== -->
        <div class="deck-stats-backdrop" id="deck-stats-backdrop" onclick="closeDeckStats()"></div>
        <div class="deck-stats-sheet" id="deck-stats-sheet">
            <div class="deck-stats-handle"></div>
            <div class="deck-stats-header">
                <div class="deck-stats-icon" id="ds-icon">📝</div>
                <div>
                    <div class="deck-stats-name" id="ds-title">Statistiques</div>
                    <div class="deck-stats-meta" id="ds-meta"></div>
                </div>
                <button class="deck-stats-close" onclick="closeDeckStats()">✕</button>
            </div>

            <div class="ds-section-label">Vue d'ensemble</div>
            <div class="ds-kpi-grid">
                <div class="ds-kpi">
                    <div class="ds-kpi-value" id="ds-plays">—</div>
                    <div class="ds-kpi-label">Parties</div>
                </div>
                <div class="ds-kpi">
                    <div class="ds-kpi-value" id="ds-avg">—</div>
                    <div class="ds-kpi-label">Score moyen</div>
                </div>
                <div class="ds-kpi ds-kpi--accent">
                    <div class="ds-kpi-value" id="ds-best">—</div>
                    <div class="ds-kpi-label">Meilleur</div>
                </div>
                <div class="ds-kpi">
                    <div class="ds-kpi-value" id="ds-mine">—</div>
                    <div class="ds-kpi-label">Mes parties</div>
                </div>
            </div>

            <div class="ds-section-label">Dernière partie</div>
            <div class="ds-last-session" id="ds-last-session">
                <span class="ds-last-date" id="ds-last-date">—</span>
                <span class="ds-last-score" id="ds-last-score">—</span>
            </div>

            <div class="ds-section-label">Mes sessions récentes</div>
            <div class="ds-history" id="ds-history"></div>

            <div style="height: 20px;"></div>
        </div>

        <!-- ===== STATS VIEW ===== -->
        <div class="view" id="stats-view">
            <header class="home-header">
                <button class="back-btn" onclick="goHome()">←</button>
                <h2>Statistiques</h2>
                <div style="width: 40px;"></div> <!-- Spacer for centering -->
            </header>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Points</div>
                    <div class="stat-value" id="stat-points">0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Série</div>
                    <div class="stat-value" id="stat-streak">0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Cartes</div>
                    <div class="stat-value" id="stat-cards">0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Temps</div>
                    <div class="stat-value" id="stat-time">0h</div>
                </div>
            </div>

            <div style="padding: 0 20px 20px;">
                <h3 style="font-size: 1.1rem; margin-bottom: 15px;">Performance (7 derniers jours)</h3>
                <div style="background: white; padding: 15px; border-radius: 16px; box-shadow: var(--shadow-sm);">
                    <canvas id="progressChart"></canvas>
                </div>
            </div>
        </div>

        <!-- ===== FAVORITES VIEW ===== -->
        <div class="view" id="favorites-view">
            <header class="home-header">
                <button class="back-btn" onclick="goHome()">←</button>
                <h2>Favoris</h2>
                <div style="width: 40px;"></div>
            </header>
            <div class="favorites-list" id="favorites-list">
                <p style="text-align:center; color:var(--text-light);">Aucun favori pour le moment.</p>
            </div>
        </div>

        <!-- ===== PROFILE VIEW ===== -->
        <div class="view" id="profile-view">
            <header class="home-header">
                <button class="back-btn" onclick="goHome()">←</button>
                <h2>Mon Profil</h2>
                <div style="width: 40px;"></div>
            </header>

            <!-- Hero card -->
            <div class="profile-hero">
                <div class="profile-avatar-ring">
                    <div class="profile-avatar">😺</div>
                </div>
                <h2 class="profile-hero-name" id="profile-name"><?php echo htmlspecialchars($user['name']); ?></h2>
                <div class="profile-code" onclick="copyUserCode()">
                    <span>@<?php echo htmlspecialchars($userCode); ?></span>
                    <span class="copy-icon">📋</span>
                </div>
                <div class="profile-level-badge">Niveau <span id="profile-level">1</span></div>
                <!-- XP bar -->
                <div class="profile-xp-wrap">
                    <div class="profile-xp-bar"><div class="profile-xp-fill" id="profile-xp-fill" style="width:0%"></div></div>
                    <div class="profile-xp-label"><span id="profile-xp-cur">0</span> / <span id="profile-xp-max">100</span> XP</div>
                </div>
            </div>

            <!-- Stats grid -->
            <div class="profile-stats-grid">
                <div class="profile-stat-card">
                    <span class="profile-stat-icon">⭐</span>
                    <span class="profile-stat-value" id="p-total-points">0</span>
                    <span class="profile-stat-label">Points</span>
                </div>
                <div class="profile-stat-card">
                    <span class="profile-stat-icon">🔥</span>
                    <span class="profile-stat-value" id="p-streak">0</span>
                    <span class="profile-stat-label">Série</span>
                </div>
                <div class="profile-stat-card">
                    <span class="profile-stat-icon">🎴</span>
                    <span class="profile-stat-value" id="p-cards">0</span>
                    <span class="profile-stat-label">Cartes apprises</span>
                </div>
                <div class="profile-stat-card">
                    <span class="profile-stat-icon">🎮</span>
                    <span class="profile-stat-value" id="p-sessions">0</span>
                    <span class="profile-stat-label">Sessions</span>
                </div>
            </div>

            <!-- Trophies -->
            <div class="trophies-section">
                <div class="section-header">
                    <h3>🏆 Trophées</h3>
                    <span class="trophy-count-badge" id="trophy-count">0/9</span>
                </div>
                <div class="trophies-grid" id="trophies-container"></div>
            </div>

            <div class="profile-actions">
                <button class="action-btn logout" onclick="logout()">
                    <span>🚪</span> Déconnexion
                </button>
            </div>
        </div>

        <!-- ===== QUIZ VIEW ===== -->
        <div class="view" id="quiz-view">
            <!-- Header -->
            <header class="quiz-header">
                <button class="back-btn" onclick="goHome()">←</button>
                <h1 class="quiz-title" id="quiz-category-title">Math</h1>
            </header>

            <!-- Progress -->
            <div class="progress-header">
                <div class="question-counter">
                    <h4>Question</h4>
                    <p><span id="current-question">1</span>/<span id="total-questions">20</span></p>
                </div>
                <div class="timer" id="timer">00:25</div>
            </div>

            <div class="quiz-progress">
                <div class="quiz-progress-fill" id="progress-bar" style="width: 5%"></div>
            </div>

            <!-- Question -->
            <div class="question-card">
                <p id="question-text">Quels sont les 3 nombres qui donnent le même résultat, qu'on les <span
                        class="highlight">additionne</span> ou qu'on les <span class="highlight">multiplie</span> ?</p>
            </div>

            <!-- Options -->
            <div class="options-list" id="options-container">
                <div class="option" onclick="selectOption(this, 0)">
                    <div class="option-letter">a</div>
                    <div class="option-text">6, 3 et 4</div>
                </div>
                <div class="option" onclick="selectOption(this, 1)">
                    <div class="option-letter">b</div>
                    <div class="option-text">1, 2 et 3</div>
                </div>
                <div class="option" onclick="selectOption(this, 2)">
                    <div class="option-letter">c</div>
                    <div class="option-text">2, 4 et 6</div>
                </div>
                <div class="option" onclick="selectOption(this, 3)">
                    <div class="option-letter">d</div>
                    <div class="option-text">1, 2 et 4</div>
                </div>
            </div>

            <!-- Next Button -->
            <button class="next-btn" id="next-btn" onclick="nextQuestion()" disabled>Suivant</button>
        </div>

        <!-- ===== RESULT VIEW ===== -->
        <div class="view result-view" id="result-view">
            <div class="result-celebration">
                <span class="confetti">⭐</span>
                <span class="confetti">✨</span>
                <span class="confetti">🎉</span>
                <span class="confetti">⭐</span>
                <span class="confetti">✨</span>
                <span class="confetti">🎊</span>
                <span class="confetti">⭐</span>
                <span class="confetti">✨</span>
                <div class="result-circle">
                    <span class="result-avatar">😺</span>
                </div>
            </div>

            <div class="result-score">
                <span>Votre score</span>
                <h2 id="final-score">19/20</h2>
            </div>

            <h1 class="result-title">Félicitations !</h1>
            <p class="result-message" id="result-message">Excellent travail !</p>

            <div class="result-points">
                <span>🏆</span>
                <p id="earned-points">200 Points</p>
            </div>

            <button class="result-btn" onclick="goHome()">Retour à l'accueil</button>
            <button class="result-btn review-btn" id="review-errors-btn" onclick="reviewErrors()"
                style="display: none;">
                Corriger mes erreurs ↺
            </button>
        </div>

        <!-- ===== STUDY MODE WIZARD ===== -->
        <div class="wizard-overlay" id="study-mode-overlay">
            <div class="wizard-card">
                <div class="wizard-header">
                    <h3>Mode de révision</h3>
                    <p>Comment souhaitez-vous étudier ce deck ?</p>
                </div>

                <div class="mode-options">
                    <button class="mode-btn" onclick="selectStudyMode('training')">
                        <span class="mode-icon">🏋️</span>
                        <div class="mode-info">
                            <strong>Entraînement</strong>
                            <p>Mode classique, à votre rythme</p>
                        </div>
                    </button>
                    <button class="mode-btn" onclick="selectStudyMode('revision')">
                        <span class="mode-icon">🔀</span>
                        <div class="mode-info">
                            <strong>Révision</strong>
                            <p>Questions et réponses aléatoires</p>
                        </div>
                    </button>
                    <button class="mode-btn" onclick="selectStudyMode('evaluation')">
                        <span class="mode-icon">⏱️</span>
                        <div class="mode-info">
                            <strong>Évaluation</strong>
                            <p>Chrono: 5s par question !</p>
                        </div>
                    </button>
                </div>

                <div class="wizard-actions" style="display: flex; gap: 10px;">
                    <button class="btn-cancel" onclick="closeStudyModeWizard()">Annuler</button>
                    <button class="btn-submit" onclick="shareDeck()"
                        style="background: linear-gradient(135deg, #10B981, #059669);">
                        <span>🔗 Partager</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ===== FLASHCARD VIEW ===== -->
        <div class="view" id="flashcard-view">
            <header class="quiz-header">
                <button class="back-btn" onclick="goHome()">←</button>
                <h1 class="quiz-title" id="flashcard-title">Titre</h1>
            </header>

            <div class="progress-header">
                <div class="question-counter">
                    <h4>Carte</h4>
                    <p><span id="fc-current">1</span>/<span id="fc-total">20</span></p>
                </div>
            </div>

            <div class="flashcard-container" onclick="flipCard()">
                <div class="flashcard" id="flashcard-element">
                    <div class="flashcard-front">
                        <p id="fc-front">Front</p>
                    </div>
                    <div class="flashcard-back">
                        <p id="fc-back">Back</p>
                    </div>
                </div>
            </div>

            <div class="fc-controls">
                <button class="fc-btn bad" onclick="rateCard(0)">Difficile</button>
                <button class="fc-btn good" onclick="rateCard(1)">Facile</button>
            </div>
        </div>

        <!-- ===== WIZARD VIEW ===== -->
        <div class="view" id="wizard-view">
            <div class="wizard-overlay" id="wizardOverlay">
                <div class="wizard-card">
                    <div class="wizard-header">
                        <h3>Bienvenue dans le Wizard !</h3>
                        <p>Complétez les étapes suivantes pour personnaliser votre expérience.</p>
                    </div>

                    <div class="form-group">
                        <label for="userName">Votre nom</label>
                        <input type="text" id="userName" class="form-control" placeholder="Entrez votre nom">
                    </div>

                    <div class="form-group">
                        <label for="userAvatar">Choisissez un avatar</label>
                        <input type="text" id="userAvatar" class="form-control"
                            placeholder="Entrez un emoji ou un texte">
                    </div>

                    <div class="wizard-actions">
                        <button class="btn-cancel" onclick="closeWizard()">Annuler</button>
                        <button class="btn-submit" id="startQuizBtn" onclick="startQuizFromWizard()">
                            <span>Commencer le Quiz</span> <span>→</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- ===== WIZARD MODAL (AI) ===== -->
        <div class="wizard-overlay" id="wizard-overlay">
            <div class="wizard-card">
                <div class="wizard-header">
                    <h3 id="wizard-title">Nouveau Deck</h3>
                    <p id="wizard-subtitle">Configurez votre génération</p>
                </div>
                <div class="form-group">
                    <label>Sujet / Titre</label>
                    <input type="text" class="form-control" id="wiz-topic" placeholder="Ex: La Révolution Française">
                </div>
                <div class="form-group">
                    <label>Contenu (Optionnel)</label>
                    <!-- PDF Upload UI -->
                    <div id="wiz-pdf-group" style="margin-bottom: 8px; display: none; align-items: center; gap: 10px;">
                        <button onclick="document.getElementById('pdf-upload').click()" class="btn-secondary" style="background: #e0e7ff; color: #4f46e5; border: none; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                            <span>📄</span> Importer un PDF
                        </button>
                        <input type="file" id="pdf-upload" accept=".pdf" style="display: none;" onchange="handlePdfUpload(this)">
                        <span id="pdf-status" style="font-size: 0.8rem; color: #666;"></span>
                    </div>
                    <!-- URL Import UI -->
                    <div id="wiz-url-group" style="margin-bottom: 8px; display: none; gap: 10px;">
                        <input type="url" id="wiz-url-input" class="form-control" placeholder="https://exemple.com/article" style="margin-bottom:0;">
                        <button onclick="handleUrlImport()" class="btn-secondary" style="background: #e0e7ff; color: #4f46e5; border: none; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; cursor: pointer; white-space: nowrap;">
                            📥 Extraire
                        </button>
                    </div>
                    <textarea class="form-control" id="wiz-content"
                        placeholder="Collez vos notes ici pour plus de précision..."></textarea>
                </div>
                <div class="form-group">
                    <label>Nombre de questions</label>
                    <select class="form-control" id="wiz-count">
                        <option value="5">5 questions</option>
                        <option value="10" selected>10 questions</option>
                        <option value="15">15 questions</option>
                        <option value="20">20 questions</option>
                        <option value="30">30 questions</option>
                        <option value="50">50 questions</option>
                        <option value="75">75 questions</option>
                    </select>
                </div>
                <div class="wizard-actions">
                    <button class="btn-cancel" onclick="closeWizard()">Annuler</button>
                    <button class="btn-submit" id="wiz-submit" onclick="submitWizard()">
                        <span>✨ Générer</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ===== WIZARD MODAL (MANUAL) ===== -->
        <div class="wizard-overlay" id="wizard-manual-overlay">
            <div class="wizard-card" style="max-width: 500px; max-height: 85vh; overflow-y: auto;">
                <div class="wizard-header">
                    <h3 id="manual-wizard-title">Nouveau Deck Manuel</h3>
                    <p id="manual-wizard-subtitle">Créez vos propres cartes</p>
                </div>
                <div class="form-group">
                    <label>Titre du deck</label>
                    <input type="text" class="form-control" id="manual-deck-title"
                        placeholder="Ex: Vocabulaire Anglais">
                </div>

                <div id="manual-cards-container" style="margin: 20px 0;">
                    <!-- Cards will be added here dynamically -->
                </div>

                <button class="btn-submit" onclick="addManualCard()" style="width: 100%; margin-bottom: 15px;">
                    <span>➕ Ajouter une carte</span>
                </button>

                <div class="wizard-actions">
                    <button class="btn-cancel" onclick="closeManualWizard()">Annuler</button>
                    <button class="btn-submit" id="manual-submit" onclick="submitManualDeck()">
                        <span>💾 Créer le deck</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variables PHP injectées
        userCode = '<?= $userCode ?>';
        userName = '<?= htmlspecialchars($user['name']) ?>';
    </script>
    <audio id="correct-sound" src="assets/sounds/correct.mp3" preload="auto"></audio>
    <audio id="finish-sound" src="assets/sounds/fin.mp3" preload="auto"></audio>
    <script src="assets/js/state.js"></script>
    <script src="assets/js/utils.js"></script>
    <script src="assets/js/api-client.js"></script>
    <script src="assets/js/ui.js"></script>
    <script src="assets/js/creation.js"></script>
    <script src="assets/js/import.js"></script>
    <script src="assets/js/study.js"></script>
    <script src="assets/js/nav.js"></script>
    <script src="assets/js/init.js"></script>
</body>

</html>