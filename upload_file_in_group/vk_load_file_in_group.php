<?php
// Запрос на получение токена
//https://oauth.vk.com/authorize?client_id=4543127&display=page&redirect_uri=https://oauth.vk.com/blank.html&scope=photos,messages,docs,status,offline&response_type=token&v=5.71&state=12345

// Ответ сервера
//https://oauth.vk.com/blank.html#access_token=df20874130448a53304fbe7be6a960d1e26208ac5e6f7c1dfbab7a3e412b7de68203d8c70a5f7fc0918a7&expires_in=0&user_id=394091516&state=12345

// Альбом в группе (157777751 - group_id идентификатор группы; 249830060 - album_id идентификатор альбома)
//https://vk.com/club157777751?z=album-157777751_249830060

error_reporting(E_ALL);
date_default_timezone_set('Asia/Novokuznetsk');
define('VK_API_VERSION', '5.71');
define('VK_API_ENDPOINT', 'https://api.vk.com/method/');
define('VK_API_ACCESS_TOKEN', 'df20874130448a53304fbe7be6a960d1e26208ac5e6f7c1dfbab7a3e412b7de68203d8c70a5f7fc0918a7');
define('SCRIPT_BASE_DIRECTORY', './');
define('LOGS_DIRECTORY', SCRIPT_BASE_DIRECTORY.'/logs');

$file_name = 'cat.jpg';		// Загружаемый файл
$album_id = '249830060';	// Идентификатор альбома
$group_id = '157777751';	// Идентификатор группы

$upload_server_response = vkApi_photosGetUploadServer($album_id, $group_id);
$upload_response = vkApi_upload($upload_server_response['upload_url'], $file_name);

$server = $upload_response['server'];
$photos_list = $upload_response['photos_list'];
$aid = $upload_response['aid'];
$hash = $upload_response['hash'];
$gid = $upload_response['gid'];

$save_response = vkApi_photossave($aid, $gid, $server, $photos_list, $hash, 0, 0, 'cat');
$photo = array_pop($save_response);

var_dump($photo);


function vkApi_photosGetUploadServer($album_id, $group_id) {
  return _vkApi_call('photos.getUploadServer', array(
    'album_id' => $album_id,
    'group_id' => $group_id,
  ));
}


function vkApi_photossave($album_id, $group_id, $server, $photos_list, $hash, $latitude, $longitude, $caption) {
  return _vkApi_call('photos.save', array(
    'album_id'    => $album_id,
    'group_id'    => $group_id,
	'server'      => $server,
	'photos_list' => $photos_list,
	'hash'        => $hash,
	'latitude'    => $latitude,
	'longitude'   => $longitude,
	'caption'     => $caption,
  ));
}


function _vkApi_call($method, $params = array()) {
  $params['access_token'] = VK_API_ACCESS_TOKEN;
  $params['v'] = VK_API_VERSION;

  $query = http_build_query($params);
  $url = VK_API_ENDPOINT.$method.'?'.$query;

  $curl = curl_init($url);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  $json = curl_exec($curl);
  $error = curl_error($curl);
  if ($error) {
    log_error($error);
    throw new Exception("Failed {$method} request");
  }

  curl_close($curl);

  $response = json_decode($json, true);
  if (!$response || !isset($response['response'])) {
    log_error($json);
    throw new Exception("Invalid response for {$method} request");
  }

  return $response['response'];
}


function vkApi_upload($url, $file_name) {
  if (!file_exists($file_name)) {
    throw new Exception('File not found: '.$file_name);
  }

  $curl = curl_init($url);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
  curl_setopt($curl, CURLOPT_POST, true);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_POSTFIELDS, array('file' => new CURLfile($file_name)));
  $json = curl_exec($curl);
  $error = curl_error($curl);
  if ($error) {
    log_error($error);
    throw new Exception("Failed {$url} request");
  }

  curl_close($curl);

  $response = json_decode($json, true);
  if (!$response) {
    throw new Exception("Invalid response for {$url} request");
  }

  return $response;
}


function log_error($message) {
  if (is_array($message)) {
    $message = json_encode($message);
  }

  _log_write('[ERROR] ' . $message);
}


function _log_write($message) {
  $trace = debug_backtrace();
  $function_name = isset($trace[2]) ? $trace[2]['function'] : '-';
  $mark = date("H:i:s") . ' [' . $function_name . ']';
  $log_name = LOGS_DIRECTORY.'/log_' . date("j.n.Y") . '.txt';
  file_put_contents($log_name, $mark . " : " . $message . "\n", FILE_APPEND);
}

?>
