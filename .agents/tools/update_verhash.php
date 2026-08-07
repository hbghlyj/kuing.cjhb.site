<?php

// Wrapper for verhash-only operation - delegates to merged rebuild_styles.php
if(!defined('APP_ACTION')) {
	define('APP_ACTION', 'verhash');
}
require __DIR__.'/rebuild_styles.php';

