<?php

class ListModel {

	// private $model;
	// private $device;
	// private $deviceId;

	public function __construct(Model $model) {

		$this->model = $model;

	}

	public function listIndex() {
		$receives = $this->model->db->query('SELECT w.name AS worker_name, r.id AS receive_id, r.creator_id AS receive_creator_id, r.* , d.id AS device_id2, d.*, c.phone
											FROM receives AS r
											LEFT JOIN devices AS d ON r.device_id = d.id
											LEFT JOIN workers AS w ON r.worker_id = w.id 
											LEFT JOIN customers AS c ON d.customer_id = c.id 
											WHERE r.deleted = 0
											LIMIT 5');
		$receives = $receives->fetchAll();

		$services = $this->model->db->query('SELECT services.name, devices.id 
											FROM devices 
											LEFT JOIN services ON services.receive_id = devices.id 
											-- WHERE devices.is_visible = 1
											');
		$services = $services->fetchAll();


		// foreach ($devices as $key => $device) {
		// 	$devices[$key]['services']
		// }

		foreach ($services as $row) {
			$service[$row['id']][] = $row['name'];

		}

		// foreach ($devices as $key => $device) {

		// }

		return ['receives' => $receives,
				'services' => $service,];

		// v($service);
	}
}