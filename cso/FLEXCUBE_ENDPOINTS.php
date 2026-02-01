<?php
/**
 * Fichier de Référence: Points Terminaux Flexcube
 * 
 * Contient les endpoints et configurations Flexcube
 */

// ============================================
// ENDPOINTS FLEXCUBE
// ============================================

return [
    
    // Environnements
    'environments' => [
        'development' => [
            'name' => 'Développement/Test',
            'url' => 'https://devtuat.ecobank.com/accountenquiryserviceaffiliate/accountenquiry/ecomobileflex/getaccountinfo',
            'verify_ssl' => false,
            'test_account' => '37220020391'
        ],
        'staging' => [
            'name' => 'Staging/Recette',
            'url' => 'https://stgtuat.ecobank.com/accountenquiryserviceaffiliate/accountenquiry/ecomobileflex/getaccountinfo',
            'verify_ssl' => true,
            'test_account' => '[À OBTENIR]'
        ],
        'production' => [
            'name' => 'Production',
            'url' => 'https://api.ecobank.com/accountenquiryserviceaffiliate/accountenquiry/ecomobileflex/getaccountinfo',
            'verify_ssl' => true,
            'test_account' => '[À OBTENIR]'
        ]
    ],
    
    // Service Endpoints
    'services' => [
        'account_enquiry' => [
            'name' => 'Account Enquiry Service',
            'path' => '/accountenquiryserviceaffiliate/accountenquiry/ecomobileflex/getaccountinfo',
            'method' => 'POST',
            'content_type' => 'application/xml',
            'description' => 'Récupère les informations détaillées d\'un compte'
        ]
    ],
    
    // Headers requis
    'headers' => [
        'Content-Type' => 'application/xml',
        'Accept' => 'application/xml',
        'User-Agent' => 'Ecobank-Corporate-Portal/1.0'
    ],
    
    // Paramètres d'authentification
    'auth' => [
        'source_code' => 'ECOBANKWEB',
        'request_type' => 'GETACCINFO',
        'affiliate_code' => 'ECG',
        'source_channel_id' => 'WEB',
        'version' => '1.0'
    ],
    
    // Formats de réponse
    'response_fields' => [
        'accountNo' => 'Numéro de compte',
        'accountName' => 'Nom du compte',
        'accountType' => 'Type de compte (SAVINGS, CURRENT, etc)',
        'currency' => 'Code devise (XAF, USD, etc)',
        'status' => 'Statut du compte (ACTIVE, INACTIVE, BLOCKED)',
        'balance' => 'Solde actuel',
        'customerId' => 'ID Client',
        'branchCode' => 'Code agence',
        'openingDate' => 'Date d\'ouverture',
        'lastTransactionDate' => 'Date dernière transaction',
        'accountOfficer' => 'Responsable compte'
    ],
    
    // Codes de statut possibles
    'account_statuses' => [
        'ACTIVE' => 'Compte actif',
        'INACTIVE' => 'Compte inactif',
        'BLOCKED' => 'Compte bloqué',
        'CLOSED' => 'Compte fermé',
        'DORMANT' => 'Compte inactif',
        'SUSPENDED' => 'Compte suspendu'
    ],
    
    // Types de compte
    'account_types' => [
        'SAVINGS' => 'Compte Épargne',
        'CURRENT' => 'Compte Courant',
        'FIXED' => 'Dépôt à Terme',
        'LOAN' => 'Compte Prêt',
        'CREDIT' => 'Compte Crédit'
    ],
    
    // Codes devises
    'currencies' => [
        'XAF' => 'Franc CFA BEAC (Cameroun)',
        'USD' => 'Dollar US',
        'EUR' => 'Euro',
        'GHS' => 'Cedi Ghanéen',
        'NGN' => 'Naira Nigérian',
        'ZAR' => 'Rand Sud-Africain'
    ],
    
    // Exemples de réponses XML
    'response_examples' => [
        'success' => '<?xml version="1.0" encoding="UTF-8"?>
<AccountDetailInfoResponse>
    <responseStatus>
        <statusCode>0</statusCode>
        <statusMessage>SUCCESS</statusMessage>
    </responseStatus>
    <accountDetail>
        <accountNo>37220020391</accountNo>
        <accountName>John Doe</accountName>
        <accountType>SAVINGS</accountType>
        <currency>XAF</currency>
        <status>ACTIVE</status>
        <balance>1500000.00</balance>
        <customerId>CUST001</customerId>
        <branchCode>00001</branchCode>
        <openingDate>2024-01-15</openingDate>
    </accountDetail>
</AccountDetailInfoResponse>',
        
        'error' => '<?xml version="1.0" encoding="UTF-8"?>
<AccountDetailInfoResponse>
    <responseStatus>
        <statusCode>1</statusCode>
        <statusMessage>ACCOUNT_NOT_FOUND</statusMessage>
    </responseStatus>
</AccountDetailInfoResponse>'
    ],
    
    // Erreurs possibles
    'error_codes' => [
        '0' => 'Succès',
        '1' => 'Compte introuvable',
        '2' => 'Paramètres invalides',
        '3' => 'Authentification échouée',
        '4' => 'Erreur serveur',
        '5' => 'Timeout',
        '6' => 'Service indisponible',
        '401' => 'Non autorisé',
        '403' => 'Accès interdit',
        '404' => 'Endpoint non trouvé',
        '500' => 'Erreur interne serveur',
        '503' => 'Service indisponible'
    ],
    
    // Limite de taux (Rate Limiting)
    'rate_limit' => [
        'requests_per_minute' => 100,
        'requests_per_hour' => 5000,
        'requests_per_day' => 50000
    ],
    
    // Timeout
    'timeouts' => [
        'connection' => 10,  // secondes
        'read' => 30,        // secondes
        'total' => 40        // secondes
    ],
    
    // Fichiers de test/démo
    'test_data' => [
        [
            'account' => '37220020391',
            'name' => 'Test Account 1',
            'expected_response' => 'SUCCESS'
        ],
        [
            'account' => '37220020392',
            'name' => 'Test Account 2',
            'expected_response' => 'SUCCESS'
        ],
        [
            'account' => '99999999999',
            'name' => 'Invalid Account',
            'expected_response' => 'ACCOUNT_NOT_FOUND'
        ]
    ],
    
    // Versionning API
    'versions' => [
        '1.0' => [
            'date' => '2024-01-01',
            'status' => 'stable',
            'notes' => 'Version initiale'
        ],
        '1.1' => [
            'date' => '2024-06-01',
            'status' => 'stable',
            'notes' => 'Ajout support multi-devise'
        ]
    ],
    
    // Conformité et Sécurité
    'security' => [
        'ssl_version' => 'TLS 1.2+',
        'cipher_suite' => 'AES-256-GCM',
        'authentication' => 'Header-based',
        'rate_limiting' => true,
        'audit_logging' => true,
        'encryption' => 'End-to-end'
    ],
    
    // Documentation Ecobank
    'documentation' => [
        'main' => 'https://ecobank.com/corporate/api/documentation',
        'swagger' => 'https://api.ecobank.com/swagger-ui.html',
        'sandbox' => 'https://sandbox.ecobank.com/api',
        'support_email' => 'api-support@ecobank.com'
    ]
];
?>
