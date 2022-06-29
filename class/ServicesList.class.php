<?php

class ServicesList
{
	private array $servicesList = [];
	private string $serviceName;

	public function __construct(

		private Database $db,
		private int $receiveId
	)
	{
		$this->getAll();
	}

	public function checkFinishedServices(): bool
	{
		$finishedServices = true;

		foreach($this->getAll() as $service)
		{
			if ($service['deleted'])
			{
				continue;
			}

			if ('finished' !== $service['status'] && 'canceled' !== $service['status'])
			{
				$finishedServices = false;
				$this->serviceName = $service['name'];
				break;
			}
		}

		return $finishedServices;
	}

	public function checkUnequivocalCost(): bool
	{
		$unequivocalCost = true;

		foreach($this->getAll() as $service)
		{
			if ($service['deleted'])
			{
				continue;
			}

			if (!is_numeric($service['price']))
			{
				$unequivocalCost = false;
				$this->serviceName = $service['name'];
				break;
			}
		}

		return $unequivocalCost;
	}

	public function getAll()
	{
		if (empty($this->servicesList))
		{
			$data = ['receive_id' => $this->receiveId];
			$exec = $this->db->run(
				'SELECT s.*, p.id AS part_id, p.name AS part_name, p.assigned
				FROM services AS s
				LEFT JOIN parts AS p ON s.part_id = p.id
				WHERE receive_id = :receive_id', $data);

			$this->servicesList = $exec->fetchAll();
		}

		return $this->servicesList;
	}

	public function getName(): string
	{
		return $this->serviceName;
	}
}