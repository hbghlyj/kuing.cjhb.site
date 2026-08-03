<?php

// Wrapper for backward compatibility - delegates to merged rebuild_styles.php
$argv = array_merge([$argv[0], '--action=verhash'], array_slice($argv, 1));
require __DIR__.'/rebuild_styles.php';

