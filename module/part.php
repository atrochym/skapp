<?php

$partId = $router->getId();
// $partModel = new PartModel($model, $partId);

if ($action == 'create-order')
{
	$part = new Part($db);
	$partCategories = $part->getAllCategories();

	$view->addCSS('part');
	$view->addJS('part');
	$view->addData(['partCategories' => $partCategories]);
	$view->addView('create-order-form');
	$view->addView('category-create');
	$view->render();

}
elseif ($action == 'save-order')
{
	$validate = new Validate;
	$validate->add('orderDate', $_POST['order-date'], 'date require');
	$validate->add('seller', $_POST['seller'], 'text require 3 50');
	$validate->add('deliveryCost', $_POST['delivery-cost'], 'float 3 6');

	if (!$validate->check())
	{
		setMessage('error::Wystąpił błąd podczas walidacji danych.');
		$router->redirect('back');
	}

	$validData = $validate->getValidData();
	
	foreach ($_POST['item'] as $key => $item)
	{
		$validate->add('category', $item['category'], 'integer require');
		$validate->add('url', $item['url'], 'url require 5 300');
		$validate->add('note', $item['note'], 'text 0 1000');
		$validate->add('name', $item['name'], 'text require 3 300');
		$validate->add('price', $item['price'], 'float require 4 8');
		$validate->add('amount', $item['amount'], 'integer require 1 2');
		$isPartUsed = isset($item['cb-is_used']) ? 1 : 0;


		if (!$validate->check())
		{
			setMessage('error::Wystąpił błąd podczas walidacji danych.');
			$router->redirect('back');
		}

		$validData['item'][$key]['category'] = $validate->category;
		$validData['item'][$key]['url'] = $validate->url;
		$validData['item'][$key]['is_used'] = $isPartUsed;
		$validData['item'][$key]['note'] = $validate->note;
		$validData['item'][$key]['name'] = $validate->name;
		$validData['item'][$key]['price'] = $validate->price;
		$validData['item'][$key]['amount'] = $validate->amount;
	}

	$part = new Part($db);
	$result = $part->createOrder($validData);



	// if (!$result['success']) {
	// 	$script = 'cl("script from php")';
	// 	$view->addData(['jsScript' => $script]);
	// } else {
	// 	$view->addData(['jsScript' => '']);

	// }
	
	setMessage($part->message);
	$router->redirect('back');
}
elseif ($action == 'create-category')
{
	$input = json_decode(file_get_contents('php://input'), true);

	$validate = new Validate;
	$validate->add('name', $input['category'], 'require text 3 50');

	if (!$validate->check())
	{
		$response['categoryId'] = null;
		$response['message'] = 'warn::Nazwa kategorii ma niepoprawny format lub długość.';
		
		echo json_encode($response);
		exit;
	}

	$part = new Part($db);
	$result = $part->createCategory($validate->name);

	$response['categoryId'] = $result;
	$response['categoryName'] = $validate->name;
	$response['message'] = $part->message;

	echo json_encode($response);
	exit;
}

?>