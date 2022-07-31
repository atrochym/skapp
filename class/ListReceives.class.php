<?php

// "List" zastrzeżone?
class ListReceives
{
	public string $message;

	public function __construct(private Database $db)
	{}

	public function allReceives() // chę to tutaj?
	{
		$receives = $this->db->run(
			'SELECT w.name AS worker_name, r.id AS receive_id, r.creator_id AS receive_creator_id, r.* , d.id AS device_id2, d.*, c.phone
			FROM receives AS r
			LEFT JOIN devices AS d ON r.device_id = d.id
			LEFT JOIN workers AS w ON r.worker_id = w.id 
			LEFT JOIN customers AS c ON d.customer_id = c.id 
			WHERE r.deleted = 0
			LIMIT 10')->fetchAll();

		$services = $this->db->run(
			'SELECT services.name, devices.id 
			FROM devices 
			LEFT JOIN services ON services.receive_id = devices.id 
			-- WHERE devices.is_visible = 1
			')->fetchAll();


		// foreach ($devices as $key => $device) {
		// 	$devices[$key]['services']
		// }

		foreach ($services as $row)
		{
			$service[$row['id']][] = $row['name'];
		}

		// foreach ($devices as $key => $device) {

		// }

		return ['receives' => $receives,
				'services' => $service,];

		// v($service);
	}
}