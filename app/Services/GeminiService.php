<?php

namespace App\Services;

use RedBeanPHP\R;

class GeminiService
{
    private string $apiKey;
    private string $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent';
    
    // Limites par utilisateur
    const MAX_REQUESTS_PER_DAY = 5;
    const COOLDOWN_SECONDS = 30;

    public function __construct()
    {
        $this->apiKey = $_ENV['GEMINI_API_KEY'] ?? '';
    }

    /**
     * Vérifier si l'utilisateur peut faire une requête
     */
    public function canUserMakeRequest(int $userId): array
    {
        $usage = $this->getUserUsage($userId);
        $today = date('Y-m-d');
        
        // Reset si nouveau jour
        if ($usage['last_date'] !== $today) {
            $this->resetDailyUsage($userId);
            $usage['requests_today'] = 0;
        }
        
        // Vérifier limite quotidienne
        if ($usage['requests_today'] >= self::MAX_REQUESTS_PER_DAY) {
            return [
                'allowed' => false,
                'reason' => 'Limite quotidienne atteinte (' . self::MAX_REQUESTS_PER_DAY . ' requêtes/jour)',
                'remaining' => 0,
                'reset_at' => 'demain'
            ];
        }
        
        // Vérifier cooldown
        $lastRequest = strtotime($usage['last_request_at'] ?? '2000-01-01');
        $cooldownRemaining = self::COOLDOWN_SECONDS - (time() - $lastRequest);
        
        if ($cooldownRemaining > 0) {
            return [
                'allowed' => false,
                'reason' => 'Veuillez patienter ' . $cooldownRemaining . ' secondes',
                'remaining' => self::MAX_REQUESTS_PER_DAY - $usage['requests_today'],
                'cooldown' => $cooldownRemaining
            ];
        }
        
        return [
            'allowed' => true,
            'remaining' => self::MAX_REQUESTS_PER_DAY - $usage['requests_today']
        ];
    }

    /**
     * Générer des conseils financiers personnalisés
     */
    public function getFinancialAdvice(int $userId, array $financialData): array
    {
        // Vérifier les limites
        $canRequest = $this->canUserMakeRequest($userId);
        if (!$canRequest['allowed']) {
            return [
                'success' => false,
                'error' => $canRequest['reason'],
                'remaining' => $canRequest['remaining'] ?? 0
            ];
        }

        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'error' => 'Clé API Gemini non configurée'
            ];
        }

        // Construire le prompt
        $prompt = $this->buildFinancialPrompt($financialData);
        
        try {
            $response = $this->callGeminiAPI($prompt);
            
            // Enregistrer l'utilisation
            $this->recordUsage($userId);
            
            return [
                'success' => true,
                'advice' => $response,
                'remaining' => self::MAX_REQUESTS_PER_DAY - $this->getUserUsage($userId)['requests_today']
            ];
        } catch (\Exception $e) {
            error_log("Gemini API Error: " . $e->getMessage());
            error_log("Gemini API Stack: " . $e->getTraceAsString());
            return [
                'success' => false,
                'error' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Construire le prompt financier
     */
    private function buildFinancialPrompt(array $data): string
    {
        $monthlyIncome = number_format($data['monthly_income'] ?? 0, 0, ',', ' ');
        $fixedExpenses = number_format($data['fixed_expenses'] ?? 0, 0, ',', ' ');
        $available = number_format($data['available'] ?? 0, 0, ',', ' ');
        $savingsGoal = $data['savings_goal'] ?? '';
        $targetAmount = number_format($data['target_amount'] ?? 0, 0, ',', ' ');
        $promptType = $data['prompt_type'] ?? 'general';
        $customQuestion = $data['custom_question'] ?? '';
        
        $expensesList = '';
        if (!empty($data['expenses_details'])) {
            foreach ($data['expenses_details'] as $expense) {
                $expensesList .= "- {$expense['description']}: " . number_format($expense['amount'], 0, ',', ' ') . " FCFA\n";
            }
        }

        // Contexte financier de base
        $context = "SITUATION FINANCIÈRE DE L'UTILISATEUR:
- Revenu mensuel: {$monthlyIncome} FCFA
- Charges fixes mensuelles: {$fixedExpenses} FCFA
- Disponible après charges: {$available} FCFA

DÉTAIL DES CHARGES:
{$expensesList}";

        // Question personnalisée
        if ($promptType === 'custom' && !empty($customQuestion)) {
            return "Tu es Kiti Coach, un conseiller financier expert pour les utilisateurs africains. Tu réponds UNIQUEMENT aux questions liées aux finances personnelles (budget, épargne, dépenses, investissement, objectifs financiers).

{$context}

QUESTION DE L'UTILISATEUR: {$customQuestion}

RÈGLES STRICTES:
1. Si la question n'est PAS liée aux finances personnelles, réponds poliment: \"Je suis votre coach financier, je ne peux répondre qu'aux questions sur vos finances (budget, épargne, dépenses...). Reformulez votre question !\"
2. Si la question EST financière, donne une réponse personnalisée basée sur la situation ci-dessus
3. Sois concis (200 mots max), pratique et encourageant
4. Utilise des emojis pour rendre la lecture agréable
5. Réponds en français";
        }

        // Prompts par type
        $instructions = match($promptType) {
            'savings' => "CONSIGNES:
1. Concentre-toi sur les stratégies d'épargne
2. Propose 3 méthodes d'épargne adaptées à ce profil
3. Suggère un montant d'épargne réaliste (% du disponible)
4. Mentionne les erreurs à éviter",
            'optimize' => "CONSIGNES:
1. Analyse les charges fixes et identifie les économies possibles
2. Propose 3 astuces concrètes pour réduire les dépenses
3. Estime le montant qu'on peut économiser
4. Priorise par impact (plus gros gains en premier)",
            'goal' => "OBJECTIF D'ÉPARGNE: {$savingsGoal}
MONTANT CIBLE: {$targetAmount} FCFA

CONSIGNES:
1. Évalue si l'objectif est réaliste avec ce budget
2. Calcule le temps nécessaire pour l'atteindre
3. Propose un plan d'action en 3 étapes
4. Suggère des alternatives si l'objectif est trop ambitieux",
            default => "CONSIGNES:
1. Analyse la situation en 2-3 phrases
2. Donne 3 conseils concrets et actionnables
3. Suggère un montant d'épargne mensuel réaliste
4. Identifie les points forts et les axes d'amélioration"
        };

        return "Tu es Kiti Coach, un conseiller financier expert pour les utilisateurs africains. Donne des conseils personnalisés, pratiques et réalistes en français.

{$context}

{$instructions}

STYLE:
- Reste encourageant et positif
- Utilise des emojis pour rendre la lecture agréable
- Limite ta réponse à 250 mots maximum";
    }

    /**
     * Appeler l'API Gemini
     */
    private function callGeminiAPI(string $prompt): string
    {
        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 500
            ]
        ];

        $url = $this->apiUrl . '?key=' . $this->apiKey;
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        error_log("Gemini API URL: " . $this->apiUrl);
        error_log("Gemini API Key present: " . (!empty($this->apiKey) ? 'Yes' : 'No'));
        error_log("Gemini API HTTP Code: " . $httpCode);
        
        if ($curlError) {
            error_log("Gemini cURL Error: " . $curlError);
            throw new \Exception("cURL error: " . $curlError);
        }

        if ($httpCode !== 200) {
            error_log("Gemini API Response: " . $response);
            throw new \Exception("API returned HTTP {$httpCode}: " . substr($response, 0, 200));
        }

        $result = json_decode($response, true);
        
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return $result['candidates'][0]['content']['parts'][0]['text'];
        }

        error_log("Gemini API Invalid Response: " . $response);
        throw new \Exception("Invalid API response format");
    }

    /**
     * Récupérer l'utilisation d'un utilisateur
     */
    private function getUserUsage(int $userId): array
    {
        $usage = R::findOne('useraiusage', 'user_id = ?', [$userId]);
        
        if (!$usage) {
            return [
                'requests_today' => 0,
                'last_date' => date('Y-m-d'),
                'last_request_at' => null
            ];
        }

        return [
            'requests_today' => (int)$usage->requests_today,
            'last_date' => $usage->last_date,
            'last_request_at' => $usage->last_request_at
        ];
    }

    /**
     * Enregistrer une utilisation
     */
    private function recordUsage(int $userId): void
    {
        $usage = R::findOne('useraiusage', 'user_id = ?', [$userId]);
        
        if (!$usage) {
            $usage = R::dispense('useraiusage');
            $usage->user_id = $userId;
            $usage->requests_today = 0;
            $usage->total_requests = 0;
        }

        $today = date('Y-m-d');
        if ($usage->last_date !== $today) {
            $usage->requests_today = 0;
            $usage->last_date = $today;
        }

        $usage->requests_today++;
        $usage->total_requests++;
        $usage->last_request_at = date('Y-m-d H:i:s');
        
        R::store($usage);
    }

    /**
     * Reset l'utilisation quotidienne
     */
    private function resetDailyUsage(int $userId): void
    {
        $usage = R::findOne('useraiusage', 'user_id = ?', [$userId]);
        
        if ($usage) {
            $usage->requests_today = 0;
            $usage->last_date = date('Y-m-d');
            R::store($usage);
        }
    }
}
