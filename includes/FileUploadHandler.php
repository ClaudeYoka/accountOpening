<?php
class FileUploadHandler {
    private $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    private $maxSize = 5242880; // 5MB
    private $uploadDir = __DIR__ . '/../uploads/';

    public function __construct() {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    public function validate($file) {
        // Vérifier si le fichier a été uploadé
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception('Fichier non uploadé correctement');
        }

        // Vérifier taille
        if ($file['size'] > $this->maxSize) {
            throw new Exception('Fichier trop volumineux (max 5MB)');
        }

        // Vérifier extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowedExts)) {
            throw new Exception('Type de fichier non autorisé');
        }

        // Vérifier MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        if (!in_array($mime, $allowedMimes)) {
            throw new Exception('Type MIME non valide');
        }

        return true;
    }

    public function save($file, $prefix = '') {
        $this->validate($file);

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $newName = $prefix . uniqid() . '.' . $ext;
        $path = $this->uploadDir . $newName;

        if (!move_uploaded_file($file['tmp_name'], $path)) {
            throw new Exception('Échec de l\'upload du fichier');
        }

        // Permissions sécurisées
        chmod($path, 0644);

        return $newName;
    }
}
?>