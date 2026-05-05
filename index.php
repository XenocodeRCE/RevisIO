<?php
// ===== GESTION DE LA BASE DE DONNÉES JSON =====
$dbFile = __DIR__ . '/users.json';

// Créer le fichier s'il n'existe pas
if (!file_exists($dbFile)) {
    file_put_contents($dbFile, json_encode(['users' => []], JSON_PRETTY_PRINT));
}

// Lire la DB
function getDB() {
    global $dbFile;
    return json_decode(file_get_contents($dbFile), true);
}

// Sauvegarder la DB
function saveDB($data) {
    global $dbFile;
    file_put_contents($dbFile, json_encode($data, JSON_PRETTY_PRINT));
}

// Générer un code unique à 6 chiffres
function generateUniqueCode() {
    $db = getDB();
    do {
        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    } while (isset($db['users'][$code]));
    return $code;
}

// Traitement des requêtes
$message = '';
$messageType = '';
$newUserCode = '';
$sharedDeckId = $_GET['deck'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $db = getDB();
    
    if ($action === 'login') {
        $code = $_POST['code'] ?? '';
        $deckParam = $_POST['deck'] ?? '';
        if (isset($db['users'][$code])) {
            // Connexion réussie - redirection vers l'app
            $db['users'][$code]['lastLogin'] = date('Y-m-d H:i:s');
            saveDB($db);
            
            // Rediriger avec ou sans deck
            if ($deckParam) {
                header('Location: app.php?user=' . $code . '&deck=' . $deckParam);
            } else {
                header('Location: app.php?user=' . $code);
            }
            exit;
        } else {
            $message = 'Code invalide. Vérifiez votre code ou créez un compte.';
            $messageType = 'error';
        }
    } elseif ($action === 'register') {
        $name = trim($_POST['name'] ?? '');
        $deckParam = $_POST['deck'] ?? '';
        if (strlen($name) >= 2) {
            $code = generateUniqueCode();
            $db['users'][$code] = [
                'name' => $name,
                'createdAt' => date('Y-m-d H:i:s'),
                'lastLogin' => date('Y-m-d H:i:s'),
                'points' => 0,
                'quizzes' => []
            ];
            saveDB($db);
            $newUserCode = $code;
            
            // Si deck partagé, rediriger automatiquement après inscription
            if ($deckParam) {
                header('Location: app.php?user=' . $code . '&deck=' . $deckParam);
                exit;
            }
            
            $message = "Compte créé ! Votre code personnel est : <strong>$code</strong><br>Notez-le bien, c'est votre clé d'accès !";
            $messageType = 'success';
        } else {
            $message = 'Veuillez entrer un prénom valide (min. 2 caractères).';
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta name="x-poe-datastore-behavior" content="local_only">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' 'unsafe-inline' 'unsafe-eval' data: blob: https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://code.jquery.com https://unpkg.com https://d3js.org https://threejs.org https://cdn.plot.ly https://stackpath.bootstrapcdn.com https://maps.googleapis.com https://cdn.tailwindcss.com https://ajax.googleapis.com https://kit.fontawesome.com https://cdn.datatables.net https://maxcdn.bootstrapcdn.com https://code.highcharts.com https://tako-static-assets-production.s3.amazonaws.com https://www.youtube.com https://fonts.googleapis.com https://fonts.gstatic.com https://pfst.cf2.poecdn.net https://puc.poecdn.net https://i.imgur.com https://wikimedia.org https://*.icons8.com https://*.giphy.com https://picsum.photos https://images.unsplash.com; frame-src 'self' https://www.youtube.com https://trytako.com; child-src 'self'; manifest-src 'self'; worker-src 'self'; upgrade-insecure-requests; block-all-mixed-content;">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RevisIO - Reviser simplement</title>
    <style>
        :root {
            --primary: #6366f1;
            --primary-light: #818cf8;
            --primary-dark: #4f46e5;
            --accent: #f59e0b;
            --accent-light: #fbbf24;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --bg-tertiary: #f1f5f9;
            --text: #1e293b;
            --text-light: #64748b;
            --text-muted: #94a3b8;
            --border: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --radius: 16px;
            --card-gradient: linear-gradient(135deg, #f8fafc, #f1f5f9);
        }

        [data-theme="dark"] {
            --bg: #0f172a;
            --card-bg: #1e293b;
            --bg-tertiary: #1e2d45;
            --text: #f1f5f9;
            --text-light: #b0bcc8;
            --text-muted: #8494a7;
            --border: #334155;
            --shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
            --shadow-lg: 0 20px 40px rgba(0, 0, 0, 0.5);
            --card-gradient: linear-gradient(135deg, #1e293b, #1e2d45);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        button, input, select, textarea {
            font-family: inherit;
            color: inherit;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.6;
            overflow-x: hidden;
            transition: background-color 0.4s ease, color 0.4s ease;
        }

        /* --- Animations Keyframes --- */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }

        @keyframes pulse-glow {
            0% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.7); }
            70% { box-shadow: 0 0 0 15px rgba(37, 99, 235, 0); }
            100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0); }
        }

        @keyframes blob {
            0% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
            100% { transform: translate(0px, 0px) scale(1); }
        }

        @keyframes slideUpFade {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Classes pour l'animation JS */
        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s ease-out;
        }
        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        /* --- Background Blobs --- */
        .background-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            overflow: hidden;
            z-index: -1;
            pointer-events: none;
        }
        .blob {
            position: absolute;
            filter: blur(80px);
            opacity: 0.4;
            animation: blob 10s infinite ease-in-out alternate;
        }
        .blob-1 { top: -10%; left: -10%; width: 500px; height: 500px; background: #bfdbfe; }
        .blob-2 { top: 20%; right: -10%; width: 400px; height: 400px; background: #e9d5ff; animation-delay: 2s; }
        .blob-3 { bottom: -10%; left: 20%; width: 600px; height: 600px; background: #fef3c7; animation-delay: 4s; }

        /* --- Typography & Buttons --- */
        h1, h2, h3 {
            font-weight: 800;
            letter-spacing: -0.025em;
            color: var(--text);
        }

        p {
            color: var(--text-light);
            font-size: 1.125rem;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 1rem;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            box-shadow: 0 4px 14px 0 rgba(37, 99, 235, 0.39);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.23);
        }

        .btn-outline {
            background: rgba(255, 255, 255, 0.08);
            color: var(--text);
            border: 1px solid var(--border);
            backdrop-filter: blur(4px);
        }

        .btn-outline:hover {
            border-color: var(--text);
            background: var(--card-bg);
        }

        /* --- Navbar --- */
        nav {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(248, 250, 252, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 1rem 1.5rem;
            transition: background-color 0.4s ease, border-color 0.4s ease;
        }

        [data-theme="dark"] nav {
            background: rgba(15, 23, 42, 0.88);
        }

        .theme-toggle-index {
            background: var(--card-bg);
            border: 1px solid var(--border);
            padding: 8px 14px;
            border-radius: 50px;
            font-size: 1.1rem;
            cursor: pointer;
            box-shadow: var(--shadow);
            transition: transform 0.2s, background-color 0.4s ease;
            line-height: 1;
        }

        .theme-toggle-index:hover {
            transform: scale(1.08);
        }

        @keyframes theme-flip {
            0%   { transform: rotateY(0deg) scale(1); opacity: 1; }
            40%  { transform: rotateY(90deg) scale(0.7); opacity: 0; }
            60%  { transform: rotateY(-90deg) scale(0.7); opacity: 0; }
            100% { transform: rotateY(0deg) scale(1); opacity: 1; }
        }

        .theme-switching-index {
            animation: theme-flip 0.45s ease forwards;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 900;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* --- Hero Section --- */
        .hero {
            padding: 6rem 1.5rem 4rem;
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr;
            gap: 3rem;
            align-items: center;
            text-align: center;
            position: relative;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            line-height: 1.1;
            margin-bottom: 1.5rem;
        }

        .hero-content span.highlight {
            color: var(--primary);
            position: relative;
            display: inline-block;
        }
        
        /* Effet curseur machine à écrire */
        .typewriter-cursor::after {
            content: '|';
            animation: blink 1s infinite;
            color: var(--primary);
        }
        @keyframes blink { 50% { opacity: 0; } }

        .hero-buttons {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        /* --- Auth Card Styles (Merged from index2.php) --- */
        .auth-card {
            background: var(--card-bg);
            border-radius: 28px;
            padding: 30px 25px;
            box-shadow: var(--shadow-lg);
            max-width: 400px;
            margin: 0 auto;
            text-align: left;
            animation: slideUp 0.6s ease-out 0.3s backwards;
            border: 1px solid var(--border);
        }

        .auth-tabs {
            display: flex;
            background: var(--bg-tertiary);
            border-radius: 14px;
            padding: 4px;
            margin-bottom: 25px;
        }

        .auth-tab {
            flex: 1;
            padding: 12px;
            border: none;
            background: transparent;
            font-family: inherit;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-muted);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .auth-tab.active {
            background: var(--card-bg);
            color: var(--primary);
            box-shadow: var(--shadow);
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-main);
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 15px 18px;
            border: 2px solid var(--border);
            border-radius: 14px;
            font-family: inherit;
            font-size: 1rem;
            color: var(--text);
            background: var(--bg-tertiary);
            transition: border-color 0.3s, background-color 0.3s, box-shadow 0.3s;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--card-bg);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
        }

        .form-input::placeholder {
            color: var(--text-muted);
        }

        .btn-auth {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 14px;
            background: var(--primary);
            color: white;
            font-family: inherit;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-auth:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
        }

        .auth-form {
            display: none;
        }

        .auth-form.active {
            display: block;
        }

        .code-inputs {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-bottom: 20px;
        }

        .code-digit {
            width: 48px;
            height: 58px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-family: inherit;
            font-size: 1.5rem;
            font-weight: 700;
            text-align: center;
            color: var(--primary);
            background: var(--bg-tertiary);
            transition: all 0.3s;
        }

        .code-digit:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--card-bg);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
            transform: scale(1.05);
        }

        .code-digit.filled {
            background: rgba(99, 102, 241, 0.1);
            border-color: var(--primary-light);
        }

        .message {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            text-align: center;
            animation: slideUp 0.3s ease-out;
        }

        .message.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .code-display {
            background: var(--primary);
            color: white;
            padding: 20px;
            border-radius: 16px;
            text-align: center;
            margin: 15px 0;
            box-shadow: var(--shadow-lg);
        }

        .code-display .code {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: 8px;
            margin: 10px 0;
        }

        .code-display .hint {
            font-size: 0.85rem;
            opacity: 0.9;
        }

        .form-hint {
            text-align: center;
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 20px;
        }
        
        .form-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .form-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        /* --- Feature Sections --- */
        .feature-section {
            padding: 6rem 1.5rem;
            position: relative;
        }

        .feature-section:nth-child(even) {
            background-color: var(--bg-section);
        }

        .feature-container {
            max-width: 1100px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr;
            gap: 4rem;
            align-items: center;
        }

        .feature-visual {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            padding: 2.5rem;
            border: 1px solid var(--border);
            min-height: 350px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            transition: transform 0.3s;
        }
        
        .feature-visual:hover {
            transform: translateY(-5px);
        }

        /* Interactive Quiz Visual */
        .quiz-option {
            border: 1px solid var(--border);
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: var(--card-bg);
            color: var(--text);
        }
        .quiz-option:hover {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        .quiz-option.correct {
            background: #dcfce7;
            border-color: #22c55e;
            color: #15803d;
        }

        /* XP Progress Bar Animation */
        .xp-bar-container {
            background: var(--bg-tertiary);
            height: 12px;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 5px;
            position: relative;
        }
        .xp-bar-fill {
            width: 0%; /* Starts at 0 for animation */
            background: linear-gradient(90deg, var(--primary) 0%, #60a5fa 100%);
            height: 100%;
            border-radius: 6px;
            transition: width 1.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* --- Footer --- */
        footer {
            padding: 5rem 1.5rem;
            text-align: center;
            background: var(--card-bg);
            border-top: 1px solid var(--border);
            transition: background-color 0.4s ease;
        }

        /* --- AI Generator Animations --- */
        @keyframes popIn {
            0% { transform: scale(0); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .loader {
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top: 3px solid white;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
        }

        /* --- Media Queries --- */
        @media (min-width: 768px) {
            .hero {
                grid-template-columns: 1.2fr 0.8fr;
                text-align: left;
                padding: 8rem 1.5rem;
            }
            .hero-buttons {
                justify-content: flex-start;
            }
            .feature-container {
                grid-template-columns: 1fr 1fr;
            }
            /* Alternate order */
            .feature-section:nth-child(odd) .feature-container .feature-text { order: 1; }
            .feature-section:nth-child(odd) .feature-container .feature-visual { order: 2; }
            .feature-section:nth-child(even) .feature-container .feature-text { order: 2; }
            .feature-section:nth-child(even) .feature-container .feature-visual { order: 1; }
        }

        /* --- Mobile: max 767px --- */
        @media (max-width: 767px) {
            .hero {
                padding: 4rem 1rem 2rem;
                gap: 2rem;
            }

            .hero-content h1 {
                font-size: 2.2rem;
                line-height: 1.15;
                margin-bottom: 1rem;
            }

            .hero-content p {
                font-size: 1rem;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: stretch;
                gap: 0.75rem;
            }

            .hero-buttons .btn {
                text-align: center;
            }

            .auth-card {
                padding: 22px 18px;
                border-radius: 20px;
                max-width: 100%;
            }

            .code-inputs {
                gap: 6px;
            }

            .code-digit {
                width: 42px;
                height: 52px;
                font-size: 1.3rem;
                border-radius: 10px;
            }

            .feature-section {
                padding: 3.5rem 1rem;
            }

            .feature-visual {
                padding: 1.5rem;
                min-height: auto;
            }

            footer {
                padding: 3rem 1rem;
            }

            footer h2 {
                font-size: 1.8rem !important;
            }
        }

        /* --- Very small phones: max 380px --- */
        @media (max-width: 380px) {
            .hero-content h1 {
                font-size: 1.85rem;
            }

            .code-digit {
                width: 36px;
                height: 46px;
                font-size: 1.1rem;
                border-radius: 8px;
            }

            .code-inputs {
                gap: 4px;
            }
        }

        /* --- Navbar mobile --- */
        @media (max-width: 480px) {
            nav {
                padding: 0.85rem 1rem;
            }

            .logo {
                font-size: 1.25rem;
            }

            .nav-links-text {
                display: none;
            }
        }
    </style>
<script src="https://puc.poecdn.net/authenticated_preview_page/syncedState.3f7572448765332f3047.js"></script></head>
<body>

    <!-- Background Blobs -->
    <div class="background-shapes">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>

    <!-- Navigation -->
    <nav>
        <div class="nav-container">
            <a href="#" class="logo">⚡ RevisIO</a>
            <div style="display: flex; gap: 0.75rem; align-items: center;">
                <button class="theme-toggle-index" id="theme-toggle-index" onclick="toggleThemeIndex()" aria-label="Changer le thème"><span id="theme-icon-index">🌘</span></button>
                <a href="#" class="nav-links-text" onclick="switchTab('login'); document.querySelector('.hero').scrollIntoView({behavior: 'smooth'}); return false;" style="text-decoration: none; color: var(--text); font-weight: 500; transition: color 0.2s;">Connexion</a>
                <a href="#" onclick="switchTab('register'); document.querySelector('.hero').scrollIntoView({behavior: 'smooth'}); return false;" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">S'inscrire</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content reveal active">
            <h1>Révisez <span id="dynamic-text" class="highlight typewriter-cursor">la philo</span><br>sans effort.</h1>
            <p>L'alternative moderne aux usines à gaz. Créez des QCM et des Flashcards en quelques secondes, suivez votre progression et débloquez des trophées.</p>
            <div class="hero-buttons">
                <a href="#" onclick="switchTab('register'); document.querySelector('.auth-card').scrollIntoView({behavior: 'smooth'}); return false;" class="btn btn-primary">Commencer maintenant</a>
                <!--a href="#" class="btn btn-outline">Voir la démo</a-->
            </div>
            <!--div style="margin-top: 2rem; display: flex; align-items: center; gap: 10px; justify-content: center; opacity: 0.8;">
                <div style="display: flex;">
                    <span style="color: #fbbf24;">★</span><span style="color: #fbbf24;">★</span><span style="color: #fbbf24;">★</span><span style="color: #fbbf24;">★</span><span style="color: #fbbf24;">★</span>
                </div>
                <span style="font-size: 0.9rem;">Utilisé par +10 étudiants</span>
            </div-->
        </div>

        <!-- Auth Card (Replaces Mockup) -->
        <div class="auth-card">
            <div class="auth-tabs">
                <button class="auth-tab <?= empty($newUserCode) ? 'active' : '' ?>" onclick="switchTab('login')">Connexion</button>
                <button class="auth-tab <?= !empty($newUserCode) ? 'active' : '' ?>" onclick="switchTab('register')">Inscription</button>
            </div>

            <?php if ($message): ?>
            <div class="message <?= $messageType ?>">
                <?= $message ?>
            </div>
            <?php endif; ?>

            <?php if ($sharedDeckId): ?>
            <div class="message success">
                📚 <strong>Deck partagé !</strong><br>
                Quelqu'un vous a partagé un deck. Connectez-vous pour y accéder.
            </div>
            <?php endif; ?>

            <?php if ($newUserCode): ?>
            <div class="code-display">
                <div class="hint">🎉 Votre code personnel</div>
                <div class="code"><?= $newUserCode ?></div>
                <div class="hint">Gardez-le précieusement !</div>
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form class="auth-form <?= empty($newUserCode) ? 'active' : '' ?>" id="login-form" method="POST">
                <input type="hidden" name="action" value="login">
                <?php if ($sharedDeckId): ?>
                <input type="hidden" name="deck" value="<?= htmlspecialchars($sharedDeckId) ?>">
                <?php endif; ?>
                <p class="form-hint">Entrez votre code à 6 chiffres pour vous connecter</p>
                
                <div class="code-inputs">
                    <input type="text" class="code-digit" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                    <input type="text" class="code-digit" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                    <input type="text" class="code-digit" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                    <input type="text" class="code-digit" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                    <input type="text" class="code-digit" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                    <input type="text" class="code-digit" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                </div>
                <input type="hidden" name="code" id="login-code">
                
                <button type="submit" class="btn-auth">
                    Se connecter <span>→</span>
                </button>
                
                <div class="form-footer">
                    Pas encore de compte ? <a href="#" onclick="switchTab('register'); return false;">Créer un compte</a>
                </div>
            </form>

            <!-- Register Form -->
            <form class="auth-form <?= !empty($newUserCode) ? 'active' : '' ?>" id="register-form" method="POST">
                <input type="hidden" name="action" value="register">
                <?php if ($sharedDeckId): ?>
                <input type="hidden" name="deck" value="<?= htmlspecialchars($sharedDeckId) ?>">
                <?php endif; ?>
                <p class="form-hint">Créez votre compte et recevez votre code personnel</p>
                
                <div class="form-group">
                    <label class="form-label">Votre prénom</label>
                    <input type="text" name="name" class="form-input" placeholder="Ex: Sarah" required minlength="2">
                </div>
                
                <button type="submit" class="btn-auth">
                    Créer mon compte <span>✨</span>
                </button>
                
                <div class="form-footer">
                    Déjà un compte ? <a href="#" onclick="switchTab('login'); return false;">Se connecter</a>
                </div>
            </form>
        </div>
    </section>

    <!-- Feature 1: Modes de révision -->
    <section class="feature-section">
        <div class="feature-container">
            <div class="feature-text reveal">
                <h2>Entraînement ou Évaluation ?</h2>
                <p>Revis.IO s'adapte à votre façon d'apprendre. Ne perdez plus de temps à configurer des options complexes.</p>
                <ul style="list-style: none; margin-top: 1.5rem;">
                    <li style="margin-bottom: 1rem; display: flex; align-items: center; gap: 15px;">
                        <span style="background: rgba(99,102,241,0.12); color: var(--primary); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.2rem;">🎴</span>
                        <div><strong>Mode Flashcards :</strong> Retournez les cartes pour mémoriser.</div>
                    </li>
                    <li style="margin-bottom: 1rem; display: flex; align-items: center; gap: 15px;">
                        <span style="background: rgba(99,102,241,0.12); color: var(--primary); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.2rem;">📝</span>
                        <div><strong>Mode QCM :</strong> Questions générées automatiquement.</div>
                    </li>
                    <li style="margin-bottom: 1rem; display: flex; align-items: center; gap: 15px;">
                        <span style="background: rgba(99,102,241,0.12); color: var(--primary); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.2rem;">⏱️</span>
                        <div><strong>Mode Chrono :</strong> 5 secondes par question !</div>
                    </li>
                </ul>
            </div>
            <div class="feature-visual reveal">
                <!-- Interactive Quiz Mockup -->
                <div style="text-align: center; margin-bottom: 20px; font-weight: bold; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; font-size: 0.8rem;">Mathématiques</div>
                <div style="background: var(--bg-tertiary); padding: 25px; border-radius: 12px; margin-bottom: 20px; text-align: center; font-weight: 500; box-shadow: inset 0 2px 4px rgba(0,0,0,0.08);">
                    Quels sont les 3 nombres qui donnent le même résultat additionnés ou multipliés ?
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="quiz-option" onclick="this.style.background='#fee2e2'; this.style.borderColor='#ef4444'; this.style.color='#b91c1c';">6, 3 et 4</div>
                    <div class="quiz-option correct-answer-trigger">1, 2 et 3</div>
                    <div class="quiz-option" onclick="this.style.background='#fee2e2'; this.style.borderColor='#ef4444'; this.style.color='#b91c1c';">2, 4 et 6</div>
                    <div class="quiz-option" onclick="this.style.background='#fee2e2'; this.style.borderColor='#ef4444'; this.style.color='#b91c1c';">1, 2 et 4</div>
                </div>
                <p style="text-align: center; margin-top: 15px; font-size: 0.8rem; color: var(--text-muted);">(Essayez de cliquer !)</p>
            </div>
        </div>
    </section>

    <!-- Feature 2: IA vs Manuel -->
    <section class="feature-section">
        <div class="feature-container">
            <div class="feature-text reveal">
                <h2>Générez vos decks en un éclair.</h2>
                <p>Pourquoi passer des heures à copier-coller ? Notre assistant IA crée vos révisions pour vous.</p>
                <div style="margin-top: 2rem; display: flex; gap: 15px;">
                    <div style="flex: 1; background: var(--bg-tertiary); padding: 15px; border-radius: 12px; border: 1px solid var(--border);">
                        <div style="font-size: 1.5rem; margin-bottom: 5px;">✨</div>
                        <strong>Génération IA</strong>
                        <p style="font-size: 0.9rem;">Entrez un sujet, c'est prêt.</p>
                    </div>
                    <div style="flex: 1; background: var(--bg-tertiary); padding: 15px; border-radius: 12px; border: 1px solid var(--border);">
                        <div style="font-size: 1.5rem; margin-bottom: 5px;">📤</div>
                        <strong>Partage Facile</strong>
                        <p style="font-size: 0.9rem;">Partagez vos listes avec vos amis.</p>
                    </div>
                </div>
            </div>
            <div class="feature-visual reveal" style="background: linear-gradient(to bottom right, #f8fafc, #eef2ff);">
                <div id="ai-generator-card" style="background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow-lg); transition: background-color 0.4s ease; min-height: 320px; display: flex; flex-direction: column; justify-content: center;">
                    
                    <!-- Input View -->
                    <div id="ai-input-view">
                        <h3 style="margin-bottom: 15px;">Nouveau Deck IA</h3>
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 5px;">Sujet / Titre</div>
                        <div style="background: var(--bg-tertiary); border: 1px solid var(--border); padding: 10px; border-radius: 6px; margin-bottom: 15px; width: 100%; font-size: 0.9rem; color: var(--text);">
                            La Conscience (Philosophie)
                        </div>
                        
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 5px;">Nombre de questions</div>
                        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                            <span style="background: rgba(99,102,241,0.12); color: var(--primary); padding: 5px 15px; border-radius: 20px; font-size: 0.8rem; border: 1px solid var(--primary); cursor: pointer;">10 questions</span>
                            <span style="background: var(--bg-tertiary); color: var(--text-light); padding: 5px 15px; border-radius: 20px; font-size: 0.8rem; cursor: pointer;">20 questions</span>
                        </div>

                        <div id="ai-generate-btn" style="background: var(--primary); color: white; text-align: center; padding: 12px; border-radius: 8px; font-weight: bold; display: flex; justify-content: center; align-items: center; gap: 8px; cursor: pointer; transition: opacity 0.2s;">
                            <span>✨</span> Générer le Quiz
                        </div>
                    </div>

                    <!-- Success View (Hidden initially) -->
                    <div id="ai-success-view" style="display: none; text-align: center; animation: fadeIn 0.5s;">
                        <div style="width: 60px; height: 60px; background: #dcfce7; color: #16a34a; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 15px; animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);">✓</div>
                        <h3 style="margin-bottom: 5px; color: #166534;">Quiz Prêt !</h3>
                        <p style="font-size: 0.9rem; color: var(--text-light); margin-bottom: 20px;">"La Conscience" a été généré avec succès.</p>
                        
                        <div style="background: var(--bg-tertiary); border: 1px solid var(--border); border-radius: 8px; padding: 15px; margin-bottom: 20px; text-align: left;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <span style="font-weight: 600; font-size: 0.9rem;">Q1. Qu'est-ce que le "Cogito" ?</span>
                                <span style="font-size: 0.8rem; color: var(--text-muted);">Difficile</span>
                            </div>
                            <div style="height: 6px; background: #e2e8f0; border-radius: 3px; width: 100%;">
                                <div style="width: 40%; background: var(--primary); height: 100%; border-radius: 3px;"></div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 10px;">
                            <button id="ai-reset-btn" style="flex: 1; padding: 10px; border: 1px solid var(--border); background: var(--card-bg); border-radius: 8px; cursor: pointer; font-weight: 500;">Retour</button>
                            <button style="flex: 1; padding: 10px; background: var(--primary); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.3);">Commencer</button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <!-- Feature 3: Gamification -->
    <section class="feature-section">
        <div class="feature-container">
            <div class="feature-text reveal">
                <h2>Restez motivé avec l'XP.</h2>
                <p>L'apprentissage ne doit pas être ennuyeux. Gagnez des points à chaque bonne réponse et suivez votre série.</p>
                <div style="margin-top: 20px; display: flex; gap: 20px;">
                    <div>
                        <div style="font-size: 2rem; font-weight: 800; color: var(--primary);">+<span id="xp-counter">0</span></div>
                        <div style="font-size: 0.9rem; color: var(--text-light);">XP gagnés cette semaine</div>
                    </div>
                    <div>
                        <div style="font-size: 2rem; font-weight: 800; color: #ef4444;">7</div>
                        <div style="font-size: 0.9rem; color: var(--text-light);">Jours de série</div>
                    </div>
                </div>
            </div>
            <div class="feature-visual reveal">
                <div style="text-align: center; margin-bottom: 1.5rem;">
                    <div style="width: 100px; height: 100px; border-radius: 50%; border: 4px solid var(--primary); display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-size: 2.5rem; background: rgba(99,102,241,0.12); position: relative;">
                        😺
                        <div style="position: absolute; bottom: 0; right: 0; background: var(--primary); color: white; width: 30px; height: 30px; border-radius: 50%; font-size: 1rem; display: flex; align-items: center; justify-content: center; border: 2px solid white;">1</div>
                    </div>
                    <h3>M Roux</h3>
                    <p style="font-size: 0.9rem;">@123456</p>
                </div>
                
                <div class="xp-bar-container">
                    <div class="xp-bar-fill" id="xp-bar"></div>
                </div>
                
                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 20px;">
                    <span>700 XP</span>
                    <span>1000 XP</span>
                </div>
                
                <div style="display: flex; gap: 15px; justify-content: center;">
                    <span style="font-size: 2rem; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1)); transform: scale(1.1);">🏆</span>
                    <span style="font-size: 2rem; opacity: 0.3; filter: grayscale(1);">🥇</span>
                    <span style="font-size: 2rem; opacity: 0.3; filter: grayscale(1);">🧠</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="reveal">
            <h2 style="font-size: 2.5rem; margin-bottom: 1rem;">Prêt à booster vos révisions ?</h2>
            <p style="margin-bottom: 2rem; max-width: 600px; margin-left: auto; margin-right: auto;">Rejoignez des milliers d'étudiants dès aujourd'hui. C'est gratuit, sans publicité et open-source.</p>
            <a href="#" class="btn btn-primary" style="padding: 1rem 2.5rem; font-size: 1.2rem; animation: pulse-glow 3s infinite;">Créer un compte gratuit</a>

           
        </div>
    </footer>

    <script>
        // --- 1. Scroll Reveal Animation ---
        const revealElements = document.querySelectorAll('.reveal');

        const revealOnScroll = () => {
            const windowHeight = window.innerHeight;
            const elementVisible = 100;

            revealElements.forEach((reveal) => {
                const elementTop = reveal.getBoundingClientRect().top;
                if (elementTop < windowHeight - elementVisible) {
                    if (!reveal.classList.contains('active')) {
                        reveal.classList.add('active');
                        
                        // Trigger specific animations inside sections
                        if(reveal.querySelector('#xp-bar')) {
                            setTimeout(() => {
                                document.getElementById('xp-bar').style.width = '70%';
                                animateValue("xp-counter", 0, 700, 2000);
                            }, 500);
                        }
                    }
                }
            });
        };

        window.addEventListener('scroll', revealOnScroll);
        // Trigger once on load
        revealOnScroll();

        // --- 2. Dynamic Text Typing Effect (Typewriter) ---
        const words = ["la philo", "les maths", "la physique", "l'espagnol", "la chimie", "l'EMC", "l'histoire", "l'anglais"];
        let wordIndex = 0;
        let charIndex = 0;
        let isDeleting = false;
        const textElement = document.getElementById('dynamic-text');
        const typeSpeed = 100;
        const deleteSpeed = 50;
        const pauseTime = 2000;

        function typeEffect() {
            const currentWord = words[wordIndex];
            
            if (isDeleting) {
                textElement.textContent = currentWord.substring(0, charIndex - 1);
                charIndex--;
            } else {
                textElement.textContent = currentWord.substring(0, charIndex + 1);
                charIndex++;
            }

            let nextSpeed = isDeleting ? deleteSpeed : typeSpeed;

            if (!isDeleting && charIndex === currentWord.length) {
                isDeleting = true;
                nextSpeed = pauseTime;
            } else if (isDeleting && charIndex === 0) {
                isDeleting = false;
                wordIndex = (wordIndex + 1) % words.length;
                nextSpeed = 500;
            }

            setTimeout(typeEffect, nextSpeed);
        }

        // Start the animation
        charIndex = words[0].length;
        isDeleting = true; // Start by deleting the initial word after a pause
        setTimeout(typeEffect, pauseTime);

        // --- 3. Number Counter Animation ---
        function animateValue(id, start, end, duration) {
            if (start === end) return;
            const range = end - start;
            let current = start;
            const increment = end > start ? 10 : -10; // Count by 10s
            const stepTime = Math.abs(Math.floor(duration / (range / increment)));
            const obj = document.getElementById(id);
            
            const timer = setInterval(function() {
                current += increment;
                obj.innerHTML = current;
                if (current >= end) {
                    obj.innerHTML = end;
                    clearInterval(timer);
                }
            }, stepTime);
        }

        // --- 4. Interactive Quiz Logic ---
        const correctTrigger = document.querySelector('.correct-answer-trigger');
        if(correctTrigger) {
            correctTrigger.addEventListener('click', function() {
                this.classList.add('correct');
                // Reset after 2 seconds for replayability
                setTimeout(() => {
                    this.classList.remove('correct');
                }, 2000);
            });
        }
        // --- 5. AI Generator Simulation ---
        const generateBtn = document.getElementById('ai-generate-btn');
        const resetBtn = document.getElementById('ai-reset-btn');
        const inputView = document.getElementById('ai-input-view');
        const successView = document.getElementById('ai-success-view');
        const generatorCard = document.getElementById('ai-generator-card');

        if(generateBtn) {
            generateBtn.addEventListener('click', function() {
                // Loading state
                const originalContent = this.innerHTML;
                this.innerHTML = '<span class="loader"></span> Génération...';
                this.style.opacity = '0.8';
                this.style.pointerEvents = 'none';

                // Simulate API call
                setTimeout(() => {
                    // Transition
                    inputView.style.display = 'none';
                    successView.style.display = 'block';
                    
                    // Reset button state for next time (if we go back)
                    this.innerHTML = originalContent;
                    this.style.opacity = '1';
                    this.style.pointerEvents = 'auto';
                }, 2000);
            });
        }

        if(resetBtn) {
            resetBtn.addEventListener('click', function() {
                successView.style.display = 'none';
                inputView.style.display = 'block';
            });
        }

        // --- 6. Auth Logic (Merged from index2.php) ---
        // Gestion des onglets
        function switchTab(tab) {
            const tabs = document.querySelectorAll('.auth-tab');
            const forms = document.querySelectorAll('.auth-form');
            
            tabs.forEach(t => t.classList.remove('active'));
            forms.forEach(f => f.classList.remove('active'));
            
            if (tab === 'login') {
                tabs[0].classList.add('active');
                document.getElementById('login-form').classList.add('active');
            } else {
                tabs[1].classList.add('active');
                document.getElementById('register-form').classList.add('active');
            }
        }

        // Gestion des inputs de code
        const codeDigits = document.querySelectorAll('.code-digit');
        const loginCodeInput = document.getElementById('login-code');

        if (codeDigits.length > 0) {
            codeDigits.forEach((digit, index) => {
                // Focus auto sur le suivant
                digit.addEventListener('input', (e) => {
                    const value = e.target.value;
                    
                    // Ne garder que les chiffres
                    e.target.value = value.replace(/[^0-9]/g, '');
                    
                    if (e.target.value && index < codeDigits.length - 1) {
                        codeDigits[index + 1].focus();
                    }
                    
                    // Mettre à jour le style
                    e.target.classList.toggle('filled', e.target.value !== '');
                    
                    // Mettre à jour le code caché
                    updateHiddenCode();

                    // Connexion automatique dès que la dernière case est remplie
                    if (index === codeDigits.length - 1 && e.target.value) {
                        document.getElementById('login-form').submit();
                    }
                });

                // Gestion du retour arrière
                digit.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && !e.target.value && index > 0) {
                        codeDigits[index - 1].focus();
                    }
                });

                // Coller un code complet
                digit.addEventListener('paste', (e) => {
                    e.preventDefault();
                    const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '');
                    
                    for (let i = 0; i < 6 && i < pastedData.length; i++) {
                        codeDigits[i].value = pastedData[i];
                        codeDigits[i].classList.add('filled');
                    }
                    
                    updateHiddenCode();
                    
                    // Focus sur le dernier champ rempli ou le suivant
                    const focusIndex = Math.min(pastedData.length, 5);
                    codeDigits[focusIndex].focus();

                    // Connexion automatique si les 6 cases sont remplies
                    if (pastedData.length >= 6) {
                        document.getElementById('login-form').submit();
                    }
                });

                // Sélectionner tout au focus
                digit.addEventListener('focus', () => {
                    digit.select();
                });
            });
        }

        function updateHiddenCode() {
            let code = '';
            codeDigits.forEach(d => code += d.value);
            if(loginCodeInput) loginCodeInput.value = code;
        }

        // --- Theme Toggle ---
        function applyThemeIndex(theme) {
            if (theme === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
            else document.documentElement.removeAttribute('data-theme');
            localStorage.setItem('theme', theme);
            const icon = document.getElementById('theme-icon-index');
            if (icon) icon.textContent = theme === 'dark' ? '☀️' : '🌘';
        }

        function toggleThemeIndex() {
            const current = localStorage.getItem('theme') || 'light';
            const btn = document.getElementById('theme-toggle-index');
            if (btn) {
                btn.classList.add('theme-switching-index');
                btn.addEventListener('animationend', function handler() {
                    btn.classList.remove('theme-switching-index');
                    btn.removeEventListener('animationend', handler);
                });
            }
            applyThemeIndex(current === 'dark' ? 'light' : 'dark');
        }

        // Apply saved theme on load
        (function(){
            const saved = localStorage.getItem('theme') || 'light';
            if (saved === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
            document.addEventListener('DOMContentLoaded', function() {
                applyThemeIndex(saved);
            });
        })();
    </script>
</body>
</html>
