// ===== STATE =====
let decks = [];
let favorites = [];
let stats = { points: 0, streak: 0, cardsLearned: 0, timeSpent: 0 };
let currentDeck = null;
let currentCardIndex = 0;
let currentSessionScore = 0;
let currentFilter = 'all';
let searchQuery = '';
let pendingCreationType = null; // 'qcm' or 'flashcards'
let currentStudyMode = 'training';
let incorrectItems = [];
let studyTimer = null;
let activeDeckCards = []; // Cards used in current session (shuffled or not)
let sessionStartTime = 0;
let manualCards = []; // Cards being created manually
let manualCreationType = null; // 'qcm' or 'flashcards'
let selectedCreationType = null;

// Variables PHP définies globalement dans app.php avant le chargement de ce script :
//   userCode, userName

const API_URL     = 'api/openai.php';
const API_BACKEND = 'api/api.php';
