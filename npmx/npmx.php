<?php

require dirname(__DIR__).'/manager.php';
require dirname(__DIR__).'/yandex.php';

$npm = new terminal_manager('npm', $argv);
$token = file_get_contents(dirname(__DIR__).'/token.txt');
$ya_disk = new yandex_disk_api($token);
$allvars = get_defined_vars();
$npm->on(['install', 'i'], function($module_name, $json, $json_path) use ($allvars) {
	extract($allvars);
	file_put_contents($json_path, '');
	$ya_disk->download_file(dirname($json_path), '/sync/'.basename($json_path));
	if (empty($json)){
		$json = [];
		$json[] = $module_name;
		echo 'first module installed: ' . $module_name . PHP_EOL;
	} else {
		if (!in_array($module_name, $json)){
			$json[] = $module_name;
			echo 'module installed: ' . $module_name . PHP_EOL;
		} else {
			echo 'module exist: ' . $module_name . PHP_EOL;
			return false;
		}
	}
	file_put_contents($json_path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
	$ya_disk->delete_file('/sync/'.basename($json_path));
	$ya_disk->upload_file($json_path, $path = '/sync');
	return true;
});

$npm->command('install_all', function ($module_name, $json, $json_path) {
	foreach ($json as $module){
		echo '- '.$module . PHP_EOL;
	}
});

$npm->command('mlist', function ($module_name, $json, $json_path) {
	echo 'modules:' . PHP_EOL;
	foreach ($json as $name) { 
		echo '- '.$name . PHP_EOL;
	}
});

$npm->command('count', function ($module_name, $json, $json_path) {
	echo 'modules count: '.count($json) . PHP_EOL;
});

$npm->command('rm', function ($module_name, $json, $json_path) {
	if (!empty($json)){
		for ($i = 0; $i < count($json); $i++) { 
			$name = $json[$i];
			if ($name == $module_name){
				echo 'module remove on yandex disk: ' . $module_name . PHP_EOL;
				file_put_contents($json_path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
				$ya_disk->delete_file('/sync/'.basename($json_path));
				$ya_disk->upload_file($json_path, $path = '/sync');
				return true;
			}
		}
	}
	return false;
});

$allvars = get_defined_vars();
$npm->on('remove', function($module_name, $json, $json_path) use ($allvars) {
	extract($allvars);
	if (!empty($json)){
		for ($i = 0; $i < count($json); $i++) { 
			$name = $json[$i];
			if ($name == $module_name){
				unset($json[$i]);
				break;
			}
		}
		echo 'module remove: ' . $module_name . PHP_EOL;
		file_put_contents($json_path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		$ya_disk->delete_file('/sync/'.basename($json_path));
		$ya_disk->upload_file($json_path, $path = '/sync');
		return true;
	}
});

$allvars = get_defined_vars();
$npm->command('_h', function($module_name, $json, $json_path) use ($allvars) {
	extract($allvars);
	echo 'commands:' . PHP_EOL;
	foreach ($npm->command_list as $name) { 
		echo '- '.$name . PHP_EOL;
	}
});