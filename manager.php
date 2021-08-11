<?php

class terminal_manager {
	public $command_list = [];

	function __construct($manager_name, $args){
		unset($args[0]);
		$this->manager_name = $manager_name;
		$this->args = $args;
	}

	public function command(string|array $command_name, callable $func){
		$first = $this->args[1];
		$command = $this->manager_name.' '.implode(' ', $this->args);
		$json_file = __DIR__."/json/{$this->manager_name}_modules.json";
		if (file_exists($json_file)){
			$npm_json = json_decode(file_get_contents($json_file), true);
		} else {
			file_put_contents($json_file, '');
			$npm_json = [];
		}
		$module_name = '';
		for ($i = 2; $i <= count($this->args); $i++){
			if (!str_starts_with($this->args[$i], '-')){
				$module_name = $this->args[$i];
				break;
			}
		}

		if ($module_name == ''){
			$module_name = '"not found"';
		}

		if (is_string($command_name)){
			if ($first == $command_name){
				$func($module_name, $npm_json, $json_file);
			}
		} elseif (is_array($command_name)) {
			if (in_array($first, $command_name)){
				$func($module_name, $npm_json, $json_file);
			}
		}
		if (!in_array($command_name, $this->command_list))
			$this->command_list[] = $command_name;
	}

	public function on(string|array $command_name, callable $func){
		$first = $this->args[1];
		$command = $this->manager_name.' '.implode(' ', $this->args);
		$json_file = __DIR__."/json/{$this->manager_name}_modules.json";
		if (file_exists($json_file)){
			$npm_json = json_decode(file_get_contents($json_file), true);
		} else {
			file_put_contents($json_file, '');
			$npm_json = [];
		}
		
		for ($i = 2; $i <= count($this->args); $i++){
			if (!str_starts_with($this->args[$i], '-')){
				$module_name = $this->args[$i];
				break;
			}
		}

		if (is_string($command_name)){
			if ($first == $command_name){
				$res = $func($module_name, $npm_json, $json_file);
				if ($res)
					echo $command . PHP_EOL;
				// system($command);
			}
		} elseif (is_array($command_name)) {
			if (in_array($first, $command_name)){
				$res = $func($module_name, $npm_json, $json_file);
				if ($res)
					echo $command . PHP_EOL;
				// system($command);
			}
		}
	}
}