<?php

require_once __DIR__.'/bootstrap.php';
$discuzRoot = chat_init();
chat_require_write();

$file = !empty($_FILES['file']) ? $_FILES['file'] : (!empty($_FILES['photo']) ? $_FILES['photo'] : null);
if(!$file || !is_array($file) || $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
	chat_json(400, ['error' => 'No valid file was uploaded']);
}
if($file['size'] <= 0 || $file['size'] > 5 * 1024 * 1024) {
	chat_json(413, ['error' => 'Image must be no larger than 5 MB']);
}

$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowedMimes = [
	'jpg' => ['image/jpeg'],
	'jpeg' => ['image/jpeg'],
	'png' => ['image/png'],
	'gif' => ['image/gif'],
	'bmp' => ['image/bmp', 'image/x-ms-bmp'],
	'webp' => ['image/webp'],
];
if(!isset($allowedMimes[$extension]) || !function_exists('finfo_open')) {
	chat_json(400, ['error' => 'Unsupported image type']);
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : false;
if($finfo) finfo_close($finfo);
$imageInfo = @getimagesize($file['tmp_name']);
if($mime === false || $imageInfo === false || empty($imageInfo['mime'])
	|| !in_array($mime, $allowedMimes[$extension], true)
	|| !in_array($imageInfo['mime'], $allowedMimes[$extension], true)) {
	chat_json(400, ['error' => 'Uploaded file is not a valid image']);
}

$subDir = 'data/attachment/chat/';
$targetDir = $discuzRoot.$subDir;
if(!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
	chat_json(500, ['error' => 'Unable to create upload directory']);
}
$filename = bin2hex(random_bytes(16)).'.'.$extension;
$target = $targetDir.$filename;
if(!move_uploaded_file($file['tmp_name'], $target)) {
	chat_json(500, ['error' => 'Unable to save uploaded image']);
}
@chmod($target, 0644);

chat_json(200, ['status' => 200, 'url' => '/'.$subDir.$filename]);
