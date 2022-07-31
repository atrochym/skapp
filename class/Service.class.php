<?php

class Service
{
	private int $serviceId;
	private array $service = [];
	private int $workerId;
	public string $message;

	public function __construct(private Database $db)
	{
		$this->workerId = getFromSession('workerId');
	}
	
	public function update(array $data)
	{
		if (!$this->getData() || $this->isReceiveLocked() || $this->isServiceLocked())
		{
			return false;
		}

		$values = [
			'serviceId' => $this->serviceId,
			'name' => $data['name'],
			'price' => $data['price'],
		];
		$this->db->run('UPDATE services SET name = :name, price = :price WHERE id = :serviceId', $values);
		$this->message = 'success::Zmiany zostały zapisane.';

		return true;
	}

	public function create(array $data)
	{
		$this->db->beginTransaction();

		foreach ($data['solution'] as $service)
		{
			$values = [
				'receive_id' => $data['receiveId'],
				'creator_id' => getFromSession('workerId'),
				'name' => $service['name'],
				'price' => $service['price']
			];

			$this->db->insert('services', $values);
		}

		$this->db->commit();
		$this->message = 'info::Przyjęcie zostało zaktualizowane.';
		return true;
	}
	
	public function restore()
	{
		if (!$this->getData() || $this->isReceiveLocked())
		{
			return false;
		}

		$values = [
			'serviceId' => $this->serviceId,
			'workerId' => $this->workerId,
		];
		$this->db->run("UPDATE services SET status = 'opened', worker_id = :workerId WHERE id = :serviceId", $values);
		$this->message = 'success::Usługa '.  $this->service['name'] .' została przywrócona.';

		return true;
	}

	public function cancel()
	{
		if (!$this->getData() || $this->isReceiveLocked() || $this->isServiceLocked())
		{
			return false;
		}

		$values = [
			'serviceId' => $this->serviceId,
			'workerId' => $this->workerId,
		];
		$this->db->run("UPDATE services SET status = 'canceled', worker_id = :workerId WHERE id = :serviceId", $values);
		$this->message = 'success::Usłudze '.  $this->service['name'] .' został cofnięty status ukończonej.';

		return true;
	}

	public function incomplete()
	{
		if (!$this->getData() || $this->isReceiveLocked())
		{
			return false;
		}

		$values = [
			'serviceId' => $this->serviceId,
			'workerId' => $this->workerId,
		];
		$this->db->run("UPDATE services SET status = 'opened', worker_id = :workerId WHERE id = :serviceId", $values);
		$this->message = 'success::Usłudze '.  $this->service['name'] .' został cofnięty status ukończonej.';

		return true;
	}

	public function complete()
	{
		if (!$this->getData() || $this->isReceiveLocked() || $this->isServiceLocked())
		{
			return false;
		}

		if (!is_numeric($this->service['price']))
		{
			$this->message = 'warn::Cena dla usługi "' . $this->service['name'] . '" nie jest jednoznaczna, doprecyzuj ją.';
			return false;
		}

		$values = [
			'serviceId' => $this->serviceId,
			'workerId' => $this->workerId,
		];
		$this->db->run("UPDATE services SET status = 'finished', finished = NOW(), worker_id = :workerId WHERE id = :serviceId", $values);
		$this->message = 'success::Usługa '.  $this->service['name'] .' została ukończona.';

		return true;
	}

	public function setWorker(int $workerId)
	{
		if (!$this->getData() || $this->isReceiveLocked() || $this->isDeleted())
		{
			return false;
		}

		$values = [
			'serviceId' => $this->serviceId,
			'workerId' => $workerId,
		];
		$this->db->run('UPDATE services SET worker_id = :workerId WHERE id = :serviceId', $values);
		$this->message = 'success::Przypisano dla: '. $workerId .'.';

		return true;
	}

	public function recover()
	{
		if (!$this->getData() || $this->isReceiveLocked())
		{
			return false;
		}

		$values = [
			'serviceId' => $this->serviceId,
			'workerId' => $this->workerId,
		];
		$this->db->run('UPDATE services SET deleted = 0, worker_id = :workerId WHERE id = :serviceId', $values);
		$this->message = 'success::Usługa ' . $this->service['name'] . 'została przywrócona.';

		return true;
	}

	public function delete()
	{
		if (!$this->getData() || $this->isFinished() || $this->isOrdered() || $this->isReceiveLocked() || $this->isDeleted())
		{
			return false;
		}

		$values = [
			'workerId' => $this->workerId,
			'serviceId' => $this->serviceId,
		];
		$this->db->run('UPDATE services SET deleted = 1, worker_id = :workerId WHERE id = :serviceId', $values);
		$this->message = 'success::Usługa ' . $this->service['name'] . 'została usunieta.';

		return true;
	}

	private function isFinished()
	{
		if ('finished' == $this->service['status'])
		{
			return true;
		}

		$this->message = 'warn::Usługa ' . $this->service['name'] . ' jest oznaczona jako ukończona. Nie można jej usunąć, zmień jej status.';
		return false;
	}

	private function isOrdered()
	{
		if ('part_ordered' == $this->service['status'])
		{
			return true;
		}

		$this->message = 'warn::Usługa ' . $this->service['name'] . ' czeka na zamówioną część. Nie można jej usunąć, zmień jej status.';
		return false;
	}

	private function isDeleted()
	{
		if ($this->service['deleted'])
		{
			$this->message = 'warn::Usługa ' . $this->service['name'] . ' jest usunięta. Nie można jej edytować, zmień jej status.';
			return true;
		}

		return false;
	}


	private function isServiceLocked()
	{
		switch ($this->service['status'])
		{
			case 'finished':
			case 'part_ordered':
			case 'canceled':
			case 'waiting_decision':
				$this->message = 'error::Usługa nie mogła zostać zmieniona ponieważ jest zamknięta lub zablokowana.';
				return true;
		}

		if ($this->service['deleted'])
		{
			$this->message = 'error::Usługa nie mogła zostać zmieniona ponieważ jest usunięta.';
			return true;
		}
		
		return false;
	}

	private function isReceiveLocked()
	{
		switch ($this->service['receive_status'])
		{
			case 'returned':
			case 'finished':
			case 'canceled':
				$this->message = 'error::Usługa nie mogła zostać zmieniona, przyjęcie jest zamknięte lub zablokowane.';
				return true;
		}

		if ($this->service['receive_deleted'])
		{
			$this->message = 'error::Usługa nie mogła zostać zmieniona, przyjęcie jest usunięte.';
			return true;
		}

		return false;
	}

	public function getData()
	{
		if ($this->serviceId < 1)
		{
			$this->message = 'warn::Niepoprawny identyfikator usługi.';
			return false;
		}

		if ($this->service)
		{
			return $this->service;
		}

		$service = $this->db->run(
			'SELECT s.*, r.status AS receive_status, r.deleted AS receive_deleted FROM services AS s
			LEFT JOIN receives AS r ON s.receive_id = r.id
			WHERE s.id = :serviceId', $this->serviceId)->fetch();

		if (!$service)
		{
			$this->message = 'warn::Usługa o podanym identyfikatorze nie istnieje.';
			return false;
		}

		$this->service = $service;
		return $this->service;
	}
	
	public function setServiceId(int $serviceId)
	{
		$this->serviceId = $serviceId;
	}
}