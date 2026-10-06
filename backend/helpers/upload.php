<?php
// Validates and stores an uploaded image, returns the new file name.
// Throws InvalidArgumentException with a user-friendly message when the file is not acceptable.
function save_uploaded_image(array $file, string $dir, string $prefix, int $maxBytes = 2097152): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('The upload failed. Please try again.');
    }
    if ($file['size'] > $maxBytes) {
        throw new InvalidArgumentException('Image must be 2 MB or smaller.');
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new InvalidArgumentException('The upload failed. Please try again.');
    }
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime  = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($types[$mime]) || @getimagesize($file['tmp_name']) === false) {
        throw new InvalidArgumentException('Use a JPG, PNG, or WEBP image.');
    }
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new RuntimeException('Could not save the image.');
    }
    $name = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $types[$mime];
    if (!move_uploaded_file($file['tmp_name'], rtrim($dir, '/\\') . '/' . $name)) {
        throw new RuntimeException('Could not save the image.');
    }
    return $name;
}