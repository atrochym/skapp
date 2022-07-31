<?php

spl_autoload_register('classLoader');

function classLoader(string $className): void
{
	$classFile = "class/$className.class.php";

	if (!file_exists($classFile))
	{
		throw new Exception('classLoader: missing class '.$className);
	}

	require_once($classFile);
	// return;
}


function nameOfDay($date) {
	switch(date('w', strtotime($date))){
		case 0 : return "nd"; break;
		case 1 : return "pn"; break;
		case 2 : return "wt"; break;
		case 3 : return "śr"; break;
		case 4 : return "cz"; break;
		case 5 : return "pt"; break;
		case 6 : return "sb"; break;
	}
}

function longDate($datetime, $showTime=false) {
	$datetime = strtotime($datetime);
	$today = date('Ymd');
	$time = '';
	
	if ($today == date('Ymd', $datetime)) {
		$date = 'dziś, '.date('H:i', $datetime);
		// $time = date('d.m.Y', $datetime);

	} elseif (--$today == date('Ymd', $datetime)) {
		$date = 'wczoraj, '.date('H:i', $datetime);
		// $time = date('d.m.Y', $datetime);

	} else {
		$date = nameOfDay($datetime) . ', '. date('d.m.Y', $datetime);
		$time = date('H:i', $datetime);  
	}

	if($showTime) {
		return $date.' '.$time;
	}
	return $date;
}

function workerLoggedIn()
{
	if (isset($_SESSION['workerId']) && (int)$_SESSION['workerId'] > 0)
	{
		return true;
	}
	
	return false;
}

function labelIdToName($labelCode) { // sprawdzić czy użwana, usunąć
	switch($labelCode) {
		case '10';
			$label['name'] = 'Czeka na części';
			$label['id'] = '10';
			break;
		case '11';
			$label['name'] = 'Czeka na decyzję';
			$label['id'] = '11';
			break;
		case '12';
			$label['name'] = 'Usługa ekspres';
			$label['id'] = '12';
			break;
		case '13';
			$label['name'] = 'Reklamacja';
			$label['id'] = '13';
			break;
		default;
			$label['name'] = 'Unknown label';
			$label['id'] = '0';
	}

	return $label;
}

function formBackup($field = '') { // do usunięcia, sprawa dla localstorage
	if ($field) {
		$value = isset($_SESSION['form_backup'][$field]) ? $_SESSION['form_backup'][$field] : '';
		unset($_SESSION['form_backup'][$field]);
		return $value;
	}

	unset($_SESSION['form_backup']);

	foreach ($_POST as $key => $value) {
		if ($key == 'worker_password') {
			continue;
		}
		$_SESSION['form_backup'][$key] = $value;
	}
}

function v(mixed $in, string $description = null) {
	if (debugMode() == false) {
		return;
	}	

	echo '<br>';
	echo !$description ? '' : $description.' :: ';

	if (!array($in))
	{
		switch($in) {
			case 'p':
				$in = $_POST;
				$description = 'POST dump';
				break;
			case 'g':
				$in = $_GET;
				$description = 'GET dump';
				break;
			case 's':
				$in = $_SESSION;
				$description = 'SESSION dump';
				break;
		}
	}

	if (!array($in))
	{
		var_export($in);
		return;
	}

	echo '<pre>', print_r($in), '</pre>';
}

function ve($in, $description = false)
{
	v($in, $description);
	echo '<br><< exit >>';
	exit;
}

function e(string $string = '{empty}', string $description = null)
{
	if (debugMode() == false) {
		return;
	}

	echo '<br>';
	echo !$description ? '' : $description.' :: ';
	echo $string;
}

function ee(string $string, string $description = null)
{
	e($string, $description);
	echo '<br><< exit >>';
	exit;
}

// =========== TYMCZASOWE

function workerPermit(string $permission): bool
{
	if (!isset($_SESSION['permission'][$permission]))
	{
		exit('workerPermit :: permission missing '. $permission);
	}
	
	return $_SESSION['permission'][$permission];
}

function testworkerPermit(string $permission): bool
{
	if (!isset($_SESSION['permission'][$permission]))
	{
		exit('workerPermit :: permission missing '. $permission);
	}

	if (!$_SESSION['permission'][$permission])
	{
		setMessage('warn::Do wykonania tej akcji potrzebujesz uprawnień.');
		header('Location: ' . DIR . $_SESSION['locationUrl']['previous']);
		exit;
	}

	return $_SESSION['permission'][$permission];
}


function getFromSession(string $key)
{
	if (!isset($_SESSION[$key]))
	{
		throw new Exception("Session :: key '$key' not exist");
		exit;
	}

	return $_SESSION[$key];
}

function setToSession(string $key, mixed $value)
{
	$_SESSION[$key] = $value;
}

function getFromCookie(string $cookie): string|null
{
	if (!isset($_COOKIE[$cookie]))
	{
		return null;
	}

	$cookie = $_COOKIE[$cookie];
	$validate = new Validate;
	$validate->add('cookie', $cookie, 'required text');
	
	return $validate->check() ? $validate->cookie : null;
}

function logToFile(string $data, string $file = 'main.log')
{
	$resource = fopen('log/'.$file, 'a+');
	$workerId = isset($_SESSION['workerId']) ? $_SESSION['workerId'] : 0;

	if (!$resource)
	{
		throw new Exception('Log file :: file open failed.');
		exit;
	}
	// $size = filesize($file);
	if (!$resource)
	{
		throw new Exception('Log file :: wrong file size.');
		exit;
	}
	// $read = fread($resource, $size);
	$body = date('Y-m-d H:i:s') . "\t" . $workerId . "\t" . $data;

	if (!fwrite($resource, "$body\n"))
	{
		throw new Exception('Log file :: write file failed.');
		exit;
	}
	fclose($resource);
}

function setMessage(string $message)
{
	$_SESSION['message'] = $message;
}

function getMessage()
{
	if (isset($_SESSION['message']))
	{
		$message = $_SESSION['message'];
		unset($_SESSION['message']);
		return $message;
	}
}

// przeniesione do Worker
// function getWorkersList(Database $db)
// {
// 	if (!isset($_SESSION['workersList']))
// 	{
// 		$_SESSION['workersList'] = $db->run('SELECT id, name FROM workers')->fetchAll(PDO::FETCH_KEY_PAIR);
// 	}

// 	return $_SESSION['workersList'];
// }

function filterDevicesDuplicates(array $devices)
{
	$devicesIds = [];
	$newArray = [];

	foreach ($devices as $device)
	{
		if (in_array($device['device_id'], $devicesIds))
			continue;

		$devicesIds[] = $device['device_id'];
		$newArray[] = $device;
		
	}

	return $newArray;
}

function parseUserAgent(string $userAgent = null)
{
	$userAgent = $userAgent ?: $_SERVER['HTTP_USER_AGENT'];
	$userAgentHash = sha1($userAgent);

	if (isset($_SESSION['userAgent']) && $userAgentHash == $_SESSION['userAgent']['hash'])
	{
		// setToSession('userAgent', ['restored' => true]); // nie działa jak należy w tym wypadku (nadpisuje)
		$_SESSION['userAgent']['restored'] = true;
		return getFromSession('userAgent');
	}

	require 'tmp/uadb.php';

	$timer = new Timer;
	$timer->start('executionTime');
	$timer->start('browser');
	$browser = null;
	$platform = null;
	$bIterations = 0;

	$userAgent = strip_tags($userAgent);
	$userAgent = htmlspecialchars($userAgent);
	$userAgent = str_replace('_', '.', $userAgent);
	$userAgentLower = strtolower($userAgent);

	// find browser
	foreach ($userAgentDB['browsers'] as $key => $browser)
	{
		if (str_contains($userAgentLower, $key))
		{
			$browser = $userAgentDB['browsers'][$key];
			$key = str_replace('/', '-', $key); // dla preg_match i Trident/6 itp
			break;
		}
		$bIterations++;
	}

	if (!$browser)
	{
		$browser = end($userAgentDB['browsers']);
		$browserVersion = '';
	}
	else
	{
		$regex = '/' .$key. '.?(\d+\.?\d{0,1})|(?:version.)(\d+.\d+)/i';
		preg_match_all($regex, $userAgent, $version);

		$browserVersion = !empty($version[1][0]) ? (float) $version[1][0] : '';
		$browserVersion = !empty($version[1][1]) ? (float) $version[1][1] : $browserVersion;
		$browserVersion = !empty($version[2][1]) ? (float) $version[2][1] : $browserVersion;

		if ($key == 'safari' && !empty($version[2][0]))
		{
			$browserVersion = (float) $version[2][0];
		}
	}

	$result = [
		'browser' => $browser . ' '. $browserVersion,
		'browserName' => $browser,
		'browserVersion' => $browserVersion,
		'browserTime' => $timer->end('browser'),
		'browserIterations' => $bIterations,
	];

	// find platform
	if (str_contains($userAgentLower, 'windows'))
	{
		$timer->start('platform');
		$regex = '/Windows NT .*?\d\.\d|Windows \w{2}|Win\d{2}/i';
		
		preg_match($regex, $userAgent, $match);
		$platform = isset($match[0]) && isset($userAgentDB['windowsOS'][$match[0]]) ? $userAgentDB['windowsOS'][$match[0]] : end($userAgentDB['windowsOS']);

	}
	elseif (str_contains($userAgentLower, 'like mac os x') || str_contains($userAgentLower, 'ios'))
	{
		$timer->start('platform');

		$regex = '/(?:\()(.*?)(?:;)|(?:os )(\d+)/i';
		//[0] pierwsze dopasowania
		//[1] drugie dopasowania
		preg_match_all($regex, $userAgent, $match, PREG_SET_ORDER);

		$version = isset($match[1][2]) ? $match[1][2] : '';
		$platform = $match[0][1] . ' iOS ' . $version;

	}
	elseif (str_contains($userAgentLower, 'mac os x'))
	{
		$timer->start('platform');

		$regex = '/Mac OS X.*? \d+.\d+/i';
		preg_match($regex, $userAgent, $match);

		$platform = isset($match[0]) && isset($userAgentDB['macOS'][$match[0]]) ? $userAgentDB['macOS'][$match[0]] : end($userAgentDB['macOS']);

	}
	elseif (str_contains($userAgentLower, 'android'))
	{
		$timer->start('platform');

		$userAgent = str_replace(';', '', $userAgentLower);
		$regex = '/android \d*\.*?\d/i';
		preg_match($regex, $userAgent, $match);

		$platform = isset($match[0]) && isset($userAgentDB['androidOS'][$match[0]]) ? $userAgentDB['androidOS'][$match[0]] : end($userAgentDB['androidOS']);

	}
	elseif (str_contains($userAgentLower, 'linux'))
	{
		$timer->start('platform');

		foreach ($userAgentDB['linuxOS'] as $key => $linux)
		{
			if (str_contains($userAgentLower, $key))
			{
				$platform = $userAgentDB['linuxOS'][$key];
				break;
			}
		}
		$regex = '/' .$key. '.(\d+.\d+)/i';
		preg_match($regex, $userAgent, $version);
		$version = isset($version[1]) ? $version[1] : '';

		$platform = $platform . ' ' . $version;
	}
	else
	{
		$timer->start('platform');

		foreach ($userAgentDB['otherOS'] as $key => $other)
		{
			if (str_contains($userAgentLower, $key))
			{
				$platform = $userAgentDB['otherOS'][$key];
				break;
			}
		}

		$platform = $platform ?: end($userAgentDB['otherOS']);
	}

	$result += [
		'platform' => $platform,
		'platformTime' => $timer->end('platform'),
		'executionTime' => $timer->end('executionTime'),
		'hash' => $userAgentHash,
	];
	setToSession('userAgent', $result);
	return $result;
}

// function fingerprint(string $input, string $fingerprint = null): string|bool // na razie nie używam
// {
// 	if (!$fingerprint)
// 	{
// 		$int = random_int(268435456, 4294967295);
// 		$salt = dechex($int);
	
// 		return substr(sha1($input . $int), 0, 32) . $salt;
// 	}

// 	if (strlen($fingerprint) !== 40)
// 	{
// 		return false;
// 	}

// 	$hex = substr($fingerprint, 32);
// 	if (!hexdec($hex))
// 	{
// 		return false;
// 	}
// 	$salt = hexdec($hex);

// 	$inputHash = substr(sha1($input . $salt), 0, 32);

// 	if ($inputHash . $hex == $fingerprint)
// 	{
// 		return true;
// 	}

// 	return false;
// }


function maskWorkerId(): string
{
	$workerId = getFromSession('workerId');
	$rand = random_int(65536, 1048575);
	$randHex = dechex($rand);
	$workerIdMask = dechex(substr($rand, 0, 4) + $workerId);
	$result = $workerIdMask . $randHex;
	$hash = substr(sha1($result), 2, 10);

	return $result . $hash;
}

function unmaskWorkerId($workerIdMask): int|false
{
	$hash = substr($workerIdMask, -10);
	$workerIdMask = substr($workerIdMask, 0, -10);

	if (substr(sha1($workerIdMask), 2, 10) == $hash)
	{
		$rand = hexdec(substr($workerIdMask, -5));
		$workerIdMask = hexdec(substr($workerIdMask, 0, -5));
		$workerId = $workerIdMask - substr($rand, 0, 4);

		if (is_int($workerId))
		{
			return $workerId;
		}
	}

	return false;
}

// function getWorkerId(string $identifier): int
// {
// 	$int = hexdec(substr($identifier, 0, 5));
// 	$divider = substr($int, 0, 3);
// 	$workerId = hexdec(substr($identifier, 15));

// 	return $workerId / $divider;
// }

// function checkWorkerId(Database $db, string $identifier): bool
// {
// 	$int = hexdec(substr($identifier, 0, 5));
// 	$divider = substr($int, 0, 3);
// 	$workerId = hexdec(substr($identifier, 15)) / $divider;

// 	$worker = $db->run('SELECT * FROM workers WHERE id = :id LIMIT 1', $workerId)->fetch();

// 	if ($worker)
// 	{
// 		$loginHash = substr(sha1($worker['login'] . $int), 0, 10);

// 		if (substr($identifier, 5, 10) == $loginHash)
// 		{
// 			return true;
// 		}

// 		// return substr($identifier, 5, 10) == $loginHash ?: false;
// 	}

// 	return false;
// }

function makeAuth(): string
{
	$workerLogin = getFromSession('workerLogin');
	$securityToken = getFromSession('workerSecurityToken');

	$rand = random_int(65536, 1048575);
	$randhex = dechex($rand);
	$loginHash = substr(sha1($workerLogin . $securityToken . $rand), 0, 25);
	// $securityToken = substr(sha1($workerSecurityToken . $rand), 0, 15);
	$result = $loginHash . $randhex;
	$checksum = substr(sha1($result), 5, 10);

	return $result . $checksum;
}

function checkAuth(Database $db, $auth): bool
{
	$checksum = substr($auth, -10);
	$auth = substr($auth, 0, -10);

	if (substr(sha1($auth), 5, 10) == $checksum)
	{
		$workerId = unmaskWorkerId('17918fdc9b1d64762ad');

		if ($workerId)
		{
			$randHex = substr($auth, 20);
			$rand = hexdec($randHex);
			$worker = $db->run('SELECT login, security_token FROM workers WHERE id = :id LIMIT 1', $workerId)->fetch();

			if ($worker)
			{
				$loginHash = substr(sha1($worker['login'] . $worker['security_token'] . $rand), 0, 25);
				// $securityToken = substr(sha1($worker['security_token'] . $rand), 0, 15);
				$result = $loginHash . $randHex;

				if ($result == $auth)
				{
					return true;
				}
			}
		}
	}

	return false;
}

// function deviceFingerprint(string $fingerprint = null): string|bool
// {
// 	$ip = getIP();
// 	$uagent = parseUserAgent();
// 	$device = $ip . $uagent['platform'] . $uagent['browser'];

// 	if (!$fingerprint)
// 	{
// 		$rand = random_int(268435456, 4294967295);
// 		$randHex = dechex($rand);
// 		$result = substr(sha1($device . $rand), 0, 32) . $randHex;
// 		$checksum = substr((sha1($result)), 5, 10);
	
// 		return $result . $checksum;
// 	}

// 	$checksum = substr($fingerprint, -10);
// 	$fingerprint = substr($fingerprint, 0, -10);

// 	if (substr((sha1($fingerprint)), 5, 10) == $checksum)
// 	{
// 		$randHex = substr($fingerprint, 32);
// 		$rand = hexdec($randHex);
// 		if ($rand)
// 		{
// 			$compare = substr(sha1($device . $rand), 0, 32) . $randHex;

// 			if ($compare == $fingerprint)
// 			{
// 				return true;
// 			}
// 		}
// 	}

// 	return false;
// }

function newDeviceFingerprint(): string
{
	$ip = getIP();
	$uagent = parseUserAgent();
	$device = $ip . $uagent['platform'] . $uagent['browser'];

	$rand = random_int(268435456, 4294967295);
	$randHex = dechex($rand);
	$result = substr(sha1($device . $rand), 0, 32) . $randHex;
	$checksum = substr((sha1($result)), 5, 10);

	return $result . $checksum;
}

function checkDeviceFingerprint(string $fingerprint): bool
{
	$ip = getIP();
	$uagent = parseUserAgent();
	$device = $ip . $uagent['platform'] . $uagent['browser'];
	$checksum = substr($fingerprint, -10);
	$fingerprint = substr($fingerprint, 0, -10);

	if (substr((sha1($fingerprint)), 5, 10) == $checksum)
	{
		$randHex = substr($fingerprint, 32);
		$rand = hexdec($randHex);
		if ($rand)
		{
			$compare = substr(sha1($device . $rand), 0, 32) . $randHex;

			if ($compare == $fingerprint)
			{
				return true;
			}
		}
	}

	return false;
}


function getIP(): string|false
{
	$remoteAddr = $_SERVER['REMOTE_ADDR'];

	$ip = filter_var($remoteAddr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);

	if (!$ip)
	{
		throw new Exception('getIP :: invalid remote addr');
	}
	return $ip;
}


function makeDate()
{
	$hour = date('G');
	$year = substr(date('y'), -1);
	$hour = $hour > 9 ? $hour : "3$hour";

	return $hour . $year . date('z');
}

function url(string $url): string
{
	// if (strpos($url, '/'))
	// {
		$explode = explode('/', trim($url, '/'));
		$module = isset($explode[0]) ? $explode[0] : '';
		$id = isset($explode[1]) ? $explode[1] : $url;
	// }
	// else
	// {
	// 	$module = 'form';
	// 	$id = $url;
	// }

	$workerId = getFromSession('workerId');
	$date = makeDate();
	$rand = random_int(256, 4095);

	$randHex = str_split(dechex($rand));
	$integer1 = (int) substr($rand, 0, 3);
	$integer2 = (int) substr($rand, 0, 2);

	$idHex = dechex($id + $integer1);
	$idLength = strlen($idHex);

	$workerIdHex = dechex($workerId + $integer2);
	$workerIdLength = strlen($workerIdHex);
	
	$dateMask = ($date + $rand) ;
	$dateHex = dechex($dateMask);

	$result = $idHex . $workerIdHex . $dateHex;
	$offset = (string) $rand;

	$result = str_split($result);

	array_splice($result, $offset[0] - 1, 0, $idLength);
	array_splice($result, $offset[1], 0, $workerIdLength);

	$randOffset = hexdec($result[0]);
	$randOffset = count($result) < $randOffset ? round($randOffset / 2) : $randOffset;

	array_splice($result, $randOffset, 0, $randHex);
	$finish = implode($result);
	$hash = substr(hash('crc32', $finish . $module), 0, 3);
	// $finish = $finish . $hash;
	$explode[1] = $finish . $hash;

	return '/'. implode('/', $explode);
}

function maskId(int $id): string
{
	$rand = random_int(4095, 65535);

	$randHex = dechex($rand);
	$addInt = (int) substr($rand, 0, 3);

	$id = dechex($addInt + $id);
	$result = $randHex . $id;

	$hash = hash('crc32', $result);
	$finish = $result . $hash;

	return $finish;
}

function unmaskId(string $maskId): false|int
{
	$hash = substr($maskId, -8);
	$maskId = substr($maskId, 0, -8);
	
	if (hash('crc32', $maskId) == $hash)
	{
		$rand = hexdec(substr($maskId, 0, 4));	
		$substract = (int) substr($rand, 0, 3);
		$id = hexdec(substr($maskId, 4));
		$result = $id - $substract;

		if (is_int($result))
		{
			return $result;
		}
	}

	return false;
}



// function maskId(int $id): string
// {
// 	$rand = random_int(4095, 65535);

// 	$randHex = str_split(dechex($rand));
// 	$addInt = (int) substr($rand, 0, 3);

// 	$id = dechex($addInt + $id);
// 	$result = $randHex . $id;
// 	$result = str_split($result);

// 	$randOffset = hexdec($result[0]);
// 	$randOffset = count($result) < $randOffset ? round($randOffset / 2) : $randOffset;

// 	array_splice($result, $randOffset, 0, $randHex);
// 	$finish = implode($result);
// 	$hash = substr(hash('crc32', $finish), 0, 3);
// 	$finish = $finish . $hash;

// 	return $finish;
// }


// function maskId(int $id, int $workerId = null): string
// {
// 	$workerId = getFromSession('workerId');
// 	$date = makeDate();
// 	$rand = random_int(256, 4095);

// 	$randHex = str_split(dechex($rand));
// 	$integer1 = (int) substr($rand, 0, 3);
// 	$integer2 = (int) substr($rand, 0, 2);

// 	$idHex = dechex($id + $integer1);
// 	$idLength = strlen($idHex);

// 	$workerIdHex = dechex($workerId + $integer2);
// 	$workerIdLength = strlen($workerIdHex);
	
// 	$dateMask = ($date + $rand) ;
// 	$dateHex = dechex($dateMask);

// 	$result = $idHex . $workerIdHex . $dateHex;
// 	$offset = (string) $rand;

// 	$result = str_split($result);

// 	array_splice($result, $offset[0] - 1, 0, $idLength);
// 	array_splice($result, $offset[1], 0, $workerIdLength);

// 	$randOffset = hexdec($result[0]);
// 	$randOffset = count($result) < $randOffset ? round($randOffset / 2) : $randOffset;

// 	array_splice($result, $randOffset, 0, $randHex);
// 	$finish = implode($result);
// 	$hash = substr(hash('crc32', $finish), 0, 3);
// 	$finish = $finish . $hash;

// 	return $finish;
// }

// function unmaskId(string $maskId): false|array
// {
// 	$hash = substr($maskId, -3);
// 	$maskId = substr($maskId, 0, -3);
	
// 	if (substr(hash('crc32', $maskId), 0, 3) != $hash)
// 	{
// 		// throw new Exception('idMask hash incorrect');
// 		return false;
// 	}
	
// 	$randOffset = (int) hexdec($maskId[0]);
// 	$randOffset = strlen($maskId) - 3 < $randOffset ? round($randOffset / 2) : $randOffset;
	
// 	$maskId = str_split($maskId);
	
// 	$rand = array_splice($maskId, $randOffset, 3);
// 	$rand = hexdec(implode($rand));
	
// 	$offset = (string) $rand;
// 	$workerIdLength = array_splice($maskId, $offset[1], 1)[0];
// 	$idLength = array_splice($maskId, $offset[0] - 1, 1)[0];

// 	$maskId = implode($maskId);

// 	$integer1 = (int) substr($rand, 0, 3);
// 	$integer2 = (int) substr($rand, 0, 2);
	
// 	$id = hexdec(substr($maskId, 0, $idLength));
// 	$workerId = hexdec(substr($maskId, $idLength, $workerIdLength));
// 	$date = hexdec(substr($maskId, $idLength + $workerIdLength));
	
// 	$result['id'] = $id - $integer1;
// 	$result['worker'] = $workerId - $integer2;
// 	$result['date'] = $date - $rand;

// 	return $result;
// }

function registerDevice(Database $db, string $deviceName = null): false|array
{
	$fingerprint = getFromCookie('fingerprint');

	if (!$fingerprint || !checkDeviceFingerprint($fingerprint))
	{
		$fingerprint = newDeviceFingerprint();
	}

	$workerId = getFromSession('workerId');
	$status = workerPermit('session_manager') ? 'allow' : null;
	$uAgent = parseUserAgent();
	$device = $uAgent['platform'] . ' ' .$uAgent['browser'];

	$values = [
		'worker_id' => $workerId,
		'name' => $deviceName ? $deviceName : '',
		'type' => $device,
		'fingerprint' => $fingerprint,
		'ip' => ip2long(getIP()),
		'status' => $status,
	];
	$deviceId = $db->insert('workers_devices', $values);

	if ($deviceId)
	{
		return [
			'fingerprint' => $fingerprint,
			'id' => $deviceId,
		];
	}

	return false;
}