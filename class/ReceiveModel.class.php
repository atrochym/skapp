<?php

class ReceiveModel {

	private $model;
	// private $db;
	private $receive;
	private $receiveId;
	private $services;
	private $result = [];

	public function __construct(Model $model, int $receiveId = 0) {

		$this->model = $model;
		$this->receiveId = $receiveId;

		if ($receiveId) {
			$this->setReceive();
		}
		// $this->db = $this->model->db();
	}

	public function getAll(int $customerId) {

		$condition = !$this->model->workerPermit('receive_delete') ? 'AND deleted = 0' : '';

		$receives = $this->model->db->prepare('SELECT r.id AS receive_id, r.*, d.*
												FROM receives AS r
												LEFT JOIN devices AS d ON r.device_id = d.id
												WHERE d.customer_id = ? ' .$condition);
		$receives->bindValue(1, $customerId);
		$receives->execute();
		$receives = $receives->fetchAll();

		if (!$receives) {
			return ['success' => true,
					'devices' => null];
		}

		return ['success' => true,
				'receives' => $receives,];
	}

	// public function delete() {

	// 	if (!$this->receiveExist()) {
	// 		return $this->result;
	// 	}

	// 	$removeReceive = $this->model->db->exec('UPDATE receives SET deleted = 1 WHERE id = '.$this->receiveId);

	// 	if (!$removeReceive) {
	// 		return ['success' => false,
	// 				'messageContent' => 'Usunięcie przyjęcia nie powiodło się.', 
	// 				'messageType' => 'yellow'];
	// 	}

	// 	return ['success' => true,
	// 			'messageContent' => 'Przyjęcie zostało usunięte.', 
	// 			'messageType' => 'green'];
	// }

	// public function restore(int $receiveId) {

	// 	$receive = $this->model->db->prepare('SELECT * FROM receives WHERE id = ?');
	// 	$receive->bindValue(1, $receiveId);
	// 	$receive->execute();
	// 	$receive = $receive->fetch();

	// 	if (!$receive) {
	// 		return ['success' => false,
	// 				'messageContent' => 'Błąd przywracania, przyjęcie nie istnieje.', 
	// 				'messageType' => 'yellow'];
	// 	}

	// 	$restoreReceive = $this->model->db->exec('UPDATE receives SET deleted = 0 WHERE id = '.$receiveId);

	// 	if (!$restoreReceive) {
	// 		return ['success' => false,
	// 				'messageContent' => 'Przywracanie przyjęcia nie powiodło się.', 
	// 				'messageType' => 'yellow'];
	// 	}

	// 	return ['success' => true,
	// 			'messageContent' => 'Przyjęcie zostało przywrócone.', 
	// 			'messageType' => 'green'];
	// }

	// public function complete() {

	// 	if (!$this->receiveExist()) {

	// 		return $this->result;
	// 	}

	// 	if ('finished' == $this->receive['status']) {

	// 		return ['success' => false,
	// 				'messageContent' => 'Wystąpił błąd w trakcie zmiany statusu, przyjęcie już jest zamknięte.', 
	// 				'messageType' => 'yellow'];
	// 	}

	// 	$services = $this->getServices();

	// 	foreach ($services as $service) {

	// 		if ($service['deleted']) {

	// 			continue;
	// 		}

	// 		if (!is_numeric($service['cost'])) {

	// 			return ['success' => false,
	// 					'messageContent' => 'Usługa '.$service['name'].' musi mieć jednoznaczny koszt.', 
	// 					'messageType' => 'yellow'];
	// 		}

	// 		if ('finished' !== $service['status'] && 'canceled' !== $service['status']) {

	// 			return ['success' => false,
	// 					'messageContent' => 'Prawdopodobnie zadanie '.$service['name'].' nie zostało jednoznacznie zakończone.', 
	// 					'messageType' => 'yellow'];
	// 		}
	// 	}
	// 	$complete = $this->model->db->query("UPDATE receives SET finished = NOW(), status = 'finished' WHERE id = $this->receiveId");

	// 	if (!$complete) {

	// 		return ['success' => false,
	// 				'messageContent' => 'Wystąpił błąd w trakcie zmiany statusu.', 
	// 				'messageType' => 'red'];
	// 	}
	// 	return ['success' => true,
	// 			'messageContent' => 'Przyjęcie ukończone i przekazane do wydania.', 
	// 			'messageType' => 'green'];
	// }

	// public function open() {

	// 	if ($this->receive['deleted']) {

	// 		return ['success' => false,
	// 				'messageContent' => 'Nie możesz edytować usuniętego przyjęcia.', 
	// 				'messageType' => 'red'];
	// 	}
		
	// 	if ('returned' == $this->receive['status']) {

	// 		return ['success' => false,
	// 				'messageContent' => 'Urządzenie z tego przyjęcia zostało już wydane, nie możesz go edytować.', 
	// 				'messageType' => 'yellow'];
	// 	}

	// 	$open = $this->model->db->query("UPDATE receives SET finished = NULL, status = NULL WHERE id = $this->receiveId");

	// 	if (!$open) {

	// 		return ['success' => false,
	// 				'messageContent' => 'Wystąpił błąd w trakcie zmiany statusu.', 
	// 				'messageType' => 'red'];
	// 	}

	// 	return ['success' => true,
	// 			'messageContent' => 'Przyjęcie zostało otwarte.', 
	// 			'messageType' => 'green'];

	// }





	public function setReceive() { // raczej wyleci

		$receive = $this->model->db->prepare('SELECT r.id AS receive_id, r.*, d.customer_id, d.producer, d.model, c.telephone
										FROM receives AS r 
										LEFT JOIN devices AS d ON r.device_id = d.id 
										LEFT JOIN customers AS c ON d.customer_id = c.id 
										WHERE r.id = ?');

		$receive->bindValue(1, $this->receiveId);
		$receive->execute();
		$receive = $receive->fetch();

		if (!$receive) {
			return ['success' => false,
					'messageContent' => 'Przyjęcie o podanym identyfikatorze nie istnieje.', 
					'messageType' => 'red'];
		}

		// $this->receiveId = $this->receiveId;
		$this->receive = $receive;

		return ['success' => true];
	}


	private function checkEditPossibility() {

		if ('returned' == $this->receive['status']) {

			return false;
		}

		if ('finished' == $this->receive['status']) {

			return false;
		}

		if ($this->receive['deleted']) {

			return false;
		}

		return true;
	}

	private function receiveExist() {

		if (!$this->receive['receive_id']) {

			$this->result =  ['success' => false,
							'messageContent' => 'Przyjęcie o podanym identyfikatorze nie istnieje.', 
							'messageType' => 'red'];
			return false;
		}
		return true;
	}

	private function getServices() {

		if ($this->services) {
			return $this->services;
		}
		$services = $this->model->db->prepare('SELECT s.*, p.id AS part_id, p.name AS part_name, p.assigned
												FROM services AS s
												LEFT JOIN parts AS p ON s.part_id = p.id
												WHERE receive_id = ?');
		$services->bindValue(1, $this->receiveId);
		$services->execute();
		$this->services = $services->fetchAll();

		return $this->services;
	}

	public function solutions($receiveId) {

		$this->getServices($receiveId); // raz juz wywołuję w menu(), po chuj?

		$workers = $this->model->getWorkersList();

		$data = ['services' => $this->services,
				'receiveId' => $receiveId,
				'workersList' => $workers,
				'receiveStatus' => $this->receive['status'],
				'receiveDeleted' => $this->receive['deleted'],
				'allowEdit' => $this->model->workerPermit('for_test'),
				'allowRemove' => $this->model->workerPermit('for_test'),
				'allowChangeWorker' => $this->model->workerPermit('for_test'),];

		return $data;
	}

	public function info() {
		return ['issue' => $this->receive['issue'],
				'notice' => $this->receive['notice']];

	}

	// public function parts($receiveId) {
	// 	$parts = $this->model->db->prepare("SELECT * FROM stock_parts WHERE receive_id=$receiveId AND deleted IS NULL");
	// 	$parts->execute();

	// 	return $parts->fetchAll();
	// }

	public function comments($receiveId) {
		$comments = $this->model->db->query('SELECT w.id, w.name AS worker_name, c.* 
								FROM receives_comments AS c 
								LEFT JOIN workers AS w ON c.worker_id = w.id 
								WHERE c.is_visible=1 AND c.receive_id='.$receiveId);

		$comments = $comments->fetchAll();
		krsort($comments);

		foreach ($comments as $key => $value) {

			$comments[$key]['create_datetime'] = longDate($value['create_datetime'], true);
			$name = explode(' ', $value['worker_name']);
			$comments[$key]['worker_short'] = strtoupper(substr($name[0], 0, 1) . substr($name[1], 0, 1));
		}

		return $comments;
	}

	// to powino działać tak, że przy wywołaniu tworzy się obiekt klasy receive
	// ma on właściwości i metody typowe dla pojedynczego przyjęcia
	// za pomocą metod mogę wpływać na stan tego obiektu

	// np do wyrenederowania menu będzie mi potrzebny aktualny stan obiektu receive z bazy 
	// i na tej podstawie controler odbierając dane z obiektu receive przetworzy je
	// wstawi dane do widoku, wybierze widok i wyrenderuje stronę

	public function menu() {
		$delegateReceive = false;

		foreach ($this->getServices($this->receiveId) as $service) {
			if ($service['status'] == 0 && !$service['deleted']) {
				$delegateReceive = true;
			}
		}

		$deviceName = $this->receive['producer'] . ' ' . $this->receive['model'];

		return ['tag' => $this->receive['tag'],
				'name' => $deviceName,
				'predicted_datetime' => $this->receive['predicted_datetime'],
				'finished' => $this->receive['finished'],
				'returned' => $this->receive['returned'],
				'deleted' => $this->receive['deleted'],
				'status' => $this->receive['status'],
				'telephone' => $this->receive['telephone'],
				'password' => $this->receive['password'],
				'delegateReceive' => $delegateReceive,];
	}

	public function getDetails() {

		$receive = $this->model->db->prepare(
			'SELECT r.id AS receive_id, r.*, d.customer_id, d.producer, d.model, c.telephone
			FROM receives AS r 
			LEFT JOIN devices AS d ON r.device_id = d.id 
			LEFT JOIN customers AS c ON d.customer_id = c.id 
			WHERE r.id = ?');

		$receive->bindValue(1, $this->receiveId);
		$receive->execute();
		$receive = $receive->fetch();

		if (!$receive) {
			return [
				'success' => false,
				'message' => '{error}Przyjęcie o podanym identyfikatorze nie istnieje.',
			];
		}

		// $this->receiveId = $this->receiveId;
		$this->receive = $receive;

		return ['success' => true];
	}

	// public function start() {


	// 	if (!$this->receive['receive_id']) {

	// 		return ['success' => false,
	// 				'messageContent' => 'Przyjęcie nie istnieje.',
	// 				'messageType' => 'red'];
	// 	}
		
	// 	if ($this->receive['worker_id'] > 0) {

	// 		return ['success' => false,
	// 				'messageContent' => 'To zadanie zostało już rozpoczętę przez: '. $this->receive['worker_id'] .'.',
	// 				'messageType' => 'yellow'];
	// 	}

	// 	if ($this->receive['deleted']) {

	// 		return ['success' => false,
	// 				'messageContent' => 'To zadanie zostało wcześniej usunięte.',
	// 				'messageType' => 'yellow'];
	// 	}

	// 	if ($this->receive['finished']) {

	// 		return ['success' => false,
	// 				'messageContent' => 'To zadanie zostało wcześniej ukończone przez: ' . $this->receive['worker_id'] . '.',
	// 				'messageType' => 'yellow'];
	// 	}

	// 	$worker = $_SESSION['workerId'];
	// 	$start = $this->model->db->query("UPDATE receives SET worker_id = $worker, started = NOW(), status = 'started'  WHERE id = $this->receiveId");

	// 	if (!$start) {
	// 		return ['success' => false,
	// 				'messageContent' => 'Błąd podczas rozpoczęcia zadania dla urządzenia '. $this->receive['producer'] .'.',
	// 				'messageType' => 'red'];
	// 	}

	// 	return ['success' => true];

	// }


	public function partsList(array $data) {
		$services = $data['services'];
		v($services);
	}

	public function create(array $data) {

// TRANSAKCJA!

		$tag = $this->model->db->query('SELECT tag FROM receives ORDER BY id DESC LIMIT 1');
		$tag = $tag->fetchColumn();
		$tag = genDeviceTag($tag);

		$dataX = ['id' => NULL,
				'tag' => $tag,
				'device_id' => $data['device_id'],
				'creator_id' => '99',
				'worker_id' => '0',
				'password' => $data['password'],
				'issue' => $data['issue'],
				'notice' => $data['notice'],
				'extra_items' => isset($data['cb-extra_items']) ?: 0,
				'express' => isset($data['cb-express']) ?: 0,
				'confirmation_receipt' => isset($data['cb-confirmation_receipt']) ?: 0,
				'advance_value' => isset($data['advance_value']) ?: 0,
				// 'predicted_datetime' => $form['predicted_datetime']
				'predicted_datetime' => '2010-10-10 10:10:10',
				'is_draft' => isset($data['cb-is_draft']) ?: 0];

		$receive = $this->model->db->prepare(insert('receives', $dataX));
		if (!$receive->execute($dataX)) {
			return ['success' => false,
					'messageContent' => 'Utworzenie przyjęcia nie powiodło się.',
					'messageType' => 'red'];
		}

		$newReceiveId = $this->model->db->lastInsertId();
		
		foreach ($data['solution'] as $key => $value) {

			$data = ['id' => NULL,
					'receive_id' => $newReceiveId,
					'creator_id' => '99',
					'name' => $value['name'],
					'cost' => $value['price']];

			$service = $this->model->db->prepare(insert('services', $data));
			if (!$service->execute($data)) {
				return ['success' => false,
						'messageContent' => 'Wystąpił błąd podczas zapisywania danych.',
						'messageType' => 'red'];
			}
		}
		
		return ['success' => true,
				'messageContent' => 'Przyjęcie zostało zapisane pomyślnie.',
				'messageType' => 'green'];
	}



	public function relatedReceives() {
		$device = $this->receive['device_id'];

		$related = $this->model->db->query("SELECT * FROM receives WHERE device_id = $device");
		$related = $related->fetchAll();

		return ['success' => true,
				'receiveId_' => $this->receiveId,
				'relatedReceives' => $related];

	}

	public function return(int $receiveId)
	{
		echo 'zwracamy ' .$receiveId;
		exit;
	}

}

// A MOŻE do tabeli services dodac kolumnę part_id, będzie też to obliczać koszt przedmiotu i koszt montażu
// nie wiem jeszcze jak odróżnić część ze stocka od części z allegro czy używanej


// używam tu metody setService() zamiast tego
// private function getReceive(int $serviceId) {
// 	if (!$receiveId) {
// 			throw new Exception('ERR receivemodel: receive id missing');
// 	}

// 	if ($this->receive) {
// 		return $this->receive;
// 	}

// 	$receive = $this->model->db->query('SELECT * FROM services WHERE id = ' . $serviceId);
// 	$receive = $receive->fetch();

// 	if ($receive) {
// 		$this->receive = $receive;
// 		return $receive;
// 	}
// }


?>