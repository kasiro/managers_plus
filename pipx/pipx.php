<?php

require dirname(__DIR__).'/manager.php';
require dirname(__DIR__).'/yandex.php';

$pip = new terminal_manager('pip', $argv);
$token = file_get_contents(dirname(__DIR__).'/token.txt');
$ya_disk = new yandex_disk_api($token);
$allvars = get_defined_vars();
$pip->on('install', function($module_name, $json, $json_path) use ($allvars) {
	extract($allvars);
	// $ya_disk->download_file(dirname($json_path), '/sync/'.basename($json_path));
	// $json = $pip->get_json();
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

$pip->command('install_all', function ($module_name, $json, $json_path) {
	foreach ($json as $module){
		echo '- '.$module . PHP_EOL;
	}
});

$pip->command('mlist', function ($module_name, $json, $json_path) {
	if (!empty($json)){
		echo 'modules:' . PHP_EOL;
		foreach ($json as $name) { 
			echo '- '.$name . PHP_EOL;
		}
	} else {
		echo 'Модули не найдены' . PHP_EOL;
	}
});

$pip->command('count', function ($module_name, $json, $json_path) {
	echo 'modules count: '.count($json) . PHP_EOL;
});

$allvars = get_defined_vars();
$pip->command('sync', function($module_name, $json, $json_path) use ($allvars) {
	extract($allvars);
	$before = count($json);
	$ya_disk->download_file(dirname($json_path), '/sync/'.basename($json_path));
	$json = $pip->get_json();
	foreach ($json as $mname){
		echo 'module "'.$mname.'" sync...' . PHP_EOL;
	}
	$after = count($json);
	if ($before < $after){
		echo 'modules installed: '.($after - $before) . PHP_EOL;
	} else {
		echo 'modules uninstalled: '.($before - $after) . PHP_EOL;
	}
	echo 'modules synchronized' . PHP_EOL;
	file_put_contents($json_path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
});

$allvars = get_defined_vars();
$pip->command('rm', function($module_name, $json, $json_path) use ($allvars) {
	extract($allvars);
	if (!empty($json)){
		$before = $json;
		for ($i = 0; $i < count($json); $i++) { 
			$name = $json[$i];
			if ($name == $module_name){
				unset($json[$i]);
				echo 'module remove on yandex disk: ' . $module_name . PHP_EOL;
				file_put_contents($json_path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
				$ya_disk->delete_file('/sync/'.basename($json_path));
				$ya_disk->upload_file($json_path, '/sync');
				file_put_contents($json_path, json_encode($before, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
				return true;
			}
		}
	}
	echo 'module "' . $module_name.'" not found...' . PHP_EOL;
	return false;
});

$allvars = get_defined_vars();
$pip->on('remove', function($module_name, $json, $json_path) use ($allvars) {
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
		$ya_disk->upload_file($json_path, '/sync');
		return true;
	}
});

$allvars = get_defined_vars();
$pip->command('_h', function($module_name, $json, $json_path) use ($allvars) {
	extract($allvars);
	echo 'commands:' . PHP_EOL;
	foreach ($pip->command_list as $name) { 
		echo '- '.$name . PHP_EOL;
	}
});