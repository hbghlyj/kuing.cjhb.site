<?php
$discuzRoot = dirname(__DIR__, 2).DIRECTORY_SEPARATOR;
chdir($discuzRoot);
require $discuzRoot.'source/class/class_core.php';
$discuz = C::app();
$discuz->init_cron = false;
$discuz->init();

if (empty($_FILES['file']) && empty($_FILES['photo'])) {
    header("HTTP/1.0 400 Bad Request");
    echo json_encode(array('status' => 400, 'error' => 'No file uploaded'));
    exit;
}

$file = !empty($_FILES['file']) ? $_FILES['file'] : $_FILES['photo'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    header("HTTP/1.0 400 Bad Request");
    echo json_encode(array('status' => 400, 'error' => 'File upload error code: ' . $file['error']));
    exit;
}

// 1. Check file extension
$allowedExts = array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp');
$fileName = $file['name'];
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if (!in_array($ext, $allowedExts)) {
    header("HTTP/1.0 400 Bad Request");
    echo json_encode(array('status' => 400, 'error' => 'Invalid file extension. Only images (jpg, png, gif, bmp, webp) are allowed.'));
    exit;
}

// 2. Validate MIME type & image dimensions
$mimeType = '';
if (function_exists('mime_content_type')) {
    $mimeType = mime_content_type($file['tmp_name']);
}
$allowedMimes = array('image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/webp', 'image/x-ms-bmp');
$imageInfo = @getimagesize($file['tmp_name']);

if ($imageInfo === false && (!empty($mimeType) && !in_array($mimeType, $allowedMimes))) {
    header("HTTP/1.0 400 Bad Request");
    echo json_encode(array('status' => 400, 'error' => 'Uploaded file is not a valid image.'));
    exit;
}

// 3. Create target storage directory
$subDir = 'data/attachment/chat/' . date('Ym') . '/';
$targetDir = $discuzRoot . $subDir;

if (!is_dir($targetDir)) {
    @mkdir($targetDir, 0777, true);
}

// 4. Generate unique filename
$newFileName = md5(uniqid(microtime(true), true)) . '.' . $ext;
$targetFilePath = $targetDir . $newFileName;

if (!move_uploaded_file($file['tmp_name'], $targetFilePath)) {
    header("HTTP/1.0 500 Internal Server Error");
    echo json_encode(array('status' => 500, 'error' => 'Failed to save uploaded photo.'));
    exit;
}

// 5. Generate site root URL for chat photo
$photoUrl = '/' . $subDir . $newFileName;

header("Content-Type: application/json");
echo json_encode(array(
    'status' => 200,
    'url' => $photoUrl
));
?>
