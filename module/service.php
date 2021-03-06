<?php

$serviceId = $controller->id();

$service = new Service($db);
$service->setServiceId($serviceId);

if ($action == 'assign-part')
{
	v('p');
	formBackup();

	if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['assign-mode'])) {
		$controller->redirect('back');
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
	$validate = new Validate;
	$validate->add('receiveId', $_POST['receiveId'], 'require integer');

	if(!$validate->getValid())
	{
		setMessage('error::Błąd przesyłania danych formularza.');
		$controller->redirect('back');
	}

	$validData = $validate->getValidData();

	foreach ($_POST['solution'] as $key => $value) // jest też w receive.php
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

	$service = new Service($db);
	$service->create($validData);

	setMessage($service->message);
	$controller->redirect('back');

}
elseif ($action == 'update')
{
	$validate = new Validate;
	$validate->add('name', $_POST['name'], 'text require 3 100');
	$validate->add('price', $_POST['price'], 'interval require 1-9');
	$validate->add('receiveId', $_POST['receiveId'], 'require integer');

	if(!$validate->getValid())
	{
		setMessage('error::Błąd przesyłania danych formularza.');
		$controller->redirect('back');
	}

	$service->update($validate->getValidData());
}
elseif ($action == 'set-worker')
{
	$validate = new Validate;
	$validate->add('workerId', $_POST['worker_id'], 'require integer');
	$validate->add('serviceId', $_POST['service_id'], 'require integer');

	if (!$validate->getValid())
	{
		setMessage('error::Błąd przesyłania danych formularza.');
		$controller->redirect('back');
	}

	$service->setServiceId($validate->serviceId);
	$service->setWorker($validate->workerId);
}

// $model->message->set($result);
setMessage($service->message);
$controller->redirect('back');