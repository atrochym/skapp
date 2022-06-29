<?php

if ($action == 'redir') {


} else {

	$listModel = new ListModel($model);
	$listView = new ListView($view);

	$data = $listModel->listIndex();
	v($data['receives']);
	$listView->listIndex($data);
	$view->render();

}