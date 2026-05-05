<?php
/**
 * API Proxy
 * La modération est automatiquement appliquée avant chaque requête IA.
 */

// ============================================================================
// CONFIGURATION
// ============================================================================

const API_KEY = "sk-XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX"; // Remplacez par votre clé API

const MODEL              = 'gpt-5-mini'; // prix : 0.0001$ / 1000 tokens (+/- 7500 mots)
const MAX_OUTPUT_TOKENS  = 128000;
const REASONING_EFFORT   = 'medium';
const RESPONSES_ENDPOINT = 'https://api.openai.com/v1/responses';
const MODERATION_ENDPOINT = 'https://api.openai.com/v1/moderations';

// ============================================================================
// HEADERS CORS
// ============================================================================

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ============================================================================
// FONCTIONS UTILITAIRES
// ============================================================================

function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError(string $message, int $statusCode = 400): void
{
    jsonResponse(['error' => $message], $statusCode);
}

function sanitizeWafTriggers(?string $text): string
{
    if ($text === null || $text === '') {
        return '';
    }

    $patterns = [
        '-->', '<!--', '-- ', 'UNION SELECT', 'DROP TABLE',
        '<script', 'javascript:', '../', 'etc/passwd', 'system(', 'exec('
    ];

    $replacements = [
        '→', '←', '— ', 'UNION_SELECT', 'DROP_TABLE',
        '‹script', 'javascript_:', './', 'etc_passwd', 'system_(', 'exec_('
    ];

    return str_replace($patterns, $replacements, $text);
}

function curlRequest(string $url, array $data): array
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . API_KEY,
        ],
        CURLOPT_TIMEOUT => 1200,
    ]);

    $response = curl_exec($ch);
    $error    = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error) {
        return ['success' => false, 'error' => 'Erreur cURL : ' . $error];
    }

    $decoded = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => 'Erreur décodage JSON', 'raw_response' => $response];
    }

    return ['success' => true, 'data' => $decoded, 'http_code' => $httpCode];
}

// ============================================================================
// MODÉRATION
// Retourne null si le contenu est acceptable, ou un tableau de catégories
// problématiques si le contenu est signalé.
// ============================================================================

function moderateContent(string $text): ?array
{
    if (empty($text)) {
        return null;
    }

    $result = curlRequest(MODERATION_ENDPOINT, [
        'model' => 'omni-moderation-latest',
        'input' => $text
    ]);

    if (!$result['success']) {
        // En cas d'erreur de l'API modération, on bloque par sécurité
        return ['moderation_api_error'];
    }

    $moderationResult = $result['data']['results'][0] ?? null;

    if ($moderationResult && $moderationResult['flagged']) {
        $flaggedCategories = [];
        foreach ($moderationResult['categories'] as $category => $isFlagged) {
            if ($isFlagged) {
                $flaggedCategories[] = $category;
            }
        }
        return $flaggedCategories;
    }

    return null;
}

// ============================================================================
// HANDLER PRINCIPAL
// ============================================================================

function handleAskAI(): void
{
    $system = sanitizeWafTriggers($_POST['system'] ?? '');
    $prompt = sanitizeWafTriggers($_POST['prompt'] ?? '');

    if (empty($prompt)) {
        jsonError('Le champ prompt est requis');
    }

    // Modération du contenu soumis par l'élève
    $flagged = moderateContent($prompt);
    if ($flagged !== null) {
        jsonResponse([
            'error'      => 'Contenu inapproprié détecté. La création du QCM a été bloquée.',
            'flagged'    => true,
            'categories' => $flagged
        ], 403);
    }

    // Construction de la requête vers gpt-5-mini
    $input = [];

    if (!empty($system)) {
        $input[] = ['role' => 'developer', 'content' => $system];
    }

    $input[] = ['role' => 'user', 'content' => $prompt];

    $requestData = [
        'model'  => MODEL,
        'input'  => $input,
        'text'   => ['format' => ['type' => 'text'], 'verbosity' => 'medium'],
        'reasoning' => ['effort' => REASONING_EFFORT],
        'include' => ['reasoning.encrypted_content'],
        'tools'  => [],
        'store'  => true
    ];

    $result = curlRequest(RESPONSES_ENDPOINT, $requestData);

    if (!$result['success']) {
        jsonError($result['error'], 500);
    }

    jsonResponse([
        'response' => $result['data'],
        'model'    => MODEL
    ]);
}

// ============================================================================
// ROUTEUR PRINCIPAL
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode non autorisée. Utilisez POST.', 405);
}

handleAskAI();