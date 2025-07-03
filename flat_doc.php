<?php
require __DIR__ . '/vendor/autoload.php';

use DocPHT\Model\FlatPageModel;

$slug = $_GET['page'] ?? 'example';
$model = new FlatPageModel();

$html = $model->render($slug);
if ($html === null) {
    http_response_code(404);
    echo "Page not found";
    exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($slug); ?></title>
</head>
<body>
<?php echo $html; ?>
</body>
</html>
