<?php


if ($action == 'redir') {

	// $accountModel = new AccountModel($model);
	// $accountModel->workerNotLooged();
	// $model->message->set('Wymagane zalogowanie się.', '');

	// $view->renderSingle('account-login-form');

}
	$workerId = $_SESSION['workerId'];

	$settingstModel = new SettingsModel($model);
	$settingstView = new SettingsView($view);

	$data = $settingstModel->index(['workerId' => $workerId]);
	$settingstView->index($data);
	$view->render();

	exit('o tak');







v('s');

$db = new Database;
$validate = new Validate;
$urlParser = new UrlParser;
$message = new Message;

// $test = $_GET['test'];
// echo $test .' :: ';
// $test2 = $validate->alphaNumeric($test, 0, 7);
// print $test2;

// exit;

$urlPath = trim(@$_SERVER['PATH_INFO'], '/');

$urlParams = explode('/', $urlPath);
$action = isset($urlParams[1]) ? $urlParams[1] : '';
$loadSection = 'default';




if ($action == 'create' && count($_POST) > 0) {
}

if (!workerLoggedIn('device_receive')) {
	echo 'zaloguj sie';
	exit;
}

if (!workerPermit('workers_manager')) {
	echo 'brak uprawnień';
	exit;
}


// deleted pdo

$test = $pdo->prepare('SELECT * FROM permissions WHERE worker_id= :worker_id LIMIT 1');
$test->bindValue(':worker_id', $_SESSION['workerId']);
$test->execute();

$test = $test->fetch();
array_shift($test);
v($test)

?>




<!DOCTYPE html>
<html>

<head>
	<title>Title of the document</title>
	<link rel="stylesheet" href="http://ivybe.ddns.net/sk/src/main.css">
	<link rel="stylesheet" href="http://ivybe.ddns.net/sk/src/settings.css">
	<link rel="stylesheet" href="http://ivybe.ddns.net/sk/src/font-awesome.min.css">

	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@800&display=swap" rel="stylesheet">


	<!-- <script src="http://ivybe.ddns.net/sk/src/jquery-1.12.4.min.js "></script> -->


</head>

<body>
					<div id="worker-add-background" style="display:none; position: fixed; width: 100%; height: 100%; background: #131313; z-index:10; opacity: 0.8;"></div>
	   
					<div id="worker-add-window" style="display:none; position: fixed; top: 30%; left: 50%; margin-right: -50%; transform: translate(-50%, 0%); z-index:11; ">
						<div style="width:696px; height:16px; background-color: #005279; padding: 8px 17px;">
							<span style="font: 13px Arial; color: #fff;">Nowy pracownik</span>
						</div>

						<div style="position:absolute;width:698px; background-color: #2E373F; padding: 20px 16px; height:130px;">
							<form autocomplete="off" method="post" action="/sk/account/add">
								<input type="hidden" name="from" value="settings/">
								<div style="display:inline-block; width:268px; padding:0 10px; margin-right:14px;">
									<span class="content-block-text">Imię i Nazwisko</span>
									<input type="text" class="form-input input-form " style="width:100%;" name="name">
								</div>

								<div style="display:inline-block; width:356px; padding:0 10px;margin-right:14px;">
									<span class="content-block-text">Email</span>
									<input type="text" class="form-input input-form " style="width:100%;" name="email" >
								</div>

								<div>
									<button class="button" type="submit" style="margin-left: 10px; height:27px; width:auto; position:absolute; right:36px; bottom: 25px; padding: 0 30px;">Dodaj</button>
									<button class="button-2" type="reset" style="margin-left: 10px; height:27px; width:auto; position:absolute; right:138px; bottom: 25px; padding: 0 30px;">Reset</button>
								</div>
								<span class="content-block-text" style="position:absolute; left: 27px; bottom:17px; font-weight:800; text-align:left;width:auto; cursor:pointer;" onclick="toggleAddWorker()">Powrót</span>
							</form>
						</div>
						
					</div>

	<div class="wrapper">
		<?php require './templates/header.html'; ?>
		<?php require './templates/sidebar.html'; ?>

		<div class="content">
			<span class="section-name">Ustawienia</span>
			<?php $message->show(); ?>

			<div style="width:auto;height:auto;background-color: #2E373F;border-radius: 5px;margin-top: 25px;padding:12px 25px;">
				<span class="content-block-header" style="display:inline;">Użytkownicy</span>
				<?php if (workerPermit('temp_add_worker')): ?>
				<a class="small-button" style="position: absolute; right: 30px;" onclick="toggleAddWorker()">Dodaj</a>
				<?php endif; ?>
				<div style="margin: 25px 0 15px 0; font: 14px Tahoma; display:flex; ">
					<span class="" style="width: 200px; ">Nazwa</span>
					<span class="" style="width: 180px; ">Login</span>
					<span class="" style="width: 250px; ">Email</span>
					<span class="" style="width: 100px; ">Status</span>
				</div>
				<?php
					$db->query("SELECT * FROM workers");
					$workers = $db->fetchAll();

					foreach($workers as $row) {
						print '
							<div class="worker-row" style="font: 14px Tahoma; display:flex; padding: 8px 11px; border-radius: 3px;">
								<span class="worker-entry" style="width: 200px; ">'.$row['name'].'</span>
								<span class="worker-entry" style="width: 180px; ">'.$row['login'].'</span>
								<span class="worker-entry" style="width: 250px; ">'.$row['email'].'</span>
								<span class="worker-entry" style="width: 135px; ">Aktywne</span>
								<span class="worker-entry" style="width: 62px; ">Edytuj</span>
								<span class="worker-entry" style="width: 75px; ">Zablokuj</span>
								<span class="worker-entry" style="width: 90px; cursor:pointer;" onclick="document.location=\'/sk/account/'.$row['id'].'/restore-password\'">Resetuj hasło</span>
							</div> ';
					}
				?>
			</div>


			<div style="width:auto;height:auto;background-color: #2E373F;border-radius: 5px;margin-top: 25px;padding:12px 25px;">
				<span class="content-block-header" style="display:inline;">Uprawnienia</span>
				<form method="post" action="/sk/account/permission">
					<button class="small-button" type="submit" style="position: absolute; right: 30px;" name="">Zapisz</button>
					<div style="display:flex; flex-wrap:wrap;margin-top:20px;">
					<?php
						$db->query("SELECT * FROM workers");
						$workers = $db->fetchAll();

						foreach($test as $key => $value) {
							$checkbox = (bool)$value ? 'checked' : '';

							print '
								<div style="height: 30px; display:flex;"> 
								<input type="checkbox" name="permission['.$key.']" value="1" style="margin-right:20px;" '.$checkbox.'>
									<span style="width:200px;">'.$key.'</span>
								</div>';
						}
					?>
					</div>
				</form>
			</div>


			<?php if ($loadSection == 'default') :    ?>

				// default



			<?php endif;  ?>


		</div>
	</div>
</body>
<script src="http://ivybe.ddns.net/sk/src/jquery-1.12.4.min.js "></script>
<script src="http://ivybe.ddns.net/sk/src/main.js"></script>
<script src="http://ivybe.ddns.net/sk/src/settings.js"></script>

</html>