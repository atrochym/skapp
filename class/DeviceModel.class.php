<?php

class DeviceModel {

	private $model;
	private $device;
	private $deviceId;

	public function __construct(Model $model) {

		$this->model = $model;
	}

	public function getAll(int $customerId) {

		$condition = !$this->model->workerPermit('receive_delete') ? 'AND deleted = 0' : '';

		$devices = $this->model->db->prepare('SELECT * FROM devices WHERE customer_id = ? ' .$condition);
		$devices->bindValue(1, $customerId);
		$devices->execute();
		$devices = $devices->fetchAll();

		if (!$devices) {
			return ['success' => true,
					'devices' => null];
		}

		return ['success' => true,
				'devices' => $devices];
	}


	public function delete(int $deviceId) {
// restore jeszce brak
		$device = $this->model->db->prepare('SELECT * FROM devices WHERE id = ?');
		$device->bindValue(1, $deviceId);
		$device->execute();
		$device = $device->fetch();

		if(!$device) {
			return ['success' => false,
					'messageContent' => 'Urządzenie nie istnieje.',
					'messageType' => 'red'];
		}

		if (!$this->model->workerPermit('device_delete')) {
			return ['success' => false,
					'messageContent' => 'Nie posiadasz uprawnień do tej akcji.',
					'messageType' => 'yellow'];
		}

		$deleteDevice = $this->model->db->exec('UPDATE devices SET deleted = 1 WHERE id=' . $deviceId);

		if (!$deleteDevice) {
			return ['success' => false,
					'messageContent' => 'Usunięcie urządzenia nie powiodło się.',
					'messageType' => 'red'];
		}
		
		return ['success' => true,
				'messageContent' => 'Urządzenie zostało usunięte',
				'messageType' => 'blue'];
	}

	public function create(array $data) {

		$validate = new Validate;

		$customerId = $data['customer_id'];
		$producer = $data['producer'];
		$model = $data['model'];
		$serialNumber = $data['serial_number'];


		if (!$validate->string($producer, 2, 50)) {

			return ['succes' => false,
					'messageContent' => 'Marka urządzenia musi mieć między 2 a 50 znaków długości.', 
					'messageType' => 'yellow'];
	
		} elseif ($model && !$validate->string($model, 2, 50)) {

			return ['succes' => false,
					'messageContent' => 'Model musi mieć między 2 a 50 znaków długości.', 
					'messageType' => 'yellow'];
	
		} elseif ($serialNumber && !$validate->string($serialNumber, 2, 50)) {

			return ['succes' => false,
					'messageContent' => 'Numer seryjny musi mieć między 2 a 50 znaków długości.', 
					'messageType' => 'yellow'];
			
		} else {

			$data = ['id' => NULL,
					'customer_id' => $customerId,
					'producer' => $producer,
					'model' => $model,
					'serial_number' => $serialNumber,
					'creator_id' => $this->model->get('workerId')];
			
			$device = $this->model->db->prepare(insert('devices', $data));
			
			if (!$device->execute($data)) {

				return ['succes' => false,
						'messageContent' => 'Dodanie urządzenia nie powiodło się.', 
						'messageType' => 'red'];

			} else {

				$newDevice = $this->model->db->lastInsertId();
				return ['succes' => true,
						'messageContent' => 'Urządzenie '.$producer.' zostało dodane. Wprowadzone poniżej dane zostaną z nim powiązane jako przyjęcie.', 
						'messageType' => 'green',
						'deviceId' => $newDevice];

			}
	
		}
	}













	// wywal
	public function setServiceDone(int $serviceId) {

		if ($service = !$this->dbServiveExist($serviceId)) {
			$this->model->message->set('Usługa nie została odnaleziona.', 'red');
			redirect('desktop');
		}

		$setDone = $this->model->db->exec("UPDATE services SET status = 1, done_datetime = NOW() WHERE id = $serviceId");
		
		if (!$setDone) {
			// $this->model->setLog($service['receive_id'], 'Błąd zakończenie usługi id '.$serviceId);
			$this->model->message->set('Aktualizacja statusu usługi zakończona niepowodzeniem.', 'red');
		}

		// $this->model->setLog($service['receive_id'], 'Zakończenie usługi id '.$serviceId);
		$this->model->message->set('Usługa oznaczona jako ukończona.', 'blue');

		redirect('device/'.$service['receive_id']);
	}



// ivybe.ddns.net/sk/device/service-done/999


	private function dbServiveExist(int $serviceId) {

		$service = $this->model->db->query("SELECT * FROM services WHERE id = $serviceId");
		return $service->fetch();
	}




	public function set($deviceId) {
		$this->deviceId = $deviceId;

		$device = $this->model->db->prepare('SELECT * FROM devices WHERE id=?');
		$device->bindValue(1, $deviceId);
		$device->execute();

		if(!$device->rowCount()) {
			$this->model->message->set(' - takie urządzenie nie istnieje', 'yellow');
			redirect('receive');
		}
		
		$this->device = $device->fetch();
	}

	public function device(int $deviceId) // do wycofania
	{

		$data = $this->model->db->prepare('SELECT c.id AS customer, telephone, name, email, non_polish, producer, model, serial_number 
									FROM devices AS d, customers AS c
									WHERE d.customer_id = c.id AND d.id=?');

		$data->bindValue(1, $deviceId);
		$data->execute();
		$data = $data->fetch();


		return array(
			'telephone' => $data['telephone'],
			'name' => $data['name'],
			'email' => $data['email'],
			'nonPolish' => $data['non_polish'],
			'producer' => $data['producer'],
			'model' => $data['model'],
			'serialNumber' => $data['serial_number'],
			'customerId' => $data['customer'],
			'deviceId' => $deviceId,
		);

	}


	public function getDetails(int $deviceId) { // testowo przepoisane po nowemu

		$data = $this->model->db->prepare(
			'SELECT c.id AS customer, telephone, name, email, non_polish, producer, model, serial_number 
			FROM devices AS d, customers AS c
			WHERE d.customer_id = c.id AND d.id=?');

		$data->bindValue(1, $deviceId);
		$data->execute();
		$data = $data->fetch();

		if (!$data) {
			return [
				'success' => false,
				'message' => 'brak device',
			];
		}

		return [
			'success' => true,
			'data' => [
				'telephone' => $data['telephone'],
				'name' => $data['name'],
				'email' => $data['email'],
				'nonPolish' => $data['non_polish'],
				'producer' => $data['producer'],
				'model' => $data['model'],
				'serialNumber' => $data['serial_number'],
				'customerId' => $data['customer'],
				'deviceId' => $deviceId,
			]
		];

	}

}

?>