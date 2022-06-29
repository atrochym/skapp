<?php

class Device
{
	private int $deviceId;
	private array $device = [];
	public string $message;

	public function __construct(private Database $db)
	{}


	public function create(array $device)
	{
		$data = [
			'customer_id' => $device['customerId'],
			'producer' => $device['producer'],
			'model' => $device['model'],
			'serial_number' => $device['serialNumber'],
			'creator_id' => getFromSession('workerId'),
		];
		$createId = $this->db->insert('devices', $data);

		$this->message = 'success::Urządzenie zostało dodane.';
		return $createId;
	}

	public function getData()
	{
		if ($this->deviceId < 1)
		{
			$this->message = 'warn::Niepoprawny identyfikator urządzenia.';
			return false;
		}

		if ($this->device)
		{
			return $this->device;
		}

		$data = ['deviceId' => $this->deviceId];
		$device = $this->db->run(
			'SELECT d.id AS device_id, c.id AS customer_id, d.*, c.* FROM devices AS d
			LEFT JOIN customers AS c ON d.customer_id = c.id
			WHERE d.id = :deviceId', $data)->fetch();

		if (!$device)
		{
			$this->message = 'warn::Urządzenie o podanym identyfikatorze nie istnieje.';
			return false;
		}

		$this->device = $device;
		return $this->device;
	}

	public function setDeviceId(int $deviceId)
	{
		$this->deviceId = $deviceId;
	}
}


// przed dodaniem chba sprawdzę czy podany customer istnieje