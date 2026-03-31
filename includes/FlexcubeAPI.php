<?php
/**
 * Flexcube Account Enquiry Service
 * 
 * Service pour intégrer l'API Flexcube d'Ecobank
 * URL: https://devtuat.ecobank.com/accountenquiryserviceaffiliate/accountenquiry/ecomobileflex/getaccountinfo
 */

require_once(__DIR__ . '/UDFDataMapper.php');

class FlexcubeAPI {
    
    // Configuration
    private $api_url = 'https://devtuat.ecobank.com/accountenquiryserviceaffiliate/accountenquiry/ecomobileflex/getaccountinfo';
    private $timeout = 30;
    private $verify_ssl = false; // À METTRE À TRUE EN PRODUCTION
    
    // Headers de la requête
    private $source_code = 'ECOBANKWEB';
    private $request_type = 'GETACCINFO';
    private $affiliate_code = 'ECG';
    private $source_channel_id = 'WEB';
    
    // Cache optionnel
    private static $cache = [];
    private static $cache_duration = 3600; // 1 heure
    
    /**
     * Récupère les informations d'un compte depuis Flexcube
     * 
     * @param string $account_number Le numéro de compte
     * @param string $request_id Identifiant unique de la requête (optionnel)
     * @return array Réponse structurée ['success' => bool, 'data' => array|null, 'error' => string|null]
     */
    public function getAccountInfo($account_number, $request_id = null) {
        
        // Validation
        if (empty($account_number)) {
            return $this->error('Numéro de compte manquant');
        }
        
        // Vérifier le cache
        $cache_key = 'flexcube_' . md5($account_number);
        if (isset(self::$cache[$cache_key])) {
            $cached = self::$cache[$cache_key];
            if (time() - $cached['timestamp'] < self::$cache_duration) {
                return $cached['data'];
            }
        }
        
        // Générer un request_id s'il n'existe pas
        if (empty($request_id)) {
            $request_id = 'REQ-' . date('YmdHis') . '-' . rand(1000, 9999);
        }
        
        // Construire la requête XML
        $xml_request = $this->buildXmlRequest($account_number, $request_id);
        
        // Envoyer la requête
        $response = $this->sendRequest($xml_request);
        
        // Mettre en cache
        if ($response['success']) {
            self::$cache[$cache_key] = [
                'timestamp' => time(),
                'data' => $response
            ];
        }
        
        return $response;
    }
    
    /**
     * Construit la requête XML pour Flexcube
     * 
     * @param string $account_number Numéro de compte
     * @param string $request_id ID de la requête
     * @return string XML formaté
     */
    private function buildXmlRequest($account_number, $request_id) {
        $request_token = $request_id; // Token = ID pour la démo
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<AccountDetailInfoRequest>' . "\n";
        $xml .= '    <hostHeaderInfo>' . "\n";
        $xml .= '        <sourceCode>' . htmlspecialchars($this->source_code) . '</sourceCode>' . "\n";
        $xml .= '        <requestId>' . htmlspecialchars($request_id) . '</requestId>' . "\n";
        $xml .= '        <requestToken>' . htmlspecialchars($request_token) . '</requestToken>' . "\n";
        $xml .= '        <requestType>' . htmlspecialchars($this->request_type) . '</requestType>' . "\n";
        $xml .= '        <affiliateCode>' . htmlspecialchars($this->affiliate_code) . '</affiliateCode>' . "\n";
        $xml .= '        <sourceChannelId>' . htmlspecialchars($this->source_channel_id) . '</sourceChannelId>' . "\n";
        $xml .= '    </hostHeaderInfo>' . "\n";
        $xml .= '    <accountNo>' . htmlspecialchars($account_number) . '</accountNo>' . "\n";
        $xml .= '</AccountDetailInfoRequest>';
        
        return $xml;
    }
    
    /**
     * Envoie la requête HTTP à Flexcube
     * 
     * @param string $xml_request Contenu XML
     * @return array Réponse structurée
     */
    private function sendRequest($xml_request) {
        
        // Initialiser cURL
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $xml_request,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/xml',
                'Content-Length: ' . strlen($xml_request),
                'Accept: application/xml'
            ],
            CURLOPT_SSL_VERIFYPEER => $this->verify_ssl,
            CURLOPT_SSL_VERIFYHOST => $this->verify_ssl ? 2 : 0,
        ]);
        
        // Exécuter
        $response_xml = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($curl);
        $curl_errno = curl_errno($curl);
        
        curl_close($curl);
        
        // Gérer les erreurs cURL
        if ($curl_errno) {
            return $this->error("Erreur cURL: $curl_error (Code: $curl_errno)");
        }
        
        // Vérifier le code HTTP
        if ($http_code !== 200) {
            return $this->error("Erreur HTTP $http_code. Réponse: " . substr($response_xml, 0, 200));
        }
        
        // Parser la réponse XML
        return $this->parseResponse($response_xml);
    }
    
    /**
     * Parse la réponse XML de Flexcube
     * 
     * @param string $xml_response Réponse XML
     * @return array Données structurées
     */
    private function parseResponse($xml_response) {
        
        try {
            // Désactiver les avertissements XML
            libxml_use_internal_errors(true);
            
            $xml = simplexml_load_string($xml_response);
            
            if ($xml === false) {
                $errors = libxml_get_errors();
                $error_msg = 'Erreur parsing XML: ';
                foreach ($errors as $err) {
                    $error_msg .= $err->message . ' ';
                }
                libxml_clear_errors();
                return $this->error($error_msg);
            }
            
            // Vérifier le responseCode dans hostHeaderInfo
            if (isset($xml->hostHeaderInfo->responseCode)) {
                $response_code = (string)$xml->hostHeaderInfo->responseCode;
                $response_message = (string)$xml->hostHeaderInfo->responseMessage ?? 'Unknown';
                
                if ($response_code !== '000') {
                    return $this->error("API Error: $response_message (Code: $response_code)");
                }
            }
            
            // Vérifier s'il y a les données du compte (AccountDetailInfo - pas accountDetail)
            if (!isset($xml->AccountDetailInfo)) {
                return $this->error('Compte introuvable ou réponse invalide');
            }
            
            // Extraire les données
            $account_info = $xml->AccountDetailInfo;
            
            // Extraire les UDFData
            $udf_values = [];
            if (isset($account_info->UDFData)) {
                foreach ($account_info->UDFData as $udf) {
                    $udf_name = (string)$udf->udfName;
                    $udf_value = (string)($udf->udfValue ?? '');
                    $udf_values[$udf_name] = $udf_value;
                }
            }
            
            // Map UDF data using UDFDataMapper
            $mapped_fields = UDFDataMapper::mapUDFToFormFields($udf_values);
            
            $data = [
                'account_number' => (string)$account_info->accountNo ?? null,
                'account_name' => (string)$account_info->accountName ?? null,
                'account_type' => (string)$account_info->accountType ?? null,
                'account_class' => (string)$account_info->accountClass ?? null,
                'currency' => (string)$account_info->ccy ?? null,
                'status' => (string)$account_info->accountStatus ?? null,
                'available_balance' => (string)$account_info->availableBalance ?? null,
                'current_balance' => (string)$account_info->currentBalance ?? null,
                'customer_id' => (string)$account_info->customerID ?? null,
                'customer_type' => (string)$account_info->customerType ?? null,
                'branch_code' => (string)$account_info->branchCode ?? null,
                'od_limit' => (string)$account_info->ODLimit ?? null,
                'blocked_amount' => (string)$account_info->blockedAmount ?? null,
                
                // Raw UDF values
                'udf_raw' => $udf_values,
                
                // Mapped form fields
                'form_fields' => $mapped_fields,
                
                // Legacy UDF fields for backward compatibility
                'first_name' => $mapped_fields['first-name'] ?? $mapped_fields['first_name'] ?? null,
                'last_name' => $mapped_fields['last-name'] ?? $mapped_fields['last_name'] ?? null,
                'contact_address' => $mapped_fields['address'] ?? $mapped_fields['adresse'] ?? null,
                'nationality' => $mapped_fields['nationality'] ?? $mapped_fields['nationalite'] ?? null,
                'country' => $mapped_fields['country'] ?? $mapped_fields['pays'] ?? null,
                'bvn' => $mapped_fields['bvn'] ?? null,
                'sex' => $mapped_fields['sex'] ?? $mapped_fields['sexe'] ?? null,
                'title' => $mapped_fields['title'] ?? null,
                // Try multiple phone field names from UDF data
                'phone' => $mapped_fields['phone'] ?? $mapped_fields['telephone'] ?? $udf_values['phoneNo'] ?? $udf_values['PHONENO'] ?? $udf_values['PHONE_NO'] ?? null,
                // Try multiple email field names from UDF data
                'email' => $mapped_fields['email'] ?? $mapped_fields['adresse_email'] ?? $udf_values['emailID'] ?? $udf_values['EMAILID'] ?? $udf_values['EMAIL_ID'] ?? null,
                
                'raw_response' => $xml_response
            ];
            
            return [
                'success' => true,
                'data' => $data,
                'error' => null,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            return $this->error('Exception parsing: ' . $e->getMessage());
        }
    }
    
    /**
     * Retourne un tableau d'erreur structuré
     * 
     * @param string $message Message d'erreur
     * @return array
     */
    private function error($message) {
        return [
            'success' => false,
            'data' => null,
            'error' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Configure les paramètres d'authentification
     * 
     * @param string $source_code Code source
     * @param string $affiliate_code Code affilié
     * @return void
     */
    public function setAuthConfig($source_code, $affiliate_code) {
        $this->source_code = $source_code;
        $this->affiliate_code = $affiliate_code;
    }
    
    /**
     * Active/désactive la vérification SSL
     * 
     * @param bool $verify
     * @return void
     */
    public function setSSLVerification($verify = true) {
        $this->verify_ssl = $verify;
    }
    
    /**
     * Définit l'URL de l'API
     * 
     * @param string $url
     * @return void
     */
    public function setApiUrl($url) {
        $this->api_url = $url;
    }
    
    /**
     * Teste la connexion à l'API
     * 
     * @param string $test_account Numéro de compte test
     * @return array Résultat du test
     */
    public function testConnection($test_account = '37220020391') {
        $response = $this->getAccountInfo($test_account, 'TEST-' . date('YmdHis'));
        return [
            'connected' => $response['success'],
            'status' => $response['success'] ? 'OK' : 'FAIL',
            'message' => $response['error'] ?? 'Connexion établie avec succès',
            'details' => $response
        ];
    }
    
    /**
     * Récupère plusieurs comptes (batch)
     * 
     * @param array $account_numbers Liste de numéros de compte
     * @return array Résultats pour chaque compte
     */
    public function getMultipleAccounts($account_numbers) {
        $results = [];
        foreach ($account_numbers as $account) {
            $results[$account] = $this->getAccountInfo($account);
        }
        return $results;
    }
}
?>
