<?php
declare(strict_types=1);

/*
 * Speed fix 2026-10-28: responsive srcset for large CMS uploads.
 * If /assets/uploads/...png has a 760px sibling "...png.m.webp", phones get the
 * smaller copy. Same picture, same layout. Falls back to nothing when missing.
 */
if (!function_exists('ke_img_srcset')) {
	function ke_img_srcset(string $url, string $sizes): string
	{
		$path = parse_url($url, PHP_URL_PATH);
		if (!is_string($path) || strpos($path, '/assets/uploads/') !== 0 || strpos($path, '..') !== false) {
			return '';
		}
		$root = dirname(__DIR__);
		$file = $root . rawurldecode($path);
		if (!is_file($file . '.m.webp')) {
			return '';
		}
		$info = @getimagesize($file);
		$w = is_array($info) ? (int) $info[0] : 0;
		if ($w <= 900) {
			return '';
		}
		$e = static function (string $v): string {
			return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
		};
		return ' srcset="' . $e($path . '.m.webp') . ' 760w, ' . $e($url) . ' ' . $w . 'w" sizes="' . $e($sizes) . '"';
	}
}
