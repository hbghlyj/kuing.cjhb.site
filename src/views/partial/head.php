<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="/public/assets/js/latex.js"></script>

    <title><?= $PageTitle ?></title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="/public/assets/css/bootstrap.min.css" >
    <!-- Custom CSS -->
    <?php
    $cssFile = (!isset($_COOKIE["theme"])) ? 'light' : $_COOKIE["theme"] ;
    echo '<link type="text/css" rel="stylesheet" href="/public/assets/css/doc-pht.'.$cssFile.'.css" />';
    ?>
    <link rel="stylesheet" href="/public/assets/css/switch.css">
    <link rel="stylesheet" href="/public/assets/css/animation.css">
    <!-- Scrollbar Custom CSS -->
    <link rel="stylesheet" href="/public/assets/css/scrollbar.min.css">
    <!-- Syntax highlighting -->
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/default.min.css">
    <!-- Most popular and easiest to use icon set -->
    <link rel="stylesheet" href="/public/assets/css/font-awesome.min.css">
    <!-- Stylesheet fro bootstrap select in form -->
    <link rel="stylesheet" href="/public/assets/css/bootstrap-select.min.css">
    <!-- Favicon -->
    <?php
        if (file_exists('json/favicon.png')) {
            echo '<link id="fav" rel="icon" type="image/png" href="/json/favicon.png?'.time().'">';
        }
    ?>

</head>

<body>

    <div class="wrapper">

    <div class="progress-container">
        <div class="progress-bar" id="scrollindicator"></div>
    </div>

    <?php include 'sidebar.php'; ?>

    <!-- Page Content  -->
    <div id="content">
    <div class="container-fluid">

    <?php if($this->msg->display()) : ?>
        <?php echo $this->msg->display(); ?>
    <?php endif; ?>