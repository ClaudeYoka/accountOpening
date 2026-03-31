<?php
/**
 * UDF Data Mapper - Maps Flexcube UDF Fields to Form Fields
 * 
 * Extracts custom UDF (User Defined Fields) from Flexcube API response
 * and maps them to form field names
 * 
 * UDF Fields available from Flexcube:
 * - TITLE, FIRST_NAME, LAST_NAME, SEX
 * - CONTACT_ADDRESS, NATIONALITY, COUNTRY
 * - ID_ISSUE_DATE, ID_EXP_DATE, BVN
 * - Additional fields like EMAIL, PHONE, etc. (custom UDF)
 */

class UDFDataMapper {
    
    /**
     * Complete UDF field names that can be returned from Flexcube
     * Maps Flexcube UDF names to user-friendly labels
     */
    public static $KNOWN_UDF_FIELDS = [
        // Personal Information
        'TITLE' => 'Title/Civility',
        'FIRST_NAME' => 'First Name',
        'LAST_NAME' => 'Last Name',
        'MIDDLE_NAME' => 'Middle Name',
        'SEX' => 'Gender/Sex',
        'DATE_OF_BIRTH' => 'Date of Birth',
        'NATIONALITY' => 'Nationality',
        
        // Contact Information
        'CONTACT_ADDRESS' => 'Residential Address',
        'PHONE' => 'Phone Number',
        'PHONE_NO' => 'Phone Number',
        'PHONENO' => 'Phone Number',
        'MOBILE' => 'Mobile Number',
        'EMAIL' => 'Email Address',
        'EMAIL_ID' => 'Email Address',
        'EMAILID' => 'Email Address',
        'TELEPHONE1' => 'Primary Telephone',
        'TELEPHONE2' => 'Secondary Telephone',
        'FAX' => 'Fax Number',
        'WEBSITE' => 'Website',
        
        // Location Information
        'COUNTRY' => 'Country',
        'CITY' => 'City',
        'STATE' => 'State/Province',
        'POSTAL_CODE' => 'Postal Code',
        'PLACE_OF_BIRTH' => 'Place of Birth',
        
        // Identification
        'ID_ISSUE_DATE' => 'ID Issue Date',
        'ID_EXP_DATE' => 'ID Expiration Date',
        'ID_TYPE' => 'ID Type',
        'BVN' => 'BVN Number',
        'PASSPORT_NO' => 'Passport Number',
        
        // Employment/Business
        'EMPLOYER_NAME' => 'Employer Name',
        'OCCUPATION' => 'Occupation',
        'BUSINESS_SECTOR' => 'Business Sector',
        
        // Additional Information
        'FATHER_NAME' => 'Father Name',
        'MOTHER_NAME' => 'Mother Name',
        'SPOUSE_NAME' => 'Spouse Name',
        'MARITAL_STATUS' => 'Marital Status',
    ];
    
    /**
     * Maps UDF data to form field IDs
     * 
     * @param array $udf_values Array of UDF values from Flexcube
     *                          Key: UDF name (e.g., 'FIRST_NAME')
     *                          Value: UDF value
     * 
     * @return array Mapped form data with both raw UDF and form field names
     */
    public static function mapUDFToFormFields($udf_values) {
        $form_data = [];
        
        if (empty($udf_values) || !is_array($udf_values)) {
            return $form_data;
        }
        
        // Map individual UDF fields
        foreach ($udf_values as $udf_name => $udf_value) {
            if (empty($udf_value)) {
                continue;
            }
            
            // Always include the raw UDF value
            $form_data['udf_' . strtolower($udf_name)] = $udf_value;
            
            // Normalize UDF name to uppercase for switch statement
            $udf_name_upper = strtoupper($udf_name);
            // Also handle camelCase by converting it to UPPER_SNAKE_CASE
            // e.g., "phoneNo" -> "PHONE_NO", "emailID" -> "EMAIL_ID"
            $udf_name_upper = preg_replace('/([a-z])([A-Z])/', '$1_$2', $udf_name_upper);
            
            // Map to specific form fields based on UDF name
            switch ($udf_name_upper) {
                case 'TITLE':
                    $form_data['title'] = $udf_value;
                    $form_data['civility'] = $udf_value;
                    break;
                    
                case 'FIRST_NAME':
                    $form_data['first-name'] = $udf_value;
                    $form_data['first_name'] = $udf_value;
                    $form_data['prenom'] = $udf_value;
                    $form_data['firstName'] = $udf_value;
                    break;
                    
                case 'LAST_NAME':
                case 'SURNAME':
                    $form_data['last-name'] = $udf_value;
                    $form_data['last_name'] = $udf_value;
                    $form_data['nom'] = $udf_value;
                    $form_data['lastName'] = $udf_value;
                    break;
                    
                case 'MIDDLE_NAME':
                    $form_data['middle-name'] = $udf_value;
                    $form_data['middle_name'] = $udf_value;
                    $form_data['prenom2'] = $udf_value;
                    break;
                    
                case 'SEX':
                case 'GENDER':
                    $form_data['sex'] = self::normalizeGender($udf_value);
                    $form_data['sexe'] = self::normalizeGender($udf_value);
                    $form_data['gender'] = self::normalizeGender($udf_value);
                    break;
                    
                case 'DATE_OF_BIRTH':
                case 'DOB':
                    $form_data['date-of-birth'] = $udf_value;
                    $form_data['date_of_birth'] = $udf_value;
                    $form_data['dob'] = $udf_value;
                    $form_data['dateNaissance'] = $udf_value;
                    break;
                    
                case 'NATIONALITY':
                    $form_data['nationality'] = $udf_value;
                    $form_data['nationalite'] = $udf_value;
                    $form_data['citizenship'] = $udf_value;
                    break;
                    
                case 'CONTACT_ADDRESS':
                case 'ADDRESS':
                    $form_data['address'] = $udf_value;
                    $form_data['adresse'] = $udf_value;
                    $form_data['residential-address'] = $udf_value;
                    $form_data['contact_address'] = $udf_value;
                    break;
                    
                case 'PHONE':
                case 'TELEPHONE1':
                case 'PRIMARY_PHONE':
                case 'PHONE_NO':
                case 'PHONENO':
                    $form_data['phone'] = $udf_value;
                    $form_data['telephone'] = $udf_value;
                    $form_data['tel'] = $udf_value;
                    $form_data['phone_number'] = $udf_value;
                    $form_data['numero_telephone'] = $udf_value;
                    break;
                    
                case 'TELEPHONE2':
                case 'MOBILE':
                case 'SECONDARY_PHONE':
                    $form_data['phone2'] = $udf_value;
                    $form_data['telephone2'] = $udf_value;
                    $form_data['tel2'] = $udf_value;
                    $form_data['mobile'] = $udf_value;
                    $form_data['numero_telephone2'] = $udf_value;
                    break;
                    
                case 'EMAIL':
                case 'EMAIL_ADDRESS':
                case 'EMAIL_ID':
                case 'EMAILID':
                    $form_data['email'] = $udf_value;
                    $form_data['email_address'] = $udf_value;
                    $form_data['adresse_email'] = $udf_value;
                    break;
                    
                case 'COUNTRY':
                case 'RESIDENCE_COUNTRY':
                    $form_data['country'] = $udf_value;
                    $form_data['residence-country'] = $udf_value;
                    $form_data['pays'] = $udf_value;
                    $form_data['residence_country'] = $udf_value;
                    break;
                    
                case 'CITY':
                    $form_data['city'] = $udf_value;
                    $form_data['ville'] = $udf_value;
                    break;
                    
                case 'STATE':
                case 'PROVINCE':
                    $form_data['state'] = $udf_value;
                    $form_data['province'] = $udf_value;
                    break;
                    
                case 'POSTAL_CODE':
                case 'ZIP_CODE':
                    $form_data['postal-code'] = $udf_value;
                    $form_data['postal_code'] = $udf_value;
                    $form_data['zip_code'] = $udf_value;
                    break;
                    
                case 'PLACE_OF_BIRTH':
                    $form_data['place-of-birth'] = $udf_value;
                    $form_data['lieu_naiss'] = $udf_value;
                    $form_data['pob'] = $udf_value;
                    break;
                    
                case 'ID_ISSUE_DATE':
                    $form_data['id-issue-date'] = $udf_value;
                    $form_data['id_issue_date'] = $udf_value;
                    break;
                    
                case 'ID_EXP_DATE':
                case 'ID_EXPIRATION_DATE':
                    $form_data['id-exp-date'] = $udf_value;
                    $form_data['id_exp_date'] = $udf_value;
                    break;
                    
                case 'BVN':
                    $form_data['bvn'] = $udf_value;
                    $form_data['bvn_number'] = $udf_value;
                    break;
                    
                case 'EMPLOYER_NAME':
                case 'EMPLOYER':
                    $form_data['employer'] = $udf_value;
                    $form_data['employer-name'] = $udf_value;
                    $form_data['employeur'] = $udf_value;
                    break;
                    
                case 'OCCUPATION':
                    $form_data['occupation'] = $udf_value;
                    $form_data['profession'] = $udf_value;
                    break;
                    
                case 'FATHER_NAME':
                    $form_data['father-name'] = $udf_value;
                    $form_data['father_name'] = $udf_value;
                    break;
                    
                case 'MOTHER_NAME':
                    $form_data['mother-name'] = $udf_value;
                    $form_data['mother_name'] = $udf_value;
                    break;
                    
                case 'MARITAL_STATUS':
                    $form_data['marital-status'] = $udf_value;
                    $form_data['marital_status'] = $udf_value;
                    break;
                    
                default:
                    // For any other UDF field, try to create a logical field name
                    $form_data[strtolower($udf_name)] = $udf_value;
                    break;
            }
        }
        
        return $form_data;
    }
    
    /**
     * Normalizes gender values to standard format
     * Converts M/Male/Masculin to 'M', F/Female/Feminin to 'F'
     * 
     * @param string $gender Raw gender value from API
     * @return string Normalized gender value
     */
    private static function normalizeGender($gender) {
        $gender = strtoupper(trim($gender));
        
        if (in_array($gender, ['M', 'MALE', 'MASCULIN', 'MAN', 'H', 'HOMME'])) {
            return 'M';
        }
        
        if (in_array($gender, ['F', 'FEMALE', 'FEMININ', 'WOMAN', 'FEMME'])) {
            return 'F';
        }
        
        // Return as-is if unrecognized
        return $gender;
    }
    
    /**
     * Extracts UDF data from Flexcube response XML
     * 
     * @param SimpleXMLElement $account_info The AccountDetailInfo element from XML
     * @return array Array of UDF values keyed by UDF name
     */
    public static function extractUDFFromXML($account_info) {
        $udf_values = [];
        
        if (!isset($account_info->UDFData)) {
            return $udf_values;
        }
        
        // Handle both single and multiple UDFData elements
        $udf_data_list = is_array($account_info->UDFData) 
            ? $account_info->UDFData 
            : [$account_info->UDFData];
        
        foreach ($udf_data_list as $udf) {
            if (isset($udf->udfName) && isset($udf->udfValue)) {
                $udf_name = (string)$udf->udfName;
                $udf_value = (string)$udf->udfValue;
                
                // Skip encrypted/placeholder values
                if (stripos($udf_value, 'ENCRYPTED') === false) {
                    $udf_values[$udf_name] = $udf_value;
                }
            }
        }
        
        return $udf_values;
    }
    
    /**
     * Get all available UDF field names
     * 
     * @return array List of known UDF field names
     */
    public static function getKnownUDFFields() {
        return array_keys(self::$KNOWN_UDF_FIELDS);
    }
    
    /**
     * Get UDF field description
     * 
     * @param string $udf_name UDF field name
     * @return string Description or empty string if unknown
     */
    public static function getUDFDescription($udf_name) {
        return self::$KNOWN_UDF_FIELDS[$udf_name] ?? '';
    }
    
    /**
     * Validates if a value looks like an email
     * 
     * @param string $value Value to check
     * @return bool True if looks like email
     */
    public static function isEmail($value) {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validates if a value looks like a phone number
     * Basic validation: contains digits and common phone characters
     * 
     * @param string $value Value to check
     * @return bool True if looks like phone number
     */
    public static function isPhoneNumber($value) {
        // Remove common phone formatting characters
        $cleaned = preg_replace('/[^\d+\-\(\)\s]/', '', $value);
        
        // Check if it has at least 5 digits
        $digits = preg_replace('/[^\d]/', '', $cleaned);
        
        return strlen($digits) >= 5;
    }
}
?>
