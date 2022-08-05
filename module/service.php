<?php

$serviceId = $router->getId();

$service = new Service($db);
if ($serviceId)
{
	$service->setServiceId($serviceId);
}

if ($action == 'assign-part')
{
	v('p');
	formBackup();

	if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['assign-mode'])) {
		$router->redirect('back');
	}

	if ($_POST['assign-mode'] == 'create-part') {
		$result = $serviceModel->createPart($_POST);

	} elseif ($_POST['assign-mode'] == 'use-sticker') {
		$result = $serviceModel->assignPartSticker($_POST);

	} elseif ($_POST['assign-mode'] == 'use-part_id') {
		$result = $serviceModel->assignPartId($_POST);
	}


}
elseif ($action == 'unplug-part')
{
	$result = $serviceModel->unplugPart();
}
elseif ($action == 'delete')
{
	$service->delete();
}
elseif ($action == 'cancel')
{
	$service->cancel();
}
elseif ($action == 'restore')
{
	$service->restore();
}
elseif ($action == 'recover')
{
	$service->recover();
}
elseif ($action == 'complete')
{
	$service->complete();
}
elseif ($action == 'incomplete')
{
	$service->incomplete();
}
elseif ($action == 'create')
{
	ve($_POST);

	
	$validate = new Validate;
	$validate->add('receiveId', $_POST['receiveId'], 'require integer');

	if(!$validate->check())
	{
		setMessage('error::Błąd przesyłania danych formularza.');
		$router->redirect('back');
	}

	$validData = $validate->getValidData();

	foreach ($_POST['solution'] as $key => $value) // jest też w receive.php
	{
		$validate->add('name', $value['name'], 'require text 3 100');
		$validate->add('price', $value['price'], 'require interval 1 9');

		if (!$validate->check())
		{
			setMessage('error::Wystąpił błąd podczas walidacji danych.');
			$router->redirect('back');
		}

		$validData['solution'][$key]['name'] = $validate->name;
		$validData['solution'][$key]['price'] = $validate->price;
	}

	$service = new Service($db);
	$service->create($validData);

	setMessage($service->message);
	$router->redirect('back');

}
elseif ($action == 'update')
{
	$input = $router->requestJson();

	$validate = new Validate;
	$validate->add('name', $input['name'], 'text require 3 100');
	$validate->add('price', $input['price'], 'interval require 1-9');
	$validate->add('serviceId', $input['serviceId'], 'require integer');

	$response['success'] = false;

	if (!$validate->check())
	{
		$response['message'] = 'warn::Wystąpił błąd walidacji danych.';
	}
	else
	{
		$service = new Service($db);
		$service->setServiceId($validate->serviceId);
		$response['success'] = $service->update($validate->getValidData());
	}

	echo json_encode($response);
	exit;
}
elseif ($action == 'set-worker')
{
	$validate = new Validate;
	$validate->add('workerId', $_POST['worker_id'], 'require integer');
	$validate->add('serviceId', $_POST['service_id'], 'require integer');

	if (!$validate->check())
	{
		setMessage('error::Błąd przesyłania danych formularza.');
		$router->redirect('back');
	}

	$service->setServiceId($validate->serviceId);
	$service->setWorker($validate->workerId);
}
elseif ($action == 'test')
{
	$input = $router->requestJson();
	// $input['name'] = 'cpz';

	$validate = new Validate;
	$validate->add('name', $input['name'], 'text require 3 100');
	// $validate->add('price', $input['price'], 'interval require 1-9');
	// $validate->add('serviceId', $input['serviceId'], 'require integer');

	$response['success'] = false;

	if (!$validate->check())
	{
		$response['message'] = 'warn::Wystąpił błąd walidacji danych.';
	}
	else
	{
		// $service = new Service($db);
		// $service->setServiceId($validate->serviceId);
		// $response['success'] = $service->update($validate->getValidData());

		$values = [
			'name' => '%'.$validate->name.'%',
			'tag' => '%'.$validate->name.'%',
		];
		$response['data'] = $db->run('SELECT * FROM services_names WHERE name LIKE :name OR tags LIKE :tag', $values)->fetchAll();
		$response['success'] = true;

	}

	echo json_encode($response);
	exit;
}
// $model->message->set($result);
setMessage($service->message);
$router->redirect('back');