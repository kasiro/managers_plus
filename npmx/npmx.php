<?php

array_map(fn($p) => require $p, glob(dirname(__DIR__).'/*.php'));
// require dirname(__DIR__).'/manager.php';
// require dirname(__DIR__).'/yandex.php';

require '/home/kasiro/Документы/managers_plus/jhp_modules/arr.php';

$manager = new terminal_manager('npm', $argv);
$token = file_get_contents(dirname(__DIR__).'/token.txt');
$ya_disk = new yandex_disk_api($token);
function get_data($res, $first, $second, $int = 0){
	preg_match('/'.$first.'/ms', $res, $matches);
	if (!empty($matches)){
		$lines = explode(PHP_EOL, $matches[1]);
		unset($lines[0]);
		$lines = array_merge($lines, []);
		$my_res = [];
		foreach ($lines as $line){
			preg_replace_callback('/'.$second.'/ms', function ($matches) use (&$my_res, $int) {
				if (!empty($matches)){
					$my_res[] = $matches[$int];
				}
			}, $line);
		}
		$my_res = array_unique($my_res);
		$my_res = array_merge($my_res, []);
		$last = &$my_res[count($my_res) - 1];
		if (strlen($last) == 0 || $last == '')
			unset($my_res[count($my_res) - 1]);
		return $my_res;
	}
	return false;
}
$manager->get_help_list = function () {
	exec('pip -h', $res);
	// print_r($res);
	$res = implode(PHP_EOL, $res);
	$commands = get_data($res, 'All commands:.*?\w+\n\n', '\w+', 1);
	// $options = array_merge($options_2, $options_1);
	return $commands;
};
// $manager->is_install = fn($module_name) => {
// 	exec('pip list', $res);
// 	unset($res[0]);unset($res[1]);$res = array_merge($res, []);
// 	$modules = [];
// 	foreach ($res as $line){
// 		$modules[] = array_unique(explode(' ', $line))[0];
// 	}
// 	if (in_array($module_name, $modules)){
// 		return true;
// 	} else return false;
// };
$allvars = get_defined_vars();
$manager->on(['install', 'i'], function($module_name, $json, $json_path) use ($allvars) {
	extract($allvars);
	if ($ya_disk->file_exist('/sync/'.basename($json_path))){
		$ya_disk->download_file(dirname($json_path), '/sync/'.basename($json_path));
		$json = $manager->get_json();
	}
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
$manager->setDescription('install', 'Устанавливает модуль и заливает');

$allvars = get_defined_vars();
$manager->command('install_all', function($module_name, $json, $json_path) use ($allvars) {
	extract($allvars);
	if (!empty($json)){
		echo 'installing...' . PHP_EOL;
		foreach ($json as $module){
			$command = $manager->manager_name.' install '.$module;
			echo $command . PHP_EOL;
		}
	} else {
		echo 'Модули не найдены' . PHP_EOL;
	}
});
$manager->setDescription('install_all', 'Устанавливает все модули из списка');

$manager->command('mlist', function ($module_name, $json, $json_path) {
	if (!empty($json)){
		echo 'modules:' . PHP_EOL;
		foreach ($json as $name) { 
			echo '- '.$name . PHP_EOL;
		}
	} else {
		echo 'Модули не найдены' . PHP_EOL;
	}
});
$manager->setDescription('mlist', 'выводит список модулей');

$manager->command('count', function ($module_name, $json, $json_path) {
	echo 'modules count: '.count($json) . PHP_EOL;
});
$manager->setDescription('count', 'Выводит колличество модулей');

$allvars = get_defined_vars();
$manager->command('sync', function($module_name, $json, $json_path) use ($allvars) {
	extract($allvars);
	$before = count($json);
	if ($ya_disk->file_exist('/sync/'.basename($json_path))){
		$ya_disk->download_file(dirname($json_path), '/sync/'.basename($json_path));
		$json = $manager->get_json();
	}
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
$manager->setDescription('sync', 'Синхронизирует, Комп с диском, Диск -> Комп');

$allvars = get_defined_vars();
$manager->command('rm', function($module_name, $json, $json_path) use ($allvars) {
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
$manager->setDescription('rm', 'Удаляет модуль с диска');

$allvars = get_defined_vars();
$manager->on('uninstall', function($module_name, $json, $json_path) use ($allvars) {
	extract($allvars);
	if (!empty($json)){
		for ($i = 0; $i < count($json); $i++) { 
			$name = $json[$i];
			if ($name == $module_name){
				unset($json[$i]);
				break;
			}
		}
		echo 'module uninstall: ' . $module_name . PHP_EOL;
		file_put_contents($json_path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		$ya_disk->delete_file('/sync/'.basename($json_path));
		$ya_disk->upload_file($json_path, '/sync');
		return true;
	}
});
$manager->setDescription('uninstall', 'Удаляет модуль и заливает');

$allvars = get_defined_vars();
$manager->stabilize = function(string $pattern, array $names, $spaces = 3) use ($allvars) {
	extract($allvars);
	$List = [];
	$newList = arr::blob_string_sort($names);
	$spacesBefore = $spaces;
	for ($i = 0; $i < count($newList); $i++){
		if ($i > 0) {
			$before = $newList[$i - 1];
			$name = $newList[$i];
		} else {
			$before = '';
			$name = $newList[$i];
			goto add;
		}
		// echo $spaces . PHP_EOL;
		if (array_key_exists($name, $manager->descriptions) && array_key_exists($before, $manager->descriptions)){
			add:
			if ($i > 0){
				if (strlen($newList[$i - 1]) > strlen($name)){
					$spaces += (strlen($newList[$i - 1]) - strlen($name));
				}
			}
			$share = strlen($name) + $spaces;
			$shares[] = $share;
		}
	}
	$first = 0;
	$spaces = $spacesBefore;
	for ($i = 0; $i < count($newList); $i++){
		if ($i > 0) {
			$before = $newList[$i - 1];
			$name = $newList[$i];
		} else {
			$before = '';
			$name = $newList[$i];
			goto add_2;
		}
		// echo $spaces . PHP_EOL;
		if (array_key_exists($name, $manager->descriptions) && array_key_exists($before, $manager->descriptions)){
			add_2:
			if ($i > 0){
				if (strlen($newList[$i - 1]) > strlen($name)){
					$spaces += (strlen($newList[$i - 1]) - strlen($name));
				} else {
					if (count(array_unique($shares)) > 1){
						if ($first == 0){
							$spaces += 2;
							$first++;
						}
					}
				}
			}
			$newpattern = str_replace('%name', $name, $pattern);
			$newpattern = str_replace('%s', str_repeat(' ', $spaces), $newpattern);
			$newpattern = str_replace('%desc', $manager->getDescription($name), $newpattern);
			$List[] = $newpattern;
		}
	}
	return $List;
};

$allvars = get_defined_vars();
$manager->command('_h', function($module_name, $json, $json_path) use ($allvars) {
	extract($allvars);
	$all_list = array_merge($manager->command_list, $manager->on_list);
	// echo $manager_name.'x manager for '.$manager_name . PHP_EOL . PHP_EOL;
	echo $manager->manager_name.'x <command> [options]' . PHP_EOL;
	echo PHP_EOL;
	$texts = $manager->stabilize('- %name%s%desc', $all_list);
	foreach ($texts as $text) { 
		echo $text . PHP_EOL;
	}
});

$allvars = get_defined_vars();
$manager->other(function($manager_name, $args) use ($allvars) {
	extract($allvars);
	if (count($args) > 1){
		$command = $manager_name.' '.implode(' ', $args);
	} else {
		$com = @$args[1] ?? '';
		$command = $manager_name.' '.$com;
	}
	$commands = $manager->get_help_list();
	if ($commands){
		$com = '';
		for ($i = 1; $i <= count($args); $i++){
			if (!str_starts_with($args[$i], '-')){
				$com = $args[$i];
				break;
			}
		}
		if (in_array($com, $commands)){
			system($command);
		}
	} else {
		$commands = [
			'-h',
			'-l',
			'help',
			'test',
			'run'
		];
		$commands = array_merge($manager->command_list, $manager->on_list, $commands);
		if (!@isset($com)) $com = @$args[1] ?? '';
		if (in_array($com, $commands)){
			system($command);
		} else {
			if ($com != ''){
				echo 'Команда не найдена' . PHP_EOL;
			} else {
				echo 'Введите команду' . PHP_EOL;
			}
		}
	}
});