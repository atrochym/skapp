<?php

$customerId = $controller->id();

$view->joinCSS('customer');


if ($action == 'create')
{
	$validate = new Validate;
	$validate->add('phone', $_POST['phone'], 'require phone 6 20');
	$validate->add('name', $_POST['name'], 'text 0 100');
	$validate->add('email', $_POST['email'], 'email 8 60');

	if (!$validate->getValid())
	{
		setMessage('error::Błąd walidacji danych.');
		$controller->redirect('customer/register');
	}

	$validData = $validate->getValidData();
	$validData['language'] = (int) isset($_POST['non_polish']);

	$customer = new Customer($db);
	$result = $customer->create($validData);

	setMessage($customer->message);

	if ($result === false)
	{
		$controller->redirect('customer/register');
	}

	$controller->redirect('customer/' . $result);

}
elseif ($action == 'edit')
{
	$customerModel = new CustomerModel($model);
	$result = $customerModel->edit($customerId);

	if (!$result['success']) {
		$model->message->set($result);
		$controller->redirect('back');
	}

	$customerView = new CustomerView($view);
	$customerView->editForm($result);
	$customerView->render();	

}
elseif ($action == 'update') // zostaje
{
	$validate = new Validate;
	$validate->add('customerId', $_POST['customer_id'], 'require integer');
	$validate->add('phone', $_POST['phone'], 'require phone 5-15');
	$validate->add('name', $_POST['name'], 'text 0-100');
	$validate->add('email', $_POST['email'], 'email');
	
	if (!$validate->getValid())
	{
		setMessage('error::Wystąpił błąd podczas walidacji danych.');
		$controller->redirect('back');
	}

	$validData = $validate->getValidData();
	$validData['language'] = isset($_POST['non_polish']);

	$customer = new Customer($db);
	$customer->setCustomerId($validate->customerId);
	$customer->update($validData);

	setMessage($customer->message);
	$controller->redirect('customer/' . $validate->customerId);	

} elseif ($action == 'edit-conflict') {
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
		$controller->redirect('back');
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
		$controller->redirect('back');
	}

	$customerView = new CustomerView($view);
	$customerView->customerDetails($result);
	$receive = new ReceiveModel($model);
	$result = $receive->getAll($customerId);

	$receiveView = new ReceiveView($view);
	$receiveView->receivesList($result);

	$view->render();
	
} elseif ($action == 'list') {
	$customer = new CustomerModel($model);
	$result = $customer->getAll();

	if (!$result['success']) {
		$model->message->set($result);
		$controller->redirect('customer/new');
	}

	$customerView = new CustomerView($view);
	$customerView->customerList($result);

	$view->render();


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
elseif ($action == 'test')
{

	$customerId = $controller->id();
	$customer = new Customer($db);

	$customerModel = new CustomerModel($model);
	$customerView = new CustomerView($view);

	$devices = $customerModel->devices($customerId);
	$customerView->devices($devices);

	$customerView->render();
}

$customerId = $controller->id();

$validate = new Validate;
$customer = new Customer($db, $validate);
$customer->setCustomerId($id);
$customerData = $customer->getData();

if ($customerData === false)
{
	setMessage($customer->message);
	redirect('desktop');
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
