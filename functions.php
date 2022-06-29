<?php


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


function genDeviceTag($lastTag) { // do wywalenia
	if ((int)strlen($lastTag) < 5) {
		throw new Exception('genDeviceID not correct');
	}

	$month = substr($lastTag, -4, 2);
	$year = substr($lastTag, -2, 2);
	$deviceTag = substr($lastTag, 0, -4);
	
	$deviceTag = $year <> date('y') ? 1 : $deviceTag;
	$deviceTag = $month <> date('m') ? 1 : ++$deviceTag;
	
	return $deviceTag . date('my');
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

function workerLoggedIn() {
	if (isset($_SESSION['workerId']) && (int)$_SESSION['workerId'] > 0) {
		return true;
	}
	return false;
}

function labelIdToName($labelCode) {
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

function formBackup($field = '') {
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

function v($in, $description=false) {
	if (debugMode() == false) {
		return;
	}

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

	echo !$description ? '' : $description.' ::';
	echo '<pre>', print_r($in), '</pre>';
}

function ve($in, $description=false) {
	if (debugMode() == false) {
		return;
	}

	echo !$description ? '' : $description.' ::';
	echo '<pre>', print_r($in), '</pre>';
	exit();
}

function e($in, $description=false) {
	if (debugMode() == false) {
		return;
	}

	echo !$description ? '' : $description.' ::';
	echo $in.'<br>' ;
}


// =========== TYMCZASOWE

function workerPermit($permission)
{
	if (!isset($_SESSION['permission'][$permission]))
	{
		exit('workerPermit :: permission missing '. $permission);
	}
	
	return (bool) $_SESSION['permission'][$permission];
}

function testworkerPermit($permission)
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
}


function getFromSession(string $key)
{
	if (!isset($_SESSION[$key]))
	{
		throw new Exception('Session :: key not exist');
		exit;
	}

	return $_SESSION[$key];
}

function setToSession(string $key, mixed $value)
{
	$_SESSION[$key] = $value;
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

// function redirect($destination)
// {
// 	if ($destination == 'back')
// 	{
// 		//  kosnstruktorze kontrolera więcej o tej zmiennej
// 		header($_SESSION['locationUrl']['previous']);
// 		exit;
// 	}

// 	header('Location: ' . DIR . $destination);
// 	exit;
// }

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


function getWorkersList(Database $db)
{
	if (!isset($_SESSION['workersList']))
	{
		$_SESSION['workersList'] = $db->run('SELECT id, name FROM workers', [])->fetchAll(PDO::FETCH_KEY_PAIR);
	}

	return $_SESSION['workersList'];
}

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

?>