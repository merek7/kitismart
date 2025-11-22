<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Utils\Csrf;
use App\Utils\Mailer;

/**
 * EmailTestController - Page de test pour les emails
 *
 * ATTENTION: Cette page doit être désactivée en production !
 * Utilisez uniquement en développement pour tester les emails.
 */
class EmailTestController extends Controller
{
    public function __construct()
    {
        // Vérifier qu'on est en mode développement
        if (($_ENV['APP_ENV'] ?? 'prod') !== 'dev') {
            header('HTTP/1.0 403 Forbidden');
            exit('Cette page n\'est accessible qu\'en mode développement.');
        }
    }

    public function index()
    {
        $csrfToken = Csrf::generateToken();

        $this->view('admin/email-test', [
            'title' => 'Test des Emails - KitiSmart',
            'csrfToken' => $csrfToken,
            'emailTypes' => $this->getEmailTypes(),
            'currentPage' => 'email-test',
            'style' => 'dashboard/notification_settings.css'
        ], 'dashboard');
    }

    /**
     * Envoyer un email de test
     */
    public function sendTest()
    {
        try {
            if (!$this->isPostRequest()) {
                return $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (!Csrf::verifyToken($data['csrf_token'] ?? '')) {
                return $this->jsonResponse(['success' => false, 'message' => 'Token CSRF invalide'], 403);
            }

            $emailType = $data['email_type'] ?? '';
            $recipientEmail = filter_var($data['recipient_email'] ?? '', FILTER_VALIDATE_EMAIL);

            if (!$recipientEmail) {
                return $this->jsonResponse(['success' => false, 'message' => 'Email destinataire invalide'], 400);
            }

            $mailer = new Mailer();
            $result = false;

            switch ($emailType) {
                case 'confirmation':
                    $result = $mailer->sendConfirmationEmail(
                        $recipientEmail,
                        'Utilisateur Test',
                        'test-token-' . bin2hex(random_bytes(16))
                    );
                    break;

                case 'password_reset':
                    $result = $mailer->sendPasswordResetEmail(
                        $recipientEmail,
                        'Utilisateur Test',
                        'reset-token-' . bin2hex(random_bytes(16))
                    );
                    break;

                case 'budget_alert':
                    $result = $mailer->sendBudgetAlertEmail(
                        $recipientEmail,
                        'Utilisateur Test',
                        [
                            'percentage' => 85,
                            'is_over_budget' => false,
                            'budget_initial' => 500000,
                            'budget_spent' => 425000,
                            'budget_remaining' => 75000
                        ]
                    );
                    break;

                case 'budget_exceeded':
                    $result = $mailer->sendBudgetAlertEmail(
                        $recipientEmail,
                        'Utilisateur Test',
                        [
                            'percentage' => 115,
                            'is_over_budget' => true,
                            'budget_initial' => 500000,
                            'budget_spent' => 575000,
                            'budget_remaining' => -75000
                        ]
                    );
                    break;

                case 'expense_alert':
                    $result = $mailer->sendExpenseAlertEmail(
                        $recipientEmail,
                        'Utilisateur Test',
                        [
                            'expense_amount' => 150000,
                            'expense_description' => 'Achat équipement informatique',
                            'expense_date' => date('Y-m-d'),
                            'expense_category' => 'Équipement',
                            'threshold' => 100000,
                            'budget_remaining' => 250000
                        ]
                    );
                    break;

                case 'monthly_summary':
                    $result = $mailer->sendMonthlySummaryEmail(
                        $recipientEmail,
                        'Utilisateur Test',
                        [
                            'period' => date('F Y'),
                            'budget_initial' => 500000,
                            'budget_remaining' => 125000,
                            'total_spent' => 375000,
                            'expense_count' => 42,
                            'usage_percentage' => 75,
                            'categories' => [
                                ['name' => 'Alimentation', 'total' => 150000],
                                ['name' => 'Transport', 'total' => 85000],
                                ['name' => 'Loisirs', 'total' => 65000],
                                ['name' => 'Santé', 'total' => 45000],
                                ['name' => 'Autres', 'total' => 30000]
                            ],
                            'top_expenses' => [
                                ['description' => 'Courses mensuelles', 'amount' => 75000, 'date' => date('Y-m-d', strtotime('-5 days'))],
                                ['description' => 'Essence voiture', 'amount' => 45000, 'date' => date('Y-m-d', strtotime('-10 days'))],
                                ['description' => 'Restaurant famille', 'amount' => 35000, 'date' => date('Y-m-d', strtotime('-15 days'))],
                                ['description' => 'Pharmacie', 'amount' => 25000, 'date' => date('Y-m-d', strtotime('-20 days'))],
                                ['description' => 'Cinéma', 'amount' => 15000, 'date' => date('Y-m-d', strtotime('-25 days'))]
                            ],
                            'insights' => [
                                'Vous avez bien géré votre budget ce mois-ci !',
                                'Votre catégorie la plus dépensière est "Alimentation".',
                                'Conseil : Essayez de réduire les dépenses de loisirs le mois prochain.'
                            ]
                        ]
                    );
                    break;

                default:
                    return $this->jsonResponse(['success' => false, 'message' => 'Type d\'email inconnu'], 400);
            }

            if ($result) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => "Email de test ({$emailType}) envoyé avec succès à {$recipientEmail}"
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Échec de l\'envoi de l\'email. Vérifiez la configuration SMTP.'
                ], 500);
            }

        } catch (\Exception $e) {
            error_log("Erreur test email: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Prévisualiser un email sans l'envoyer
     */
    public function preview()
    {
        $emailType = $_GET['type'] ?? '';

        $templateData = $this->getPreviewData($emailType);

        if (!$templateData) {
            echo '<h1>Type d\'email non trouvé</h1>';
            return;
        }

        extract($templateData);

        $templatePath = dirname(__DIR__) . "/views/emails/{$templateData['template']}.php";

        if (file_exists($templatePath)) {
            require $templatePath;
        } else {
            echo $this->getInlineEmailPreview($emailType, $templateData);
        }
    }

    /**
     * Obtenir les types d'emails disponibles
     */
    private function getEmailTypes(): array
    {
        return [
            'confirmation' => [
                'name' => 'Email de confirmation d\'inscription',
                'description' => 'Envoyé lors de la création d\'un nouveau compte',
                'icon' => '✉️'
            ],
            'password_reset' => [
                'name' => 'Email de réinitialisation de mot de passe',
                'description' => 'Envoyé quand un utilisateur demande à réinitialiser son mot de passe',
                'icon' => '🔑'
            ],
            'budget_alert' => [
                'name' => 'Alerte budget (80%)',
                'description' => 'Envoyé quand le budget atteint 80% d\'utilisation',
                'icon' => '⚠️'
            ],
            'budget_exceeded' => [
                'name' => 'Alerte budget dépassé (100%+)',
                'description' => 'Envoyé quand le budget est dépassé',
                'icon' => '🚨'
            ],
            'expense_alert' => [
                'name' => 'Alerte dépense importante',
                'description' => 'Envoyé quand une dépense dépasse le seuil configuré',
                'icon' => '💳'
            ],
            'monthly_summary' => [
                'name' => 'Récapitulatif mensuel',
                'description' => 'Envoyé en fin de mois avec le récapitulatif des dépenses',
                'icon' => '📊'
            ]
        ];
    }

    /**
     * Obtenir les données de prévisualisation
     */
    private function getPreviewData(string $emailType): ?array
    {
        $baseData = [
            'user_name' => 'Jean Dupont',
            'name' => 'Jean Dupont'
        ];

        switch ($emailType) {
            case 'confirmation':
                return array_merge($baseData, [
                    'template' => 'confirmation',
                    'confirmationLink' => $_ENV['APP_URL'] . '/confirmation/test-token-preview'
                ]);

            case 'password_reset':
                return array_merge($baseData, [
                    'template' => 'reset-password',
                    'resetLink' => $_ENV['APP_URL'] . '/reset-password/test-token-preview'
                ]);

            case 'budget_alert':
                return array_merge($baseData, [
                    'template' => 'budget_alert',
                    'percentage' => 85,
                    'is_over_budget' => false,
                    'budget_initial' => '500 000',
                    'budget_spent' => '425 000',
                    'budget_remaining' => '75 000'
                ]);

            case 'budget_exceeded':
                return array_merge($baseData, [
                    'template' => 'budget_alert',
                    'percentage' => 115,
                    'is_over_budget' => true,
                    'budget_initial' => '500 000',
                    'budget_spent' => '575 000',
                    'budget_remaining' => '-75 000'
                ]);

            case 'expense_alert':
                return array_merge($baseData, [
                    'template' => 'expense_alert',
                    'expense_amount' => 150000,
                    'expense_description' => 'Achat équipement informatique',
                    'expense_date' => date('Y-m-d'),
                    'expense_category' => 'Équipement',
                    'threshold' => 100000,
                    'budget_remaining' => 250000
                ]);

            case 'monthly_summary':
                return array_merge($baseData, [
                    'template' => 'monthly_summary',
                    'period' => date('F Y'),
                    'budget_initial' => 500000,
                    'budget_remaining' => 125000,
                    'total_spent' => 375000,
                    'expense_count' => 42,
                    'usage_percentage' => 75,
                    'categories' => [
                        ['name' => 'Alimentation', 'total' => 150000],
                        ['name' => 'Transport', 'total' => 85000],
                        ['name' => 'Loisirs', 'total' => 65000],
                        ['name' => 'Santé', 'total' => 45000],
                        ['name' => 'Autres', 'total' => 30000]
                    ],
                    'top_expenses' => [
                        ['description' => 'Courses mensuelles', 'amount' => 75000, 'date' => date('Y-m-d', strtotime('-5 days'))],
                        ['description' => 'Essence voiture', 'amount' => 45000, 'date' => date('Y-m-d', strtotime('-10 days'))],
                        ['description' => 'Restaurant famille', 'amount' => 35000, 'date' => date('Y-m-d', strtotime('-15 days'))]
                    ],
                    'insights' => [
                        'Vous avez bien géré votre budget ce mois-ci !',
                        'Votre catégorie la plus dépensière est "Alimentation".'
                    ]
                ]);

            default:
                return null;
        }
    }

    /**
     * Générer une prévisualisation inline pour les emails sans template
     */
    private function getInlineEmailPreview(string $emailType, array $data): string
    {
        if ($emailType === 'confirmation') {
            return "
                <h2>Bonjour {$data['name']},</h2>
                <p>Merci de vous être inscrit sur KitiSmart. Pour activer votre compte, veuillez cliquer sur le lien ci-dessous :</p>
                <p><a href='{$data['confirmationLink']}'>Confirmer mon compte</a></p>
                <p>Ce lien est valable pendant 20 minutes.</p>
                <p>Si vous n'avez pas créé de compte, ignorez cet email.</p>
            ";
        }

        if ($emailType === 'password_reset') {
            return "
                <h2>Bonjour {$data['name']},</h2>
                <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
                <p>Cliquez sur le lien ci-dessous pour définir un nouveau mot de passe :</p>
                <p><a href='{$data['resetLink']}'>{$data['resetLink']}</a></p>
                <p>Ce lien expirera dans 1 heure.</p>
            ";
        }

        return '<h1>Prévisualisation non disponible pour ce type d\'email</h1>';
    }
}
