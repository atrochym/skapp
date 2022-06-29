<?php

$urlParser = new UrlParser;
$db = new Database;
$message = new Message;


$action = $urlParser->action();
$loadSection = 'default';

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

function updateLastActive() {
	// zrób to jakoś lepiej

	$db = new Database;
	if(isset($_SESSION['worker_id']) && $_SESSION['worker_id'] > 0) {

		$db->query('SELECT * FROM linker_users WHERE w_id= :id LIMIT 1');
		$db->bind(':id', $_SESSION['worker_id']);
		$count = $db->rowCount();

		if($count > 0) {
			$db->query('UPDATE linker_users SET last_active=NOW() WHERE w_id= :id');
			$db->bind(':id', $_SESSION['worker_id']);
			$db->rowCount(); // bo execute popsułem...
		}
	}

}

if($action == 'redir'){
	header('Location: https://www.google.com/search?q=studio-komp&ie=UTF-8&oe=UTF-8#lrd=0x470fc27588872515:0x51a85d413a717a8b,3,,,');

	$getSticker = $urlParser->param('s');
	$getWorker = $urlParser->param('w');
	$ip = $_SERVER['REMOTE_ADDR'];
	$userAgent = $_SERVER['HTTP_USER_AGENT'];
	$token = md5('sk-' . $getSticker . $getWorker);
	$count = 0;
	
	if(isset($_COOKIE['token'])) {
		$cookie = explode('-', $_COOKIE['token']);

		$db->query('SELECT * FROM linker WHERE id= :id LIMIT 1');
		$db->bind(':id', $cookie[0]);// bo execute popsułem...
		$data = $db->fetch();
		$count = ++$data['count'];

	} 
		$db->query('INSERT INTO linker (id, sticker, worker_id, ip, user_agent, count) VALUES (NULL, :sticker, :worker_id, :ip, :user_agent, :count)');
		$data = array(
			'sticker' => $getSticker,
			'worker_id' => $getWorker,
			'ip' => $ip,
			'user_agent' => $userAgent,
			'count' => $count
		);
		$db->execute($data);
		$newID = $db->lastInsertId2();
		setcookie('token', $newID.'-'.$token, time()+(3600*24*365));

		

	// echo 'redir';
	// header('Location: https://www.google.com/search?q=studio-komp&ie=UTF-8&oe=UTF-8#lrd=0x470fc27588872515:0x51a85d413a717a8b,3,,,');
	exit;

} elseif($action == 'admin') {
	$loadSection = 'logon_form';


} elseif($action == 'register') {
	$loadSection = 'register_form';


} elseif($action == 'main') {
	if(isset($_SESSION['worker_id']) && $_SESSION['worker_id'] > 0) {
		$db->query('SELECT * FROM linker_users WHERE w_id= :id LIMIT 1');
		$db->bind(':id', $_SESSION['worker_id']);
		$count = $db->rowCount();

		if($count > 0) {
			updateLastActive();
			$loadSection = 'main';
		}        
	} else {
		$message->add('Zaloguj się bo nic nie zobaczysz.');
		$loadSection = 'logon_form';
	}





} elseif($action == 'login') {    
	$login = $_POST['worker_login'];
	$password = $_POST['worker_password'];
	$ip = $_SERVER['REMOTE_ADDR'];


	$db->query('SELECT * FROM linker_users WHERE w_login= :login AND w_password=SHA2(:password, 256) LIMIT 1');
	$data = array(
		'login' => $login,
		'password' => $password
	);
	$db->execute($data);
	$worker = $db->fetch();

	if($worker['w_id'] < 1) {
		$message->add('Login lub hasło nieprawidłowe.');
		redirect('linker/admin');
	}

	$db->query('INSERT INTO linker_log (l_id, worker_id, ip) VALUES (NULL, :worker_id, :ip)');
	$data = array(
		'worker_id' => $worker['w_id'],
		'ip' => $ip
	);
	$db->execute($data);

	$_SESSION['worker_id'] = $worker['w_id'];
	$_SESSION['worker_login'] = $worker['w_login'];

	updateLastActive();
	redirect('linker/main');

} elseif($action == 'create') {

	$login = $_POST['worker_login'];
	$password = $_POST['worker_password'];
	$passwordRepeat = $_POST['worker_password_repeat'];
	$ip = $_SERVER['REMOTE_ADDR'];

	$worker = $db->query('SELECT * FROM linker_users WHERE w_login= :login LIMIT 1');
	$worker = $db->bind(':login', $login);
	$count = $db->rowCount();

	if($count > 0) {
		$message->add('Takie konto istnieje.');
		redirect('linker/register');
	}

	if($password !== $passwordRepeat) {
		$message->add('Te hasła nie są identyczne.');
		redirect('linker/register');
	}

	$db->query('INSERT INTO linker_users (w_id, w_login, w_password, ip) VALUES (NULL, :login, SHA2(:password, 256), :ip)');
	$data = array(
		'login' => $login,
		'password' => $password,
		'ip' => $ip
	);
	$db->execute($data);

	if ($db->rowCount2() > 0) { // bo rowCpunt2 zawiera od razu execute....
		$message->add('Konto zostało założone, zaloguj się.');
		redirect('linker/admin');
	}
} elseif($action == 'logout') {
	updateLastActive();
	session_destroy();
	session_status();
	$message->add('Wylogowano.');
	$loadSection = 'logon_form';
}

?>

<!DOCTYPE html>
<html>

<head>
	<title>studio-komp linker</title>
	<link rel="stylesheet" href="http://ivybe.ddns.net/sk/src/main.css">
	<link rel="stylesheet" href="http://ivybe.ddns.net/sk/src/font-awesome.min.css">

	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@800&display=swap" rel="stylesheet">

	<style>
		.user-row:hover {
			background-color: #1B5572;
			border-radius: 3px;
			border: 1px solid #000;
		}
	</style>
</head>

<body style="margin:revert;">

	
		<?php if($loadSection == 'logon_form'):   ?>
		<div style="position: absolute; top: 30%; left: 50%; margin-right: -50%; transform: translate(-50%, 0%) ">
		<div style="width:696px; height:16px; background-color: #005279; padding: 8px 17px;">
			<span style="font: 13px Arial; color: #fff;">SK :: Logowanie do linkera</span>
		</div>
		<div style="position:absolute; width:730px; height:150px; padding-top:30px; background-color: #2E373F;"></div>
		<div style="position:absolute;width:730px; height:150px; padding-top:30px; background-color: transparent;">
			<form method="post" action="/sk/linker/login">
				<div>
					<span class="account-form-text" style="display:inline-block; color:#F0F0F0;">Login:</span>
					<input type="text" class="form-input input-form" style="display:inline-block; margin-bottom: 16px;" name="worker_login">
				</div>
				<div>
					<span class="account-form-text" style="display:inline-block; color:#F0F0F0;">Hasło:</span>
					<input type="text" class="form-input input-form" style="display:inline-block; margin-bottom: 16px;" name="worker_password">
				</div>
				<span class="account-form-text" style="position:absolute; left: 25px; bottom:5px; font-weight:800; text-align:left;width:auto;"><?= $message->show(); ?></span>
				<button class="button" type="submit" style="margin-left: 10px; height:29px; width:90px; position:absolute; right:50px; bottom: 20px;">Jedziemy</button>
			</form>
		</div>

		<?php elseif($loadSection == 'register_form'):   ?>
		<div style="position: absolute; top: 30%; left: 50%; margin-right: -50%; transform: translate(-50%, 0%) ">
		<div style="width:696px; height:16px; background-color: #005279; padding: 8px 17px;">
			<span style="font: 13px Arial; color: #fff;">SK :: Zakładanie konnta do linkera</span>
		</div>
		<div style="position:absolute; width:730px; height:190px; padding-top:30px; background-color: #2E373F;"></div>
		<div style="position:absolute;width:730px; height: 190px; ; padding-top:30px; background-color: transparent;">
			<form method="post" action="/sk/linker/create">
				<input type="hidden" name="worker_id" value="<?= $formId ?>">
				<div>
					<span class="account-form-text" style="display:inline-block; color:#F0F0F0;">Login:</span>
					<input type="text" class="form-input input-form" style="display:inline-block; margin-bottom: 16px;" name="worker_login">
				</div>
				<div>
					<span class="account-form-text" style="display:inline-block; color:#F0F0F0;">Hasło:</span>
					<input type="password" class="form-input input-form" style="display:inline-block; margin-bottom: 16px;" name="worker_password">
				</div>
				<div>
					<span class="account-form-text" style="display:inline-block; color:#F0F0F0;">Powtórz hasło:</span>
					<input type="password" class="form-input input-form" style="display:inline-block; margin-bottom: 16px;" name="worker_password_repeat">
				</div>
				<span class="account-form-text" style="position:absolute; left: 25px; bottom:5px; font-weight:800; text-align:left;width:auto;"><?= $message->show(); ?></span>
				<button class="button" type="submit" style="margin-left: 10px; height:29px; width:90px; position:absolute; right:50px; bottom: 20px;">Rejestruj</button>
			</form>
		</div>
		<?php elseif($loadSection == 'main'):   ?>

		<div class="wrapper">
			<div class="header">
				<span id="logo">Studio-Komp.pl</span>
				<div class="sidebar-icon-box" style="width: 267px; margin-right:440px">
					<i class="fa fa-search"></i>
					<input type="text" id="search" class="sidebar-search" placeholder="szukaj">
				</div>
				<span id="user"><?= $_SESSION['worker_login']?></span>

				<div class="header-icon-box">
					<i class="fa fa-bell"></i>
				</div>
				<div class="header-icon-box">
					<i class="fa fa-user"></i>
				</div>
				<div class="header-icon-box">
					<i class="fa fa-gear"></i>
				</div>
				<a class="header-icon-box" href="/sk/linker/logout">
					<i class="fa fa-bed"></i>
				</a>
				

			</div>
			<div class="sidebar">
				
				<div class="sidebar-icon-box">
					<a href="#"><i class="fa fa-arrow-down"></i>
					<span class="sidebar-text">Przyjęcie</span></a>
				</div>
				<div class="sidebar-icon-box">
					<i class="fa fa-arrow-right"></i>
					<span class="sidebar-text">Wydanie</span>
				</div>
				<div class="sidebar-icon-box">
					<i class="fa fa-hourglass-half"></i>
					<span class="sidebar-text">Kolejka</span>
				</div>
				<div class="sidebar-icon-box">
					<i class="fa fa-database"></i>
					<span class="sidebar-text">Przegląd</span>
				</div>                
			</div>
			<div class="content">
				<div style="width:auto;height:auto;background-color: #2E373F;border-radius: 5px;margin-top: 25px;padding:12px 10px 20px 15px;">
					<span class="content-block-header" style="display:inline;">Kliknięte linki do wystawienia opinii</span>
					<div style="margin: 25px 0 15px 0; font: 14px Tahoma; display:flex; ">
						<span class="" style="width: 88px; padding-left: 7px;">Plomba</span>
						<span class="" style="width: 180px; ">Data</span>
						<span class="" style="width: 120px; ">IP</span>
						<span class="" style="width: 75px; ">Serwisant</span>
						<span class="" style="width: 60px; ">Wizyta</span>
						<span class="" style="width: 529px; height:17px; overflow:hidden;">Przeglądarka</span>
					</div>
					<?php
						$db->query("SELECT * FROM linker");
						$linker = $db->fetchAll();
						rsort($linker);

						foreach($linker as $row) {
							$datetime = strtotime($row['visit_datetime']);
							$today = date('Ymd');
							
							if($today == date('Ymd', $datetime)) {
								$createDate = 'dziś, '.date('H:i', $datetime);
								$createTime = date('d.m.Y', $datetime);
		
							} elseif(--$today == date('Ymd', $datetime)) {
								$createDate = 'wczoraj, '.date('H:i', $datetime);
								$createTime = date('d.m.Y', $datetime);
		
							} else {
								$createDate = nameOfDay($row['visit_datetime']) . ', '. date('d.m.y', $datetime).', '.date('H:i', $datetime);
								$createTime = '';

							}
							print '
								<div class="user-row" style="font: 14px Tahoma; display:flex; padding: 8px 0px; border-radius: 3px; border: 1px solid transparent;">
									<span class="user-entry" style="width: 90px; padding-left: 12px;">'.$row['sticker'].'</span>
									<span class="user-entry" style="width: 180px; " title="'.$createTime.'">'.$createDate.'</span>
									<span class="user-entry" style="width: 120px; ">'.$row['ip'].'</span>
									<span class="user-entry" style="width: 75px; ">'.$row['worker_id'].'</span>
									<span class="user-entry" style="width: 60px; ">'.$row['count'].'</span>
									<span class="user-entry" style="width: 529px; height:17px; overflow:hidden;" title="'.$row['user_agent'].'">'.$row['user_agent'].'</span>
								</div> ';
						}
					?>
				</div>
			</div>

		<?php endif;  ?>

	</div>

</body>
<script src="http://ivybe.ddns.net/sk/src/jquery-1.12.4.min.js "></script>
<script src="http://ivybe.ddns.net/sk/src/main.js"></script>

</html>