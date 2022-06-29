<?php

$partId = $controller->id();
$partModel = new PartModel($model, $partId);

if ($action == 'create-order')
{
	$partView = new PartView($view);
	$partView->createOrder($partModel->partCategories());
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
elseif ($action == 'testSaveCategory' && $_SERVER['REQUEST_METHOD'] == 'POST')
{
	$input = json_decode(file_get_contents('php://input'), true);
	$result = $partModel->saveCategory($input);

	echo json_encode($result);
}

?>