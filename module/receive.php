<?php

$receiveId = $controller->id();
// $receiveModel = new ReceiveModel($model, $receiveId);

if ($action == 'create' && $_SERVER['REQUEST_METHOD'] == 'POST')
{
	$validate = new Validate;
	$validate->add('device_id', $_POST['device_id'], 'require integer');
	$validate->add('password', $_POST['password'], 'text 0 100');
	$validate->add('issue', $_POST['issue'], 'text require 3 1000');
	$validate->add('notice', $_POST['notice'], 'text 0 1000');
	$validate->add('advance_value', $_POST['advance_value'], 'float');
	$validate->add('predicted_datetime','2010-10-10 10:10:10', 'datetime require');

	if (!$validate->getValid())
	{
		setMessage('error::Wystąpił błąd podczas walidacji danych.');
		$controller->redirect('back');
	}

	$validData = $validate->getValidData();

	foreach ($_POST['solution'] as $key => $value) // jest też w service.php
	{
		$validate->add('name', $value['name'], 'require text 3 100');
		$validate->add('price', $value['price'], 'require interval 1 9');

		if (!$validate->getValid())
		{
			setMessage('error::Wystąpił błąd podczas walidacji danych.');
			$controller->redirect('back');
		}

		$validData['solution'][$key]['name'] = $validate->name;
		$validData['solution'][$key]['price'] = $validate->price;
	}

	$receive = new Receive($db);
	$receive->create($validData);

	setMessage($receive->message);

	$controller->redirect('back');
}

$view->joinCSS('receive');
$view->joinCSS('device');

$receive = new Receive($db);
$receive->setReceiveId($receiveId);
$receiveData = $receive->getData();

if (!$receiveData)
{
	setMessage($receive->message);
	$controller->redirect('desktop');
}


if ($action == 'device')
{
	$deviceId = $controller->id();

	$device = new DeviceModel($model);
	$deviceView = new DeviceView($view);

	$deviceDetails = $device->getDetails($deviceId);

	if (!$deviceDetails['success'])
	{
		ve($deviceDetails['message']);
	}


	$deviceView->device($deviceDetails['data']);


	$view->render();

} elseif ($action == 'add-device_') {
	formBackup();

	$receiveModel->addDevice($_POST);

	
	
} elseif ($action == 'add-device') {
	formBackup();

	$result = $receiveModel->addDevice($_POST);
	$model->message->set($result);

	// if (!$result['succes']) {
	// 	$controller->redirect('back');
	// }
	// $controller->redirect('customer/device/' . $result['deviceId']);
	$controller->redirect('back');



	
	
}
elseif ($action == 'new') {

	// $receiveModel = new ReceiveModel($model);
	// $receiveView = new ReceiveView($view);

	// $receiveView->addCustomer();
	
	// $view->render();
	$customerView = new CustomerView($view);
	$customerView->createForm();

	$view->render();

}
elseif ($action == 'start')
{
	$receive->start();
	setMessage($receive->message);
	$controller->redirect('back');
}
elseif ($action == 'complete')
{
	$receive->setServicesList(new ServicesList($db, $receiveId));
	$receive->complete();
	setMessage($receive->message);
	$controller->redirect('back');
}
elseif ($action == 'restore')
{ 
	$receive->restore();
	setMessage($receive->message);
	$controller->redirect('back');
}
elseif ($action == 'open')
{
	$receive->open();
	setMessage($receive->message);
	$controller->redirect('back');
}
elseif ($action == 'recover')
{
	$receive->recover();
	setMessage($receive->message);
	$controller->redirect('back');
}
elseif ($action == 'resignation')
{

	$result = $receiveModel->resignation();

}
elseif ($action == 'delete')
{
	$receive->delete();
	setMessage($receive->message);
	$controller->redirect('back');
}
elseif ($action == 'edit')
{
	$receive->setReceiveId($receiveId);
	$receiveData = $receive->getData();

	if (!$receiveData)
	{
		setMessage($receive->message);
		$controller->redirect('desktop');
	}

	ve($receiveData);
}

$deviceName = $receiveData['producer'] . ' ' . $receiveData['model'];
$data = [
	'tag' => $receiveData['tag'],
	'name' => $deviceName,
	'predicted_datetime' => $receiveData['predicted_datetime'],
	'finished' => $receiveData['finished'],
	'returned' => $receiveData['returned'],
	'deleted' => $receiveData['deleted'],
	'status' => $receiveData['status'],
	'phone' => $receiveData['phone'],
	'password' => $receiveData['password'],
	'delegateReceive' => $delegateReceive,
	'receiveId' => $receiveId,

	'issue' => $receiveData['issue'],
	'notice' => $receiveData['notice'],

];

	// $view->addView('receive-overview');
	$view->addData($data);
	$view->addView('receive-overview');
	// $view->addView('receive-test-0');

////////////////////////////////

$data = [];
$services = new ServicesList($db, $receiveId);
$data['services'] = $services->getAll();


$testWorkersList = getWorkersList($db);
$data['actionButtons'] = true;

foreach ($data['services'] as $key => $service)	{

	$data['services'][$key]['workerName'] = $testWorkersList[$service['worker_id']];
}

if ('finished' == $data['receiveStatus'] || 'finished' == $data['receiveStatus'] || $data['receiveDeleted']) {
	
	$data['actionButtons'] = false;
}

$view->addData($data);
$view->addView('receive-fix-list');
$view->addView('receive-assign-part');

//////////////////////////////////
$view->joinJS('receive');
$view->joinJS('device');

$view->render();
echo 'pokażę';
exit;



// else
// {

	$receiveId = $controller->id();

	if (!$receiveId) {
		$controller->redirect('receive/new');
	}

	$result = $receiveModel->setReceive($receiveId);

	if (!$result['success']) {
		$model->message->set($result);
		$controller->redirect('back');
	}


	$receiveView = new ReceiveView($view);
	$receiveView->menu($receiveModel->menu());
	$receiveView->info($receiveModel->info());

	$solutions = $receiveModel->solutions($receiveId);
	$receiveView->testReceive($solutions);
	$receiveView->relatedReceives($receiveModel->relatedReceives());
	
	// $receiveView->parts($receiveModel->parts($receiveId));

	$receiveModel->partsList($solutions);

	$receiveView->comments($receiveModel->comments($receiveId));

	// taki test formularza dodającego część
	$receiveView->partAssignForm();


	
// 	$view->render();

// 	exit;

// 	$receive = $db->prepare('SELECT * FROM receives AS r 
// 							LEFT JOIN devices AS d ON r.device_id = d.id 
// 							LEFT JOIN customers AS c ON d.customer_id = c.id 
// 							WHERE r.id=?');
// 	$receive->bindValue(1, $receiveId);
// 	$receive->execute();

// 	if(!$receive->rowCount()) {
// 		$message->set($receiveId .' - takie przyjęcie nie istnieje..', 'yellow');
// 		redirect('receive/new');
// 	}

// 	$data = $receive->fetch();


// 	$services = $db->prepare('SELECT * FROM services WHERE receive_id='.$receiveId);
// 	$services->execute();
// 	$services = $services->fetchAll();

// 	// if (count($services) < 1) {

// 	// 	$uslugi = 'brak uslug';
// 	// } else {
// 	// 	$uslugi
// 	// }




// // v($data);

// 	$tagId = substr($data['tag'], 0, -4);
// 	$tagMonth = substr($data['tag'], -4, 2);
// 	$receiveTag = "$tagId/$tagMonth ";

// 	$deviceName = $data['producer'].' '.$data['model'];

// 	$predicted_datetime = longDate($data['predicted_datetime']);  // coś tu ogarnąć z formatowaniem daty

// }

if (!$receiveId)
{
	// redirect('receive/new');
	$customerView = new CustomerView($view);
	$customerView->createForm();

	$view->render();
}

$receiveDetails = $receiveModel->getDetails();

if (!$receiveDetails['success'])
{
	$customerView = new CustomerView($view);
	$view->showMessage($receiveDetails['message']);
	$customerView->createForm();
	$view->render();
}


// wprowadzić model renderowania strony bez redirect
// w zależności od otrzymanej odpowedzi od modelu dobrać do wyrenderowania templatki
// wiadomość zwróconą przez model wrzucać do obiektu widoku

$receiveView = new ReceiveView($view);
$receiveView->menu($receiveModel->menu());
$receiveView->info($receiveModel->info());

$solutions = $receiveModel->solutions($receiveId);
$receiveView->testReceive($solutions);
$receiveView->relatedReceives($receiveModel->relatedReceives());

// $receiveView->parts($receiveModel->parts($receiveId));

$receiveModel->partsList($solutions);

$receiveView->comments($receiveModel->comments($receiveId));

// taki test formularza dodającego część
$receiveView->partAssignForm();



$view->render();

exit;

$receive = $db->prepare('SELECT * FROM receives AS r 
						LEFT JOIN devices AS d ON r.device_id = d.id 
						LEFT JOIN customers AS c ON d.customer_id = c.id 
						WHERE r.id=?');
$receive->bindValue(1, $receiveId);
$receive->execute();

if(!$receive->rowCount()) {
	$message->set($receiveId .' - takie przyjęcie nie istnieje..', 'yellow');
	$controller->redirect('receive/new');
}

$data = $receive->fetch();


$services = $db->prepare('SELECT * FROM services WHERE receive_id='.$receiveId);
$services->execute();
$services = $services->fetchAll();

// if (count($services) < 1) {

// 	$uslugi = 'brak uslug';
// } else {
// 	$uslugi
// }




// v($data);

$tagId = substr($data['tag'], 0, -4);
$tagMonth = substr($data['tag'], -4, 2);
$receiveTag = "$tagId/$tagMonth ";

$deviceName = $data['producer'].' '.$data['model'];

$predicted_datetime = longDate($data['predicted_datetime']);  // coś tu ogarnąć z formatowaniem daty
