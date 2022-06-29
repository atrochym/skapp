<?php

$partId = $controller->id();
// $partModel = new PartModel($model, $partId);

if ($action == 'create-order')
{
	$part = new Part($db);
	$partCategories = $part->getAllCategories();

	$view->joinCSS('part');
	$view->joinJS('part');
	$view->addData(['partCategories' => $partCategories]);
	$view->addView('create-order-form');
	$view->addView('category-create');
	$view->render();

}
elseif ($action == 'save-order')
{
	$result = $partModel->saveOrder($_POST);

	if (!$result['success']) {
		$script = 'cl("script from php")';
		$view->addData(['jsScript' => $script]);
	} else {
		$view->addData(['jsScript' => '']);

	}
	
	$model->message->set($result);
	$controller->redirect('back');
}
elseif ($action == 'create-category-DEPRACTED')
{
	$result = $partModel->saveCategory($_POST);
	$model->message->set($result);
	$controller->redirect('back');
}
elseif ($action == 'testSaveCategory')
{
	$input = json_decode(file_get_contents('php://input'), true);

	$validate = new Validate;
	$validate->add('name', $input['category'], 'require text 3 50');

	if (!$validate->getValid())
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