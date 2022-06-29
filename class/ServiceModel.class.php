<?php

class ServiceModel {

	private $model;
	private array $service = [];
	private array $part = [];
	private int $serviceId;
	private array $result;
	// private $receiveId;
	// private $services;

	public function __construct(Model $model, int $serviceId = 0) {

		$this->model = $model;
		$this->serviceId = $serviceId;

		if ($serviceId) {

			$this->setService();
		}
	}





	public function createPart(array $data) {
		$serviceId = $data['service_id'];
		$sticker = $data['sticker'] ?: null;
		$categoryId = $data['category_id'];
		$name = $data['name'];
		$note = $data['note'];
		$price = $data['price'];
		$isUsed = isset($data['cb-used']) ?: 0;

		if (!$this->serviceExist($serviceId)) {
			return $this->result;
		}

		if (!$this->canAssignPart($serviceId)) {
			return $this->result;
		}

		if ($sticker) {
			$part = $this->getPart($sticker, 'sticker');

			if ($part['id']) {
				return ['success' => false,
						'messageContent' => 'Plomba ' . $sticker . ' jest przypisana dla innego urządzenia.', 
						'messageType' => 'yellow'];
			}
		}

		$dataX = ['id' => NULL,
				'category_id' => $categoryId,
				'name' => $name,
				'price' => $price,
				'sticker' => $sticker,
				'assigned' => date('Y-m-d H:i:s'),
				'state' => 'ok',
		];

		$this->model->db->beginTransaction();

		$assign = $this->model->db->prepare(insert('parts', $dataX));
		$assign = $assign->execute($dataX);
		$lastId = $this->model->db->lastInsertId();

		$dataY = ['id' => $lastId,
				'seller' => 'Studio-Komp',
				'is_used' => $isUsed,
				'note' => $note,
				'creator_id' => '101',
		];
		
		$assign2 = $this->model->db->prepare(insert('parts_details', $dataY));
		$assign2 = $assign2->execute($dataY);
		$assign2 = $this->model->db->query("UPDATE services SET part_id = $lastId WHERE id = $serviceId");
		

		$result = $this->model->db->commit();


		if (!$result) {
			$this->model->db->rollBack();

			return ['success' => false,
					'messageContent' => $name . ' nie mógł zostać wpisany na stan lub zostać dołączony do usługi.', 
					'messageType' => 'red'];
		}

		return ['success' => true,
				'messageContent' => $name . ' został wpisany na stan dołączony do usługi.', 
				'messageType' => 'green'];
	}

	public function assignPartSticker(array $data) {
		$serviceId = $data['service_id'];
		$sticker = $data['sticker'];
		$part = $this->getPart($sticker, 'sticker');
		$partId = $part['id'];

		
		if (!$this->serviceExist($serviceId)) {
			return $this->result;
		}

		if (!$this->canAssignPart($serviceId)) {
			return $this->result;
		}

		if (!$this->partExist($sticker, 'sticker')) {
			return $this->result;
		}

		if (!$this->checkAssignedPart($partId)) {
			return $this->result;
		}


		$this->model->db->beginTransaction();
		
		$result =  $this->model->db->prepare('UPDATE services SET part_id = ? WHERE id = ?');
		$result->bindValue(1, $partId);
		$result->bindValue(2, $serviceId);
		$result->execute();

		$date = date('Y-m-d H:i:s');
		$this->model->db->query("UPDATE parts SET assigned = '$date' WHERE id = $partId");

		$result = $this->model->db->commit();


		
		if (!$result) {
			$this->model->db->rollBack();
			return ['success' => false,
					'messageContent' => 'Błąd : ' . $this->part['name'] . ' nie mógł zostać dodany.',
					'messageType' => 'red'];
		}

		return ['success' => true,
				'messageContent' => $this->part['name'] . ' został dodany.', 
				'messageType' => 'green'];
	}

	public function unplugPart() {
		// $service = $this->getService($serviceId);
		$part = $this->getPart($this->service['part_id']);

		if (!$this->serviceExist($this->serviceId)) {
			return $this->result;
		}

		$this->model->db->beginTransaction();

		$unplugPart =  $this->model->db->prepare('UPDATE services SET part_id = NULL WHERE id = ?');
		$unplugPart->bindValue(1, $this->serviceId);

		$serviceStatus = $this->model->db->query("UPDATE services SET status = 'opened' WHERE id = $this->serviceId");

		$this->model->db->commit();
		
		if (!$unplugPart->execute()) {
			$this->model->db->rollBack();
			return ['success' => false,
					'messageContent' => $part['name'] . ' nie mogło zostać odpięte od usługi.',
					'messageType' => 'red'];
		}

		if (!$serviceStatus) {
			$this->model->db->rollBack();
			return ['success' => false,
					'messageContent' => 'Błąd zmiany statusu.', 
					'messageType' => 'red'];
		}

		return ['success' => true,
				'messageContent' => $part['name'] . ' został odpięty.',
				'messageType' => 'green'];
	}

	public function assignPartId(array $data) {

		// sprawdz poprawnosc danych w bazie przed updatem

		$serviceId = $data['service_id'];
		$partId = $data['part_id'];

		if (!$this->serviceExist($serviceId)) {
			return $this->result;
		}

		if (!$this->canAssignPart($serviceId)) {
			return $this->result;
		}
		
		if (!$this->partExist($partId)) {
			return $this->result;
		}

		if (!$this->checkAssignedPart($partId)) {
			return $this->result;
		}

		$this->model->db->beginTransaction();

		$assignPart =  $this->model->db->prepare('UPDATE services SET part_id = ? WHERE id = ?');
		$assignPart->bindValue(1, $partId);
		$assignPart->bindValue(2, $serviceId);
		
		$serviceStatus = $this->model->db->query("UPDATE services SET status = 'part_ordered' WHERE id = $serviceId");

		$this->model->db->commit();

		if (!$assignPart->execute()) {
			$this->model->db->rollBack();
			return ['success' => false,
					'messageContent' => 'Błąd: ' . $this->part['name'] . ' nie mógł zostać dodany.', 
					'messageType' => 'red'];
		}

		if (!$serviceStatus) {
			$this->model->db->rollBack();
			return ['success' => false,
					'messageContent' => 'Błąd zmiany statusu.', 
					'messageType' => 'red'];
		}

		return ['success' => true,
				'messageContent' => $this->part['name'] . ' został dodany.', 
				'messageType' => 'green'];
	}

// a w ogóle to przydałoby się sprawdzanie tokena zmian
// np [1] zmienia cene a [2] daje jako ukończone nie widząc zmian w między czasie


	// to się kurde dubluje, da się zapiąć w jedno ??

	public function removeService() {
		// $service = $this->getService($serviceId);

		$workerId = $_SESSION['workerId'];

		if (!$this->serviceExist($this->serviceId)) {

			return $this->result;
		}

		if (!$this->checkEditPossibility()) {

			return ['success' => false,
					'messageContent' => 'Usługa nie mogła zostać usunięta.', 
					'messageType' => 'red'];
		}
		
		if ($this->service['status'] == 'finished') {
			return ['success' => false,
					'messageContent' => $this->service['name'] . ' jest oznaczona jako ukończona. Nie można jej usunąć, zmień jej status.', 
					'messageType' => 'yellow'];
		}

		if ($this->service['status'] == 'part_ordered') {
			return ['success' => false,
					'messageContent' => $this->service['name'] . ' czeka na zamówioną część. Nie można jej usunąć, zmień jej status.', 
					'messageType' => 'yellow'];
		}

		$remove = $this->model->db->query("UPDATE services SET deleted = 1, worker_id = $workerId WHERE id = $this->serviceId");

		if (!$remove) {
			return ['success' => false,
					'messageContent' => 'Usługa nie mogła zostać usunięta.', 
					'messageType' => 'red'];
		}

		return ['success' => true,
				'messageContent' => $this->service['name'] . ' został usunięty.', 
				'messageType' => 'green'];
	}

	private function checkEditPossibility() {

		if ('returned' == $this->service['receive_status']) {

			return false;
		}

		if ('finished' == $this->service['receive_status']) {

			return false;
		}

		if ('canceled' == $this->service['receive_status']) {

			return false;
		}
		
		if ($this->service['receive_deleted']) {

			return false;
		}

		return true;
	}

	public function restoreService() {
		// $service = $this->getService($serviceId);

		// if (!$this->serviceExist($this->serviceId)) {
		// 	return $this->result;
		// }

		if (!$this->service['deleted']) {
			return ['success' => false,
					'messageContent' => $this->service['name'] . ' przecież nie jest usunięta.', 
					'messageType' => 'yellow'];
		}

		$restore = $this->model->db->query("UPDATE services SET deleted = 0, worker_id = 0 WHERE id = $this->serviceId");

		if (!$restore) {
			return ['success' => false,
					'messageContent' => 'Usługa nie mogła zostać przywrócona.', 
					'messageType' => 'red'];
		}

		return ['success' => true,
				'messageContent' => $this->service['name'] . ' został przywrócona.', 
				'messageType' => 'green'];
	}

	public function completeService() {
		// $service = $this->getService($serviceId);

		if (!$this->serviceExist($this->serviceId)) {
			return $this->result;
		}

		// przekazywanie tokena do porónania - skąd? post? jako dodatkowy arg funkcji? albo tablica danych jako arg fun?

		// if (!$this->checkToken($serviceId, 'xxx')) {
		// 	return $this->result;
		// }

		if (!$this->checkEditPossibility()) {

			return ['success' => false,
					'messageContent' => 'Nie można edytować tego przyjęcia..', 
					'messageType' => 'red'];
		}


		if ($this->service['deleted']) {
			return ['success' => false,
					'messageContent' => 'Nie można ukończyć usuniętej usługi.', 
					'messageType' => 'yellow'];
		}

		// if ($service['status'] == 'finished') {
		// 	return ['success' => false,
		// 			'messageContent' => $service['name'] . ' już została ukończona.', 
		// 			'messageType' => 'yellow'];
		// }

		if ($this->service['status'] == 'canceled') {
			return ['success' => false,
					'messageContent' => $this->service['name'] . ' jest oznaczona jako rezygnacja, nie można oznaczyć jako ukończoną.', 
					'messageType' => 'yellow'];
		}

		$workerId = $_SESSION['workerId'];
		$complete = $this->model->db->query("UPDATE services SET status = 'finished', finished = NOW(), worker_id = $workerId WHERE id = $this->serviceId");

		if (!$complete) {
			return ['success' => false,
					'messageContent' => 'Usługa nie mogła zostać ukończona.', 
					'messageType' => 'red'];
		}

		return ['success' => true,
				'messageContent' => $this->service['name'] . ' została ukończona.', 
				'messageType' => 'green'];
	}
	
	public function incompleteService() {
		// $service = $this->getService($serviceId);

		// if (!$this->serviceExist($serviceId)) {
		// 	return $this->result;
		// }

		$incomplete = $this->model->db->query("UPDATE services SET status = 'opened', worker_id = 0 WHERE id = $this->serviceId");

		if (!$incomplete) {
			return ['success' => false,
					'messageContent' => 'Błąd podczas zmiany statusu.', 
					'messageType' => 'red'];
		}

		return ['success' => true,
				'messageContent' => 'Status \'Ukończony\' został cofnięty.', 
				'messageType' => 'green'];
	}
	
	public function cancelService() {
		// $service = $this->getService($serviceId);

		// if (!$this->serviceExist($serviceId)) {
		// 	return $this->result;
		// }

		$workerId = $_SESSION['workerId'];

		if ($this->service['status'] == 'finished') {
			return ['success' => false,
					'messageContent' => $this->service['name'] . ' jest oznaczona jako ukończona. Nie można jej usunąć, zmień jej status.', 
					'messageType' => 'yellow'];
		}

		if ($this->service['status'] == 'part_ordered') {
			return ['success' => false,
					'messageContent' => $this->service['name'] . ' czeka na zamówioną część. Nie można jej usunąć, zmień jej status.', 
					'messageType' => 'yellow'];
		}

		$cancel = $this->model->db->query("UPDATE services SET status = 'canceled', worker_id = $workerId WHERE id = $this->serviceId");

		if (!$cancel) {
			return ['success' => false,
					'messageContent' => 'Błąd podczas zmiany statusu ' . $this->service['name'] . '.',
					'messageType' => 'red'];
		}

		return ['success' => true,
				'messageContent' => 'Rezygnacja z naprawy ' . $this->service['name'],
				'messageType' => 'green'];
	}

	public function proceedService() {
		// $service = $this->getService($this->serviceId);

		// if (!$this->serviceExist($this->serviceId)) {
		// 	return $this->result;
		// }
		$workerId = $_SESSION['workerId'];
		
		$cancel = $this->model->db->query("UPDATE services SET status = 'opened', worker_id = 0 WHERE id = $this->serviceId");

		if (!$cancel) {
			return ['success' => false,
					'messageContent' => 'Błąd podczas zmiany statusu ' . $this->service['name'] . '.',
					'messageType' => 'red'];
		}

		return ['success' => true,
				'messageContent' => 'Usługa ' . $this->service['name'] . 'została przywrócona.',
				'messageType' => 'green'];
	}

	public function saveChangesService(array $data) {

		$this->serviceId = $data['serviceId'];
		$this->setService();

		if ($data['type'] == 'singleService') {
			// $service = $this->getService($data['serviceId']);

			if (!$this->serviceExist($data['serviceId'])) {
				return $this->result;
			}

			if ($this->service['status'] == 'finished') {
				return ['success' => false,
						'messageContent' => $this->service['name'] . ' jest oznaczona jako ukończona. Nie można jej edytować, zmień jej status.', 
						'messageType' => 'yellow'];
			}

			$serviceEdit = $this->model->db->exec("UPDATE services SET name='$data[name]', cost='$data[price]' WHERE id=".$data['serviceId']);

			if (!$serviceEdit) {
				return ['success' => false,
						'messageContent' => 'Błąd podczas zapisywania zmian dla ' . $this->service['name'] . '.',
						'messageType' => 'red'];
			}
	
			return ['success' => true,
					'messageContent' => 'Zmiany zostały zapisane.',
					'messageType' => 'green'];

		} else {

			foreach ($data as $name => $value) {
				$form[$name] = $value;
			}
			foreach ($form['solution'] as $value) {
	
				$data = array (
					'id' => NULL,
					'receive_id' => $data['receiveId'],
					'creator_id' => $_SESSION['workerId'],
					'name' => $value['name'],
					'cost' => $value['price']
				);
	
				$service = $this->model->db->prepare(insert('services', $data));
				$service = $service->execute($data);

				if (!$service) {
					return ['success' => false,
							'messageContent' => 'Błąd podczas zapisywania zmian.',
							'messageType' => 'red'];
				}
		
				return ['success' => true,
						'messageContent' => 'Zmiany zostały zapisane.',
						'messageType' => 'green'];
			}
		}
	}

	public function changeWorker(array $data) {

		$this->serviceId = $data['service_id'];
		$this->setService();

		$workerId = $data['worker_id'];

		// if (!$this->serviceExist($serviceId)) {
		// 	return $this->result;
		// }

		$update = $this->model->db->query("UPDATE services SET worker_id = $workerId WHERE id = $this->serviceId");

		if (!$update) {
			return ['success' => false,
					'messageContent' => 'Błąd podczas zmiany pracownika na '. $workerId .'.',
					'messageType' => 'red'];
		}

		return ['success' => true,
				'messageContent' => 'Przypisano dla: '. $workerId .'.',
				'messageType' => 'green'];

	}




	private function setService() {

		$service = $this->model->db->query('SELECT s.*, r.status AS receive_status, r.deleted AS receive_deleted FROM services AS s
											LEFT JOIN receives AS r ON s.receive_id = r.id
											WHERE s.id = ' . $this->serviceId);
		$service = $service->fetch();

		if (!$service) {
			return ['success' => false,
					'messageContent' => 'Wskazana usługa nie istnieje lub wystąpił błąd pobierania danych.', 
					'messageType' => 'red'];
		}

		$this->service = $service;
		return ['success' => true];
	}

	private function serviceExist(int $serviceId) {
		// $service = $this->getService($serviceId);

		// if (!$service['id']) {
		// 	$this->result = ['success' => false,
		// 					'messageContent' => 'Wskazana usługa nie istnieje.', 
		// 					'messageType' => 'yellow'];
		// 	return false;
		// }
		// return true;

		if (!$this->service['id']) {

			$this->result = ['success' => false,
							'messageContent' => 'Usługa o podanym identyfikatorze nie istnieje.', 
							'messageType' => 'red'];
			return false;
		}
		return true;
	}

	private function canAssignPart() {
		// $service = $this->getService($serviceId);

		if ($this->service['part_id']) {
			$this->result = ['success' => false,
							'messageContent' => 'Do tej usługi jest już przypisana część.', 
							'messageType' => 'yellow'];
			return false;
		}
		return true;
	}

	public function partExist(int $partId, string $by = 'id') {
		$part = $this->getPart($partId, $by);

		if (!$part) {
			$this->result = ['success' => false,
							'messageContent' => 'Brak części o wskazanym identyfikatorze.', 
							'messageType' => 'yellow'];
			return false;
		}
		return true;
	}

	// private function getService(int $serviceId) {
	// 	if (!$serviceId) {
	// 			throw new Exception('ERR servicemodel: service id missing');
	// 	}

	// 	if ($this->service) {
	// 		return $this->service;
	// 	}

	// 	$service = $this->model->db->query('SELECT * FROM services WHERE id = ' . $serviceId);
	// 	$service = $service->fetch();

	// 	if ($service) {
	// 		$this->service = $service;
	// 		return $service;
	// 	}
	// }
	
	private function getPart(int $partId, string $using = 'id') {
		if (!$partId) {
			throw new Exception('ERR servicemodel: part id missing');
		}

		if ($this->part) {
			return $this->part;
		}

		$using == 'id' ?: 'sticker';

		$part = $this->model->db->query("SELECT * FROM parts WHERE $using = $partId");
		$part = $part->fetch();

		if ($part) {
			$this->part = $part;
			return $part;
		}

	}


	private function checkAssignedPart(int $partId) {
		$part = $this->model->db->query("SELECT id FROM services WHERE part_id = $partId");
		$part = $part->fetch();

		if ($part) {
			$this->result = ['success' => false,
							'messageContent' => 'Ta część jest nie jest możliwa do przydzielenia.', 
							'messageType' => 'red'];
			return false;
		}
		return true;
	}

	private function token() {
		$token = substr(md5(rand() . 'token'), 0, 10);

		return $token;
	}

	private function checkToken(int $serviceId, string $token) {
		// $service = $this->getService($serviceId);

		if ($this->service['token'] !== $token) {
			$this->result = ['success' => false,
							'messageContent' => 'Dane o tej usłudze w międzyczasie zostały zmienione i zostały odświeżone. Sprawdź zmiany i spróbuj ponownie.', 
							'messageType' => 'blue'];
			return false;
		}
		return true;
	}
}

/* 
OGARNĄĆ duplikowanie tego samego kodu
start: 206 linijek
koiec: 
*/