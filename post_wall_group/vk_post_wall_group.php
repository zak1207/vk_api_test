<?php
// Запрос на получение токена
//https://oauth.vk.com/authorize?client_id=4543127&display=page&redirect_uri=https://oauth.vk.com/blank.html&scope=photos,messages,wall,docs,status,offline&response_type=token&v=5.71&state=12345

// Ответ сервера
//https://oauth.vk.com/blank.html#access_token=df20874130448a53304fbe7be6a960d1e26208ac5e6f7c1dfbab7a3e412b7de68203d8c70a5f7fc0918a7&expires_in=0&user_id=394091516&state=12345

// Ваш паблик (157777751 - group_id идентификатор группы)
//https://vk.com/club157777751

error_reporting(E_ALL);
date_default_timezone_set('Asia/Novokuznetsk');
define('VK_API_VERSION', '5.71');
define('VK_API_ENDPOINT', 'https://api.vk.com/method/');
define('VK_API_ACCESS_TOKEN', 'df20874130448a53304fbe7be6a960d1e26208ac5e6f7c1dfbab7a3e412b7de68203d8c70a5f7fc0918a7');
define('SCRIPT_BASE_DIRECTORY', './');
define('LOGS_DIRECTORY', SCRIPT_BASE_DIRECTORY.'/logs');

$file_name = 'mountain_paradise.jpg';		// Загружаемый файл
$group_id = '157777751';					// Идентификатор группы
$message = 'Хорошего настроения :)';		// Сообщение

$upload_server_response = vkApi_photosgetWallUploadServer($group_id);
$upload_response = vkApi_upload($upload_server_response['upload_url'], $file_name);

$photo = $upload_response['photo'];
$server = $upload_response['server'];
$hash = $upload_response['hash'];

$save_response = vkApi_photossaveWallPhoto($group_id, $photo, $server, $hash, 'Mountain paradise');
$photo_attach = array_pop($save_response);

$attachments = array(
   'photo'.$photo_attach['owner_id'].'_'.$photo_attach['id']
);

$post_response = vkApi_wallpost('-'.$group_id, 0, 1, $message, $attachments);

var_dump($post_response);


function vkApi_photosgetWallUploadServer($group_id) {
  return _vkApi_call('photos.getWallUploadServer', array(
    'group_id' => $group_id,
  ));
}


function vkApi_photossaveWallPhoto($group_id, $photo, $server, $hash, $caption) {
  return _vkApi_call('photos.saveWallPhoto', array(
    'group_id'  => $group_id,
    'photo' => $photo,
	'server' => $server,
	'hash' => $hash,
	'caption' => $caption,
  ));
}


function vkApi_wallpost($owner_id, $friends_only, $from_group, $message, $attachments) {
  return _vkApi_call('wall.post', array(
    'owner_id'  => $owner_id,
    'friends_only' => $friends_only,
	'from_group' => $from_group,
	'message' => $message,
	'attachments' => $attachments,
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
