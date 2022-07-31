<?php

$customerId = $router->getId();

$view->joinCSS('customer');


if ($action == 'create')
{
	$validate = new Validate;
	$validate->add('phone', $_POST['phone'], 'require phone 6 20');
	$validate->add('name', $_POST['name'], 'text 0 100');
	$validate->add('email', $_POST['email'], 'email 8 60');

	if (!$validate->check())
	{
		setMessage('error::Błąd walidacji danych.');
		$router->redirect('/customer/register');
	}

	$validData = $validate->getValidData();
	$validData['language'] = (int) isset($_POST['non_polish']);

	$customer = new Customer($db);
	$result = $customer->create($validData);

	setMessage($customer->message);

	if ($result === false)
	{
		$router->redirect('/customer/register');
	}

	// $router->redirect('/customer/' . $result);
	$router->redirect(url('/customer/' . $result));


}
elseif ($action == 'update') // zostaje
{
	$id = unmaskId($_POST['customer_id']);
	if (!$id)
	{
		// setMessage('error::y tego.');
		// $router->redirect('back');
		$router->errorPage(403);
	}

	$validate = new Validate;
	$validate->add('customerId', $id, 'require integer');
	$validate->add('phone', $_POST['phone'], 'require phone 5-15');
	$validate->add('name', $_POST['name'], 'text 0-100');
	$validate->add('email', $_POST['email'], 'email');
	
	if (!$validate->check())
	{
		setMessage('error::Wystąpił błąd podczas walidacji danych.');
		$router->redirect('back');
	}

	$validData = $validate->getValidData();
	$validData['language'] = isset($_POST['non_polish']);

	$customer = new Customer($db);
	$customer->setCustomerId($validate->customerId);
	$customer->update($validData);

	setMessage($customer->message);
	// $router->redirect('customer/' . $validate->customerId);
	$router->redirect('back');

} elseif ($action == 'edit-conflict') { // raczej wyleci
	// na razie nie używane, info w modelu
	$customerModel = new CustomerModel($model);
	$customerView = new CustomerView($view);

	$conflictId = $urlParser->param('with');

	$data = ['main' => $customerId, 
			'conflict' => $conflictId];

	$customerModel->conflict($data);

} elseif ($action == 'devices__') { // wyleci
	$customer = new CustomerModel($model);
	$result = $customer->details($customerId);

	if (!$result['success']) {
		$model->message->set($result);
		$router->redirect('back');
	}

	$customerView = new CustomerView($view);
	$customerView->customerDetails($result);
	$deviceModel = new DeviceModel($model);
	$result = $deviceModel->getAll($customerId);

	$deviceView = new DeviceView($view);
	$deviceView->devicesList($result);
	$deviceView->createForm(['customerId' => $customerId]);

	$view->render();
	
} elseif ($action == 'receives') {
	$customer = new CustomerModel($model);
	$result = $customer->details($customerId);

	if (!$result['success']) {
		$model->message->set($result);
		$router->redirect('back');
	}

	$customerView = new CustomerView($view);
	$customerView->customerDetails($result);
	$receive = new ReceiveModel($model);
	$result = $receive->getAll($customerId);

	$receiveView = new ReceiveView($view);
	$receiveView->receivesList($result);

	$view->render();
	
}
elseif ($action == 'list')
{
	$customer = new Customer($db);
	$result = $customer->getAllCustomers();

	if ($result)
	{
		$view->joinCSS('receive'); // ogarnąć, wyodrębnić
		$view->joinCSS('device');
		$view->addData(['customers' => $result]);
		$view->addView('customer-list');
		$view->render();
	}

	setMessage($customer->message);
	$router->redirect('/customer/register');

}
elseif ($action == 'register') //zostaje
{
	$view->addView('customer-create-form');
	
	$view->render();
}
elseif ($action == 'history') //zostaje
{
	$view->addView('customer-create-form');
	
	$view->render();
}
elseif ($action == 'edit')
{
	$customer = new Customer($db);
	$customer->setCustomerId($router->getId());
	$customerData = $customer->getData();

	$view->addData($customerData);
	$view->addView('customer-edit');
	$view->render();

}
elseif ($action == 'test')
{

	$customerId = $router->getId();
	$customer = new Customer($db);

	$customerModel = new CustomerModel($model);
	$customerView = new CustomerView($view);

	$devices = $customerModel->devices($customerId);
	$customerView->devices($devices);

	$customerView->render();
}

$customerId = $router->getId();

$validate = new Validate;
$customer = new Customer($db, $validate);
$customer->setCustomerId($id);
$customerData = $customer->getData();

if ($customerData === false)
{
	setMessage($customer->message);
	$router->redirect('/desktop');
}

$devices = $customer->devices();

$customerData['nonPolish'] = $customerData['non_polish'] ? 'Tak' : 'Nie';

foreach ($devices as $device)
{
	if ($device['receive_id'])
	{
		$view->addData(['receiveList' => true]);
		break;
	}
}

$view->joinCSS('receive');

$view->addData($customerData);
$view->addData(['devices' => $devices]);

$view->addView('receive');

$view->render();
