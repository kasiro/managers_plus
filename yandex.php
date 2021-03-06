<?php

class yandex_disk_api {
	function __construct($token){
		$this->token = $token;
	}

	public function get_info(){
		$ch = curl_init('https://cloud-api.yandex.net/v1/disk/');
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: OAuth ' . $this->token]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$res = curl_exec($ch);
		curl_close($ch);
		$res = json_decode($res, true);
	
		if (empty($res['error'])) {
			print_r($res);
		}
	}

	/**
	 * @param string $path Файл или папка на Диске.
	 */
	public function file_exist($path){
		$f = $this->get_folders(dirname(path));
		foreach ($f as $el){
			if ($el['name'] == basename(path)){
				return true;
			}
		}
		return false;
	}

	/**
	 * @param string $path Выведем список папки.
	 */
	public function get_folders($path = '/'){		
		// Оставим только названия и тип.
		$fields = '_embedded.items.name,_embedded.items.type';
		$limit = 100;
		$ch = curl_init('https://cloud-api.yandex.net/v1/disk/resources?path=' . urlencode($path) . '&fields=' . $fields . '&limit=' . $limit);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: OAuth ' . $this->token]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$res = curl_exec($ch);
		curl_close($ch);
		$res = json_decode($res, true);
		return $res;
	}

	/**
	 * @param string $file Файл или папка на Компе.
	 * @param string $path Файл или папка на Диске.
	 */
	public function upload_file($file, $path = '/'){
		// Папка на Яндекс Диске (уже должна быть создана).
		// Запрашиваем URL для загрузки.
		$ch = curl_init('https://cloud-api.yandex.net/v1/disk/resources/upload?path=' . urlencode($path.'/'.basename($file)));
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: OAuth ' . $this->token]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$res = curl_exec($ch);
		curl_close($ch);
	
		$res = json_decode($res, true);
		if (empty($res['error'])) {
			// Если ошибки нет, то отправляем файл на полученный URL.
			$fp = fopen($file, 'r');
			$ch = curl_init($res['href']);
			curl_setopt($ch, CURLOPT_PUT, true);
			curl_setopt($ch, CURLOPT_UPLOAD, true);
			curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file));
			curl_setopt($ch, CURLOPT_INFILE, $fp);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_exec($ch);
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			if ($http_code == 201) {
				echo basename($file).' успешно загружен на Сервер (Диск)' . PHP_EOL;
			}
		}
	}

	/**
	 * @param string $path Файл или папка на Компе.
	 * @param string $yd_file Файл или папка на Диске.
	 */
	public function download_file($path, $yd_file){
		$ch = curl_init('https://cloud-api.yandex.net/v1/disk/resources/download?path=' . urlencode($yd_file));
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: OAuth ' . $this->token]);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$res = curl_exec($ch);
		curl_close($ch);
		$res = json_decode($res, true);
		if (empty($res['error'])) {
			$file_name = $path . '/' . basename($yd_file);
			file_put_contents($file_name, file_get_contents($res['href']));
		}
	}

	/**
	 * Файл или папка на Диске.
	 * @param string $path 
	 */
	public function delete_file($path){
		$ch = curl_init('https://cloud-api.yandex.net/v1/disk/resources?path=' . urlencode($path) . '&permanently=true');
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: OAuth ' . $this->token]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$res = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if (in_array($http_code, [202, 204])) {
			echo basename($path).': Успешно удалено' . PHP_EOL;
		}
	}
	
}