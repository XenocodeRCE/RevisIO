<?php
session_start();
header('Content-Type: application/json');

// Read JSON input once
$jsonInput = json_decode(file_get_contents('php://input'), true) ?? [];

// Get User Code from Session OR Request
$userCode = null;

if (isset($_SESSION['user_code'])) {
    $userCode = $_SESSION['user_code'];
} elseif (isset($_GET['user'])) {
    $userCode = $_GET['user'];
} elseif (isset($jsonInput['userCode'])) {
    $userCode = $jsonInput['userCode'];
}

if (!$userCode) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$decksFile = __DIR__ . '/../data/decks.json';
$userFile = __DIR__ . '/../data/' . $userCode . '.json';

// Ensure data directory exists
if (!is_dir(__DIR__ . '/../data')) {
    mkdir(__DIR__ . '/../data', 0777, true);
}
 
// Ensure decks file exists
if (!file_exists($decksFile)) {
    file_put_contents($decksFile, json_encode([], JSON_PRETTY_PRINT));
}

// Ensure user file exists
if (!file_exists($userFile)) {
    $defaultUser = [
        'stats' => [
            'points' => 0, 
            'streak' => 0,
            'cardsLearned' => 0,
            'timeSpent' => 0
        ],
        'progress' => []
    ];
    file_put_contents($userFile, json_encode($defaultUser, JSON_PRETTY_PRINT));
}

$action = $_GET['action'] ?? '';

if ($action === 'load') {
    $decks = json_decode(file_get_contents($decksFile), true) ?? [];
    $userData = json_decode(file_get_contents($userFile), true) ?? [];
    
    // Merge progress into decks for the frontend
    $userProgress = $userData['progress'] ?? [];
    foreach ($decks as &$deck) {
        $deck['progress'] = $userProgress[$deck['id']] ?? 0;
        // Only expose deck stats to the deck author
        if (isset($deck['stats']) && (!isset($deck['author']) || $deck['author'] !== $userCode)) {
            unset($deck['stats']);
        }
    }
    
    echo json_encode([
        'decks' => $decks, 
        'stats' => $userData['stats'] ?? [
            'points' => 0, 
            'streak' => 0,
            'cardsLearned' => 0,
            'timeSpent' => 0
        ],
        'favorites' => $userData['favorites'] ?? [],
        'history' => $userData['history'] ?? []
    ]);
} 
elseif ($action === 'add_deck') {
    if (isset($jsonInput['deck'])) {
        $decks = json_decode(file_get_contents($decksFile), true) ?? [];
        array_unshift($decks, $jsonInput['deck']); // Add new deck to the top
        file_put_contents($decksFile, json_encode($decks, JSON_PRETTY_PRINT));
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No deck data']);
    }
} 
elseif ($action === 'delete_deck') {
    if (isset($jsonInput['deckId'])) {
        $deckId = $jsonInput['deckId'];
        $decks = json_decode(file_get_contents($decksFile), true) ?? [];
        
        $newDecks = [];
        $deleted = false;
        
        foreach ($decks as $deck) {
            // Check if deck matches ID AND if current user is the author
            if ($deck['id'] == $deckId) {
                if (isset($deck['author']) && $deck['author'] === $userCode) {
                    $deleted = true;
                    continue; // Skip adding this deck to new array (delete it)
                } else {
                    // Found deck but user is not author
                    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
                    exit;
                }
            }
            $newDecks[] = $deck;
        }
        
        if ($deleted) {
            file_put_contents($decksFile, json_encode($newDecks, JSON_PRETTY_PRINT));
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Deck not found']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Missing deck ID']);
    }
}
elseif ($action === 'save_progress') {
    // Accept stats updates, progress, favorites and session; update both user file and deck stats when relevant
    if (isset($jsonInput['stats'])) {
        $userData = json_decode(file_get_contents($userFile), true) ?? [];
        $userData['stats'] = $jsonInput['stats'];
        
        // Merge new progress with existing progress
        if (isset($jsonInput['progress'])) {
            $currentProgress = $userData['progress'] ?? [];
            foreach ($jsonInput['progress'] as $deckId => $prog) {
                $currentProgress[$deckId] = $prog;
            }
            $userData['progress'] = $currentProgress;
        }

        // Save favorites
        if (isset($jsonInput['favorites'])) {
            $userData['favorites'] = $jsonInput['favorites'];
        }

        // Save session history
        if (isset($jsonInput['session'])) {
            if (!isset($userData['history'])) {
                $userData['history'] = [];
            }
            $userData['history'][] = $jsonInput['session'];
        }
        
        // Persist user data
        file_put_contents($userFile, json_encode($userData, JSON_PRETTY_PRINT));

        // Update deck-level stats when session includes deckId and score
        if (isset($jsonInput['session']) && isset($jsonInput['session']['deckId'])) {
            $session = $jsonInput['session'];
            $deckId = $session['deckId'];
            $score = isset($session['score']) ? (int)$session['score'] : null;
            $total = isset($session['total']) ? (int)$session['total'] : null;

            $decks = json_decode(file_get_contents($decksFile), true) ?? [];
            $updated = false;
            foreach ($decks as &$deck) {
                if ($deck['id'] == $deckId) {
                    if (!isset($deck['stats'])) {
                        $deck['stats'] = [
                            'totalPlays' => 0,
                            'totalScore' => 0,
                            'bestScore' => 0,
                            'averageScore' => 0,
                            'lastPlayed' => null,
                            'playsByUser' => []
                        ];
                    }
                    $deck['stats']['totalPlays'] = ($deck['stats']['totalPlays'] ?? 0) + 1;
                    if ($score !== null) {
                        $deck['stats']['totalScore'] = ($deck['stats']['totalScore'] ?? 0) + $score;
                    }
                    $deck['stats']['averageScore'] = round(($deck['stats']['totalScore'] ?? 0) / $deck['stats']['totalPlays'], 2);
                    if ($score !== null && $score > ($deck['stats']['bestScore'] ?? 0)) {
                        $deck['stats']['bestScore'] = $score;
                    }
                    $deck['stats']['lastPlayed'] = date('c');
                    $deck['stats']['playsByUser'][$userCode] = (($deck['stats']['playsByUser'][$userCode] ?? 0) + 1);
                    $updated = true;
                    break;
                }
            }
            if ($updated) {
                file_put_contents($decksFile, json_encode($decks, JSON_PRETTY_PRINT));
            }
        }

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    }
} 
else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
?>