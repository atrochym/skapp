<?php

$deviceId = $router->getId();

if ($action == 'receive-form') {
	if (!$deviceId) {
		$router->redirect('/desktop');
	}

	$customer = new CustomerModel($model);
	$result = $customer->details($customerId); // wziąć se skądś customerID 

	if (!$result['success']) {
		$model->message->set($result);
		$router->redirect('back');
	}

	$customerView = new CustomerView($view);
	$customerView->customerDetails($result);




	echo 'formularz przyjęcia po ID urządzenia';

} elseif ($action == 'service-done') {

	$deviceModel = new DeviceModel($model);
	// $deviceView = new DeviceView($view);

	$deviceModel->setServiceDone($receiveId);


} elseif ($action == 'delete') {
	$deviceModel = new DeviceModel($model);
	$result = $deviceModel->delete($deviceId);
	$model->message->set($result);
	$router->redirect('back');

}
elseif ($action == 'create') //zostaje   /////////////////// jak zrobić ze sprawdzaniem uprawnień?
{
	// if (!workerPermit('device_create'))
	// {
	// 	setMessage('warn::Do wykonania tej akcji potrzebujesz uprawnień.');
	// 	$router->redirect('back');
	// }


	$validate = new Validate;
	$validate->add('customerId', $_POST['customer_id'], 'require integer');
	$validate->add('producer', $_POST['producer'], 'require text 3 50');
	$validate->add('model', $_POST['model'], 'text 0 50');
	$validate->add('serialNumber', $_POST['serial_number'], 'text 0 50');

	if (!$validate->check())
	{
		setMessage('error::Wystąpił błąd podczas walidacji danych.');
		$router->redirect('back');
	}

	$device = new Device($db);
	$result = $device->create($validate->getValidData());

	setMessage($device->message);
	$router->redirect('/device/' . $result . '/receive');
}

$device = new Device($db);
$device->setDeviceId($deviceId);

$deviceData = $device->getData();
// $receives = $device->getReceivesList();



// yyyyyyyyyyyyyyyyy widok itp

if (!$deviceData)
{
	setMessage($device->message);
	redirect('desktop');
}
// v($deviceData);


$view->addCSS('device');
// $view->addCSS('receive');
$view->addJS('device', true);
// $view->addJS('receive');
$view->addView('receive-create');
$view->addData($deviceData);
$view->render();


// $customerId = $urlParser->id();

// $device = new DeviceModel($model);

// $devices = $device->getAll($customerId);
// v($devices);

exit;


if ($action == 'test-service-done') {

	$deviceModel = new DeviceModel($model);
	$deviceModel->testsetServiceDone($serviceId);

	$deviceView = new DeviceView($view);

	if (!$deviceModel['status']) {
		// $deviceView
	}



}













if ($action == 'service-done') {

	$deviceModel = new DeviceModel($model);
	// $deviceView = new DeviceView($view);

	$deviceModel->setServiceDone($receiveId);
}



$db = database();


$receive = $db->prepare('SELECT * FROM receives AS re 
						LEFT JOIN devices AS de ON re.device_id = de.id 
						LEFT JOIN customers AS cu ON de.customer_id = cu.id 
						WHERE re.id=?');
$receive->bindValue(1, $receiveId);
$receive->execute();

$data = $receive->fetch();



$tagId = substr($data['tag'], 0, -4);
$tagMonth = substr($data['tag'], -4, 2);
$receiveTag = "$tagId/$tagMonth ";

$deviceName = $data['producer'].' '.$data['model'];

$predicted_datetime = longDate($data['predicted_datetime']);  // coś tu ogarnąć z formatowaniem daty

$services = $db->prepare('SELECT * FROM services WHERE receive_id='.$receiveId);
$services->execute();
$services = $services->fetchAll();

$delegateReceive = false;

foreach ($services as $service) {
	if ($service['status'] == 0 && !$service['deleted']) {
		$delegateReceive = true;
	}
}

// v($services);


$parts = $db->prepare("SELECT * FROM stock_parts WHERE receive_id=$receiveId AND deleted IS NULL");
$parts->execute();
$parts = $parts->fetchAll();

$allowEdit = false;
$allowRemove = false;
$allowDelegate = false;

if (workerPermit('for_test')) {
	$allowEdit = true;
}

if (workerPermit('for_test')) {
	$allowRemove = true;
}

if (workerPermit('for_test')) {
	$allowDelegate = true;
}

foreach ($services as $key => $row) {

	if ($row['deleted']) {
		// continue;
		// unset($services[$key]);
	}
}

// v($services);













$comments = $db->query('SELECT w.id, w.name AS worker_name, c.* FROM receives_comments AS c LEFT JOIN workers AS w ON c.worker_id = w.id WHERE c.is_visible=1 AND c.receive_id='.$receiveId);
$comments = $comments->fetchAll();
krsort($comments);
$commentsCount = count($comments);

// v($comments);

foreach ($comments as $key => $value) {

	$comments[$key]['create_datetime'] = longDate($value['create_datetime'], true);

	
	$name = explode(' ', $value['worker_name']);


	$comments[$key]['worker_short'] = strtoupper(substr($name[0], 0, 1) . substr($name[1], 0, 1));

}

$logs = $db->query('SELECT w.name AS worker_name, l.* FROM receives_log AS l LEFT JOIN workers AS w ON l.worker_id = w.id WHERE l.receive_id='.$receiveId);
$logs = $logs->fetchAll();

?>

<!DOCTYPE html>
<html>

<head>
	<title>Title of the document</title>
	<link rel="stylesheet" href="http://ivybe.ddns.net/sk/src/font-awesome.min.css">
	<link rel="stylesheet" href="http://ivybe.ddns.net/sk/src/main.css">
	<link rel="stylesheet" href="http://ivybe.ddns.net/sk/src/device.css">


	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@800&display=swap" rel="stylesheet">
</head>

<body>




<div id="part-add-background" style="display:none; position: fixed; width: 100%; height: 100%; background: #131313;z-index:10;opacity: 0.6;"></div>

<div id="part-add-window" style="display:none; position: absolute;top: 30%;left: 50%;margin-right: -50%;transform: translate(-50%, 0%);z-index:11; width: 700px;">

					<div style="width:100%; background-color: #005279; padding: 8px 17px;">
						<span style="font: 12px Arial; color: #fff;">Dołącz część do urządzenia</span>
					</div>

					<div style="width:100%; position:absolute; background-color: #2E373F; padding: 20px 16px; height:200px;">
						<form autocomplete="off" method="post" action="/sk/device/8/part-assign">
							<div style="width:660px; padding:0 10px; margin-right:14px;">
								<span class="content-block-text" style="display:inline-block">Numer plomby lub nazwę i numer plomby</span>
								<i class="tooltip fa fa-question-circle" style="display:inline" title="Dla urządzenia ze stanu magazynowego: &#013;• Nowe - spisz wyłącznie numer plomby z urządzenia &#013;• Używane - wpisz nazwę i numer naklejanej plomby"></i>

								<input type="text" id="part-url" class="form-input input-form " style="width:300px" name="part">
							</div>

							<div>

								<button class="button-blue" type="submit" style="margin-left: 10px; height:27px; width:auto; position:absolute; right:36px; bottom: 25px; padding: 0 30px;">Dodaj</button>

							</div>
							<span class="content-block-text" style="position:absolute; left: 27px; bottom:17px; font-weight:800; text-align:left;width:auto; cursor:pointer;" onclick="togglePartAdd()">Powrót</span>
						</form>
					</div>
					
				</div>




	<div class="wrapper">
		<div class="header">
			<span id="logo">Studio-Komp.pl</span>
			<div class="sidebar-icon-box" style="width: 300px; margin-right:440px; height: 45px;">
				<i class="fa fa-search"></i>
				<input type="text" id="search" class="sidebar-search" placeholder="szukaj">
			</div>
			<span id="user"><?= $_SESSION['workerName'] ?></span>

			<div class="header-icon-box">
				<i class="fa header-icon-box-fa fa-bell"></i>
			</div>
			<div class="header-icon-box">
				<i class="fa header-icon-box-fa fa-user"></i>
			</div>
			<div class="header-icon-box">
				<i class="fa header-icon-box-fa fa-gear"></i>
			</div>
			<div class="header-icon-box">
				<i class="fa header-icon-box-fa fa-bed"></i>
			</div>


		</div>
		<div class="sidebar">

			<div class="sidebar-icon-box">
				<i class="fa fa-arrow-down"></i>
				<span class="sidebar-text">Przyjęcie</span>
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
			<div class="section-name">
				<span class="device">
					<!-- 214/09 Marka urządzenia, model :: Czeka na decyzję -->
					<?= $receiveTag, $deviceName ?>
				</span>
				<span class="term">Termin: <?= $predicted_datetime ?></span>
			</div>
			<div class="top-menu">
				<?php 
					if ($data['finished']) : ?>
					Zamknięte
					<?php else: ?>

					<div>
						<?php
							if ($delegateReceive) {
								print '<a class="button-m" href="/sk/device/'.$receiveId.'/delegate">
										<i class="ico fa fa-check"></i>Oddeleguj</a>';
							} else {
								print '<a class="button-m" href="#">
								<i class="ico fa fa-check"></i>Zgłoś jako ukończony</a>';
							}
						?>
						<a class="button-m" href="#">
							<i class="ico fa fa-comment-o"></i>Kontakt z klientem</a>
						<a class="button-m" href="#">
							<i class="ico fa fa-frown-o"></i>Rezygnacja</a>
						<a class="button-m" href="#">
							<i class="ico fa fa-edit"></i>Edytuj</a>
						<a class="button-m" href="/sk/rece">
							<i class="ico fa fa-trash"></i>Usuń</a>
					</div>
					<?php endif; ?>
				<div>
					<span class="right-field">
						<i class="right-ico fa fa-lock"></i><?= $data['password'] ?></span>
					<span class="right-field">
						<i class="right-ico fa fa-phone"></i><?= $data['telephone'] ?></span>
				</div>
			</div>

			
			<div class="content-box">
				<h2 class="title">Opis usterki / uwagi</h2>
				<span class="issue-box"><?= $data['issue'] ?></span>
				<span class="notice-box"><?= $data['notice'] ?></span>
			</div>
			<div class="content-box">
				<!-- <h2 class="title">Zaproponowane rozwiązania</h2> -->
				<form class="form-test" action="/sk/device/26/save-solutions" method="post" style="width: inherit;">
				<input type="hidden" name="receive_id" value="<?= $receiveId ?>">

				<table class="solution-list" data-id="0" data-count="0">
					<tr>
						<th class="col col1">Zaproponowane rozwiązanie</th>
						<th class="col col2">Koszt</th>
						<th class="col col3">Status</th>
						<th class="col col4">Pracownik</th>
						<th class="col col5">Akcje</th>
					</tr>

					<?php foreach ($services as $value): 
						$priceTest = 'price';


						switch ($value['status']) {
							case 0:
								$status = '-';
								break;
							case 1:
								$status = 'Zrobione';
								break;
							case 2:
								$status = 'Część zamówiona';
								break;
							case 3:
								$status = 'Rezygnacja';
								$priceTest = null;
								break;
							case 4:
								$status = 'Usunięte';
								$priceTest = null;
								break;
							case 5:
								$status = 'Delegacja';
								break;
							default:
								$status = 'Unknown';
						}

						if($value['deleted']) {
							$status = 'Usunięte';
							$priceTest = null;
						}

						if ($value['worker_id'] == $workerId) {
							$workerName = 'Ty';
						} else {
							$workerName = 'NIE Ty';

							// $workerName = workersList($value['worker_id']);
						}

					?>
						<tr class="row">
							<td class="kek"><input disabled class="input-test dupa" type="text" value="<?= $value['name'] ?>"></td>
							<td class=""><input disabled class="input-test-cost <?= $priceTest ?>" type="text" value="<?= $value['cost'] ?>"></td>
							<td class=""><?= $status ?></td>
							<td class="">
								<div class="worker-name-test"><?= $workerName ?></div>
								<select class="input select delegate" style="font-size: 12px; padding: 3px; width: 135px; display: none;" size="1" >
									<option>Dołącz akcję</option>
									<option value="141">Konto Test</option>
									<option value="140">Userek</option>
									<option value="120">Dupa</option>
								</select>
							</td>
							<td class="">
								<div class="action-buttons">
								<?php

									if ($value['status'] == 1) {
										print '<a class="ico fa fa-check" title="Cofnij oznaczenie ukończenia" href="/sk/device/'.$receiveId.'/service-undone/service-'.$value['id'].'"></a>';

									} elseif ($value['status'] == 3) {
										print '<a class="ico fa fa-frown-o" title="Cofnij rezygnację z naprawy" href="/sk/device/'.$receiveId.'/service-proceed/service-'.$value['id'].'"></a>';

									} elseif ($value['status'] == 4 && $allowRemove || $value['deleted']) {
										print '<a class="ico fa fa-trash" title="Przywróć" href="/sk/device/'.$receiveId.'/service-restore/service-'.$value['id'].'"></a>';

									} else {
										print '<a class="ico fa fa-check" title="Oznacz jako ukończone" href="/sk/device/'.$receiveId.'/service-done/service-'.$value['id'].'"></a>';

										print '<a class="ico fa fa-frown-o" title="Rezygnacja z tej naprawy" href="/sk/device/'.$receiveId.'/service-cancel/service-'.$value['id'].'"></a>';

										if ($allowEdit) {
											print '<a class="ico fa fa-edit row-edit" title="Edytuj"></a>';

										}

										if ($allowRemove) {
											print '<a class="ico fa fa-trash" title="Usuń" href="/sk/device/'.$receiveId.'/service-remove/service-'.$value['id'].'"></a>';
										}

										if ($allowDelegate) {
											print '<i class="ico fa fa-user-plus delegate-button" title="Przydziel innemu pracownikowi"></i>';
										}

									}
								?>
								</div>
								<div class="edit-buttons" style="display:none;">
									<a class="ico fa fa-save row-edit-end" title="Wyślij" onclick="testsubmit(<?= $value['id'] ?>);"></a>
									<a class="ico fa fa-times row-edit-end" title="Anuluj"></a>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
					<!-- </tr> -->
					<tr class="buttons" style="line-height: 28px;">
						<td class="kek" style="text-align: right; padding-right: 10px;">RAZEM</td>
						<td class="price-total" style="padding-left: 8px;">340 PLN</td>
					</tr>
					<button class="button add-solution" style="position: absolute; left:27px; bottom: 27px; top:auto" type="button">Dodaj</button>
				</table>
			</form>				
			</div>

			<div class="content-box history">
				<h2 class="title">Powiązane naprawy</h2>
				<i class="ico-test fa fa-caret-up"></i>
				<div class="animate">
					<table class="history-list">
						<tr class="row">
							<td class="col col1">54321</td>
							<td class="col col2">Czyszczenie chłodzenia</td>
							<td class="col col3">wt, 03.08.2021</td>
						</tr>
						<tr class="row">
							<td class="col col1">54321</td>
							<td class="col col2">Czyszczenie chłodzenia</td>
							<td class="col col3">wt, 03.08.2021</td>
						</tr>
					</table>
				</div>
			</div>


			<div class="content-box">
				<h2 class="title">Zamontowane części</h2>
				<i class="ico-test fa fa-caret-up"></i>
				<div class="animate">
					<table class="history-list">
						<?php foreach ($parts as $value): ?>
							<tr class="row">
								<td class="col col1">1</td>
								<td class="col col2"><?= $value['name'] ?></td>
								<td class="col col3"><?= longDate($value['assigned']) ?></td>
							</tr>
						<?php endforeach; ?>
						<tr class="row">
							<td>
								<button class="button" style="left:27px; bottom: 10px; top:auto">Dodaj ze stanu</button>
								<button class="button" style="left:155px; bottom: 10px; top:auto">Nowy zakup</button>
							</td>
						</tr>
					</table>

				</div>
			</div>

			<div class="content-box comment-box">
				<h2 class="title">Czad, hehehe (<?= $commentsCount ?>)</h2>
				<i class="ico-test fa fa-caret-up"></i>
<div class="animate">
<form action="/sk/device/<?= $receiveId ?>/add-comment" method="POST">

				<div class="comment">
					<!-- <input type="hidden" name="receive" value="<?= $receiveId ?>"> -->
					<div class="c-worker">
						<span class="avatar-user">AT</span>
						<span class="worker-name">Andrzej Trochym</span>
						<!-- Andrzej Trochym -->
					</div>
						<textarea name="comment" class="c-content input-box"></textarea>

					<div class="button-box">
						<select class="input select" name="action" size="1" >
							<option>Dołącz akcję</option>
							<option value="mark-done">Oznacz jako zakończone</option>
							<option value="mark-cancel">Rezygnacja</option>
						</select>
						<button class="button-blue">Wyślij</button>
					</div>
				</div>
				</form>

				<?php foreach($comments as $row): ?>
					
				<div class="comment">
					<div class="c-worker">
						<span class="avatar-user"><?= $row['worker_short'] ?></span>
						<span class="worker-name"><?= $row['worker_name'] ?></span>
					</div>
					<div class="c-content"><?= $row['content'] ?></div>
					<div class="c-date"><?= $row['create_datetime'] ?></div>
					<div class="ico-box">
						<i class="ico fa fa-edit"></i>
						<a class="ico fa fa-trash" title="Usuń" href="/sk/device/<?= $receiveId ?>/remove-comment/comment-<?= $row['id'] ?>"></a>
					</div>
				</div>

				<?php endforeach; ?>


</div>

			</div>


			<div class="content-box log">
				<h2 class="title">Log</h2>
				<i class="ico-test fa fa-caret-up"></i>
				<div class="animate">
					<table class="log-box">
						<?php foreach($logs as $row): ?>
							<tr class="row">
								<td class="col col1"><?= $row['worker_name'] ?>:</td>
								<td class="col col2"><?= $row['content'] ?></td>
								</td>
								<td class="col col3"><?= longDate($row['created'], true) ?></td>
							</tr>
						<?php endforeach; ?>
					</table>
				</div>				
			</div>
			<?php if ($section == 'customer') : ?>

			<?php endif; ?>

		</div>
	</div>


</body>
<script src="http://ivybe.ddns.net/sk/src/jquery-1.12.4.min.js "></script>
<script src="http://ivybe.ddns.net/sk/src/main.js"></script>
<script src="http://ivybe.ddns.net/sk/src/device.js"></script>

</html>



<?php
