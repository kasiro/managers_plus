<?php

require dirname(__DIR__).'/manager.php';
require dirname(__DIR__).'/yandex.php';

require '/home/kasiro/Документы/managers_plus/pipx/jhp_modules/arr.php';

function internet_exists(): bool {
	return @file_get_contents('https://ya.ru/') !== false;
}
$pip = new terminal_manager('pip', $argv);
$ie = internet_exists();
$config = [];
$config['ieno'] = 'Интернет не доступен';
$config['ieyes'] = 'Интернет доступен';
$config['diskno'] = str_replace('Интернет', 'Диск', $config['ieno']);
// $ie = false;
if ($ie){
	$token = file_get_contents(dirname(__DIR__).'/token.txt');
	$ya_disk = new yandex_disk_api($token);
}
$allvars = get_defined_vars();
// $pip->get_module = function($module_name) use ($allvars) {
	extract($allvars);
// 	exec('pip list | grep '.$module_name, $res);
// 	return $res[0];
// 	return false;
// };
$allvars = get_defined_vars();
$pip->is_install = function($module_name) use ($allvars) {
	extract($allvars);
	exec('pip list', $res);
	unset($res[0]);unset($res[1]);$res = array_merge($res, []);
	$modules = [];
	foreach ($res as $line){
		$modules[] = array_unique(explode(' ', $line))[0];
	}
	if (in_array($module_name, $modules)){
		return true;
	} else return false;
};
$allvars = get_defined_vars();
$pip->command('status', function($module_name, $json, $json_path, $args) use ($allvars) {
	extract($allvars);
	if ($ie){
		echo 'Интернет доступен' . PHP_EOL;
	} else {
		echo 'Интернет не подключен' . PHP_EOL;
	}
});
$allvars = get_defined_vars();
// $pip->command('get', function($module_name, $json, $json_path, $args) use ($allvars) {
	extract($allvars);
// 	if ($pip->is_install($module_name)){
// 		exec('pip list', $res);
// 		$ec = '';
// 		$ec .= $res[0] . PHP_EOL;
// 		$ec .= $res[1] . PHP_EOL;
// 		$ec .= $pip->get_module($module_name);
// 		echo $ec . PHP_EOL;
// 		// echo 'module "'.$module_name.'" is installed' . PHP_EOL;
// 	} else {
// 		echo 'module "'.$module_name.'" not found' . PHP_EOL;
// 	}
// });
$allvars = get_defined_vars();
$pip->on('install', function($module_name, $json, $json_path) use ($allvars) {
	extract($allvars);
	if ($ie){
		$ya_disk->download_file(dirname($json_path), '/sync/'.basename($json_path));
	}
	// $json = $pip->get_json();
	if (strlen($module_name) > 0 && $module_name != ''){
		if (!$pip->is_install($module_name)){
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
			if ($ie){
				$ya_disk->delete_file('/sync/'.basename($json_path));
				$ya_disk->upload_file($json_path, '/sync');
			}
			if (!$ie){
				echo 'Интернет не доступен' . PHP_EOL;
			}
			return true;
		} else {
			echo 'Модуль уже установлен' . PHP_EOL;
		}
	} else {
		echo 'Введите Модуль' . PHP_EOL;
	}
});
$pip->setDescription('install', 'Устанавливает модуль и заливает');

$allvars = get_defined_vars();
$pip->command('install_all', function($module_name, $json, $json_path) use ($allvars) {
	extract($allvars);
	if (strlen($module_name) == 0 || $module_name == ''){
		if (!empty($json)){
			echo 'installing...' . PHP_EOL;
			foreach ($json as $module){
				$command = $pip->manager_name.' install '.$module;
				echo $command . PHP_EOL;
			}
		} else {
			echo 'Модули не найдены' . PHP_EOL;
		}
	}
});
$pip->setDescription('install_all', 'Устанавливает все модули из списка');

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
$pip->setDescription('mlist', 'выводит список модулей');

$pip->command('count', function ($module_name, $json, $json_path) {
	echo 'Колличество модулей: '.count($json) . PHP_EOL;
});
$pip->setDescription('count', 'Выводит колличество модулей');

$allvars = get_defined_vars();
$pip->command('sync', function($module_name, $json, $json_path) use ($allvars) {
	extract($allvars);
	if ($ie){
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
	} else {
		echo 'Интернет не доступен' . PHP_EOL;
	}
});
$pip->setDescription('sync', 'Синхронизирует, Комп с диском, Диск -> Комп');

$allvars = get_defined_vars();
$pip->command('del', function($module_name, $json, $json_path) use ($allvars) {
	extract($allvars);
	$found = false;
	if (strlen($module_name) > 0 && $module_name != ''){
		if (!empty($json)){
			for ($i = 0; $i < count($json); $i++) { 
				if (isset($json[$i])) $name = $json[$i];
				else continue;
				if ($name == $module_name){
					$found = !$found;
					unset($json[$i]);
					break;
				}
			}
			if ($found){
				echo 'module deleted: ' . $module_name . PHP_EOL;
				$json = array_merge($json, []);
				if (count($json) > 0) file_put_contents($json_path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
				else file_put_contents($json_path, '[]');
			} else {
				echo 'Модуль \''.$module_name.'\' не найден' . PHP_EOL;
			}
			if ($ie){
				$ya_disk->delete_file('/sync/'.basename($json_path));
				$ya_disk->upload_file($json_path, '/sync');
			}
			if (!$ie){
				echo 'Интернет не доступен' . PHP_EOL;
			}
			return true;
		}
	} else {
		echo 'Введите Модуль' . PHP_EOL;
	}
});
$pip->setDescription('del', 'удаляет на компе из списка и заливает на диск по возможности');

$allvars = get_defined_vars();
$pip->command('add', function($module_name, $json, $json_path) use ($allvars) {
	extract($allvars);
	if (strlen($module_name) > 0 && $module_name != ''){
		if (!in_array($module_name, $json)){
			$json[] = $module_name;
			echo 'module add: ' . $module_name . PHP_EOL;
		} else {
			echo 'module exist: ' . $module_name . PHP_EOL;
			return false;
		}
		file_put_contents($json_path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
	} else {
		echo 'Введите Модуль' . PHP_EOL;
	}
	if ($ie){
		$ya_disk->delete_file('/sync/'.basename($json_path));
		$ya_disk->upload_file($json_path, '/sync');
	}
	if (!$ie){
		echo 'Интернет не доступен' . PHP_EOL;
	}
	return true;
});
$pip->setDescription('add', 'Добавляет на комп в список и заливает на диск по возможности');

$allvars = get_defined_vars();
$pip->command('rm', function($module_name, $json, $json_path) use ($allvars) {
	extract($allvars);
	if ($ie){
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
	} else {
		echo 'Интернет не доступен' . PHP_EOL;
	}
});
$pip->setDescription('rm', 'Удаляет модуль с диска');

$allvars = get_defined_vars();
$pip->on('uninstall', function($module_name, $json, $json_path) use ($allvars) {
	extract($allvars);
	if (strlen($module_name) > 0 && $module_name != ''){
		if ($pip->is_install($module_name)){
			if (!empty($json)){
				for ($i = 0; $i < count($json); $i++) { 
					$name = $json[$i];
					if ($name == $module_name){
						unset($json[$i]);
						echo 'module uninstall: ' . $module_name . PHP_EOL;
						file_put_contents($json_path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
						break;
					}
				}
				if ($ie){
					$ya_disk->delete_file('/sync/'.basename($json_path));
					$ya_disk->upload_file($json_path, '/sync');
				} else {
					echo 'Интернет не доступен' . PHP_EOL;
				}
				return true;
			}
		} else {
			echo 'Модуль не установлен' . PHP_EOL;
		}
	} else {
		echo 'Введите Модуль' . PHP_EOL;
	}
});
$pip->setDescription('uninstall', 'Удаляет модуль и заливает');

$allvars = get_defined_vars();
$pip->stabilize = function(string $pattern, $names, $spaces = 3) use ($allvars) {
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
		if (array_key_exists($name, $pip->descriptions) && array_key_exists($before, $pip->descriptions)){
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
		if (array_key_exists($name, $pip->descriptions) && array_key_exists($before, $pip->descriptions)){
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
			$newpattern = str_replace('%desc', $pip->getDescription($name), $newpattern);
			$List[] = $newpattern;
		}
	}
	return $List;
};

$allvars = get_defined_vars();
$pip->command('_h', function($module_name, $json, $json_path) use ($allvars) {
	extract($allvars);
	$all_list = array_merge($pip->command_list, $pip->on_list);
	// echo $manager_name.'x manager for '.$manager_name . PHP_EOL . PHP_EOL;
	echo $pip->manager_name.'x <command> [options]' . PHP_EOL;
	echo PHP_EOL;
	$texts = $pip->stabilize('- %name%s%desc', $all_list);
	foreach ($texts as $text) { 
		echo $text . PHP_EOL;
	}
});

$pip->other(function ($manager_name, $args) {
	$command = $manager_name.' '.implode(' ', $args);
	system($command);
	// echo $command . ' not found' . PHP_EOL;
});