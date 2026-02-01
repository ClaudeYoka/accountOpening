<?php
/**
 * Flexcube API Configuration
 * 
 * Configuration centralisée pour l'intégration Flexcube
 * À personnaliser selon l'environnement
 */

// ============================================
// ENVIRONNEMENT
// ============================================

$environment = getenv('APP_ENV') ?: 'development'; // development, staging, production

// ============================================
// FLEXCUBE API CONFIGURATION
// ============================================

return [
    
    // Environnements
    'environments' => [
        
        'development' => [
            'name' => 'Développement/Test',
            'api_url' => 'https://devtuat.ecobank.com/accountenquiryserviceaffiliate/accountenquiry/ecomobileflex/getaccountinfo',
            'verify_ssl' => false,
            'timeout' => 30,
            'cache_duration' => 3600,
            'test_account' => '37220020391',
        ],
        
        'staging' => [
            'name' => 'Staging/Recette',
            'api_url' => 'https://stgtuat.ecobank.com/accountenquiryserviceaffiliate/accountenquiry/ecomobileflex/getaccountinfo',
            'verify_ssl' => true,
            'timeout' => 30,
            'cache_duration' => 3600,
            'test_account' => '[À OBTENIR]',
        ],
        
        'production' => [
            'name' => 'Production',
            'api_url' => 'https://api.ecobank.com/accountenquiryserviceaffiliate/accountenquiry/ecomobileflex/getaccountinfo',
            'verify_ssl' => true,
            'timeout' => 30,
            'cache_duration' => 3600,
            'test_account' => '[À OBTENIR]',
        ]
    ],
    
    // Configuration d'authentification
    'authentication' => [
        'source_code' => getenv('FLEXCUBE_SOURCE_CODE') ?: 'ECOBANKWEB',
        'request_type' => 'GETACCINFO',
        'affiliate_code' => getenv('FLEXCUBE_AFFILIATE_CODE') ?: 'ECG',
        'source_channel_id' => 'WEB',
        'version' => '1.0'
    ],
    
    // Headers HTTP
    'headers' => [
        'Content-Type' => 'application/xml',
        'Accept' => 'application/xml',
        'User-Agent' => 'Ecobank-Corporate-Portal/1.0'
    ],
    
    // Champs UDF obligatoires
    'required_udf_fields' => [
        'FIRST_NAME',
        'LAST_NAME',
        'NATIONALITY',
        'CONTACT_ADDRESS',
    ],
    
    // Champs UDF optionnels (mais recommandés)
    'recommended_udf_fields' => [
        'EMAIL',
        'PHONE',
        'TELEPHONE1',
        'TELEPHONE2',
        'SEX',
        'DATE_OF_BIRTH',
        'COUNTRY',
        'TITLE',
        'PLACE_OF_BIRTH',
        'ID_ISSUE_DATE',
        'ID_EXP_DATE',
    ],
    
    // Limites et validations
    'limits' => [
        'account_number_min_length' => 10,
        'account_number_max_length' => 20,
        'request_timeout' => 30,
        'cache_ttl' => 3600,
        'max_retry_attempts' => 3,
        'retry_delay' => 1000, // ms
    ],
    
    // Codes de statut connus
    'account_statuses' => [
        'ACTIVE' => 'Compte actif',
        'INACTIVE' => 'Compte inactif',
        'BLOCKED' => 'Compte bloqué',
        'CLOSED' => 'Compte fermé',
        'DORMANT' => 'Compte inactif (dormant)',
        'SUSPENDED' => 'Compte suspendu'
    ],
    
    // Types de compte
    'account_types' => [
        'U' => 'Compte Unique',
        'SAVINGS' => 'Compte Épargne',
        'CURRENT' => 'Compte Courant',
        'FIXED' => 'Dépôt à Terme',
        'LOAN' => 'Compte Prêt',
        'CREDIT' => 'Compte Crédit'
    ],
    
    // Codes devises
    'currencies' => [
        'XAF' => 'Franc CFA BEAC',
        'USD' => 'Dollar US',
        'EUR' => 'Euro',
        'GBP' => 'Livre sterling',
    ],
    
    // Types d'identification
    'id_types' => [
        'NATIONAL_ID_CARD' => 'Carte Nationale d\'Identité',
        'PASSPORT' => 'Passeport',
        'DRIVING_LICENSE' => 'Permis de Conduire',
        'VOTER_ID' => 'Carte d\'Électeur',
    ],
    
    // Mappage des champs UDF vers champs formulaire
    'field_mappings' => [
        'FIRST_NAME' => ['first-name', 'first_name', 'prenom'],
        'LAST_NAME' => ['last-name', 'last_name', 'nom'],
        'EMAIL' => ['email', 'adresse_email'],
        'PHONE' => ['telephone', 'phone', 'tel', 'numero_telephone'],
        'TELEPHONE1' => ['telephone', 'phone'],
        'TELEPHONE2' => ['telephone2', 'phone2', 'tel2'],
        'SEX' => ['sex', 'sexe', 'gender'],
        'NATIONALITY' => ['nationality', 'nationalite'],
        'CONTACT_ADDRESS' => ['address', 'adresse', 'residential-address'],
        'COUNTRY' => ['country', 'pays'],
        'TITLE' => ['title', 'civility'],
        'PLACE_OF_BIRTH' => ['place-of-birth', 'pob'],
        'DATE_OF_BIRTH' => ['date-of-birth', 'dob'],
    ],
    
    // Options de logging
    'logging' => [
        'enabled' => true,
        'level' => 'INFO', // DEBUG, INFO, WARNING, ERROR
        'log_file' => __DIR__ . '/../logs/flexcube.log',
        'log_responses' => false, // À false en production
        'log_errors' => true,
    ],
    
    // Options de cache
    'cache' => [
        'enabled' => true,
        'backend' => 'memory', // memory, file, redis (future)
        'ttl' => 3600,
        'path' => __DIR__ . '/../cache/flexcube',
    ],
    
    // Options de sécurité
    'security' => [
        'verify_ssl' => getenv('FLEXCUBE_VERIFY_SSL') === 'true',
        'encrypt_responses' => false, // À false pour maintenant
        'sanitize_output' => true,
        'validate_input' => true,
    ]
];
?>
