<?php

// workerPermit('device_receive') ?: exit ('uprawnienia');

// $urlParser = new UrlParser;
// $message = new Message;
// $validate = new Validate;

// $db = database();

// $action = $urlParser->action();

$receives = $db->query('SELECT r.id AS receive_id, producer, model, d.id AS device_id, tag, issue, finished, r.worker_id
						FROM receives AS r
						LEFT OUTER JOIN devices AS d ON d.id = r.device_id
						ORDER BY receive_id DESC');

$receives = $receives->fetchAll();

// v($receives);

$data = array(
	'testMessage' => $message->show(),
	'receives' => $receives,
);

$view = new View('overview-temp', $data);
$view->title('przegląd ');
$view->joinCSS('overview');

$view->render();