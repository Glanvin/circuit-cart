<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$themePreference = !empty($this->session->userdata('theme')) ? $this->session->userdata('theme') : 'light';
$initialTheme = $themePreference === 'system' ? 'light' : $themePreference;
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= $initialTheme ?>" data-theme-url="<?= site_url('settings/theme') ?>" data-theme-preference="<?= $themePreference ?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?=$title ?? ''?></title>
	<link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/bs/bootstrap.min.css')?>">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
	<link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/style.css')?>">
</head>
<body>
<?= $this->toastify->render(); ?>