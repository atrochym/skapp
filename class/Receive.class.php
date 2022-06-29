<?php

class Receive
{
	private $receiveId;
	private array $receive = [];
	public string $message;
	private ServicesList $services;

	public function __construct(private Database $db)
	{}

	public function ____getReceive()
	{
		return $this->receive;
	}

// sql tutaj? komunikaty błędów gdzieś indziej?
// może zdefinować gdzieś konkretne metody operacji na bazie danych?

	public function open()
	{
		if (!$this->getData())
		{
			return;
		}

		if ($this->receive['deleted'])
		{
			$this->message = 'error::Nie możesz edytować usuniętego przyjęcia.';
			return false;
		}

		if ('returned' == $this->receive['status'])
		{
			$this->message = 'error::Urządzenie z tego przyjęcia zostało już wydane, nie możesz go edytować.';
			return false;
		}
		
		$data = ['receiveId' => $this->receiveId];
		$this->db->run('UPDATE receives SET finished = NULL, status = NULL WHERE id = :receiveId', $data);

		$this->message = 'success::Przyjęcie zostało otwarte.';
		return true;
	}

	public function complete()
	{
		if (!$this->getData())
		{
			return;
		}

		if ('finished' == $this->receive['status'])
		{
			$this->message = 'warn::Wystąpił błąd w trakcie zmiany statusu, przyjęcie już jest zamknięte.';
			return false;
		}

		if (!$this->services->checkFinishedServices())
		{
			$this->message = 'error::Usługa '. $this->services->getName() . ' nie została jednoznacznie zakończona.';
			return false;
		}

		if (!$this->services->checkUnequivocalCost())
		{
			$this->message = 'error::Usługa '. $this->services->getName() . ' nie ma sprecyzowanego kosztu.';
			return false;
		}

		$data = ['receiveId' => $this->receiveId];
		$this->db->run("UPDATE receives SET finished = NOW(), status = 'finished' WHERE id = :receiveId", $data);

		$this->message = 'success::Przyjęcie ukończone, przekazane do wydania.';
		return true;
	}

	public function start()
	{
		if (!$this->getData())
		{
			return;
		}

		if ($this->receive['deleted'])
		{
			$this->message = 'error::To przyjęcie zostało usunięte, nie można go rozpocząć.';
			return false;
		}

		if (null !== $this->receive['status'])
		{
			$this->message = 'warn::To przyjęcie już zostało rozpoczęte przez '. $this->receive['worker_id'] .'.';
			return false;
		}

		$worker = $_SESSION['workerId'];

		$data = [
			'receiveId' => $this->receiveId,
			'worker_id' => $worker
		];

		$this->db->run("UPDATE receives SET worker_id = :worker_id, started = NOW(), status = 'started' WHERE id = :receiveId", $data);

		$this->message = 'info::Rozpocząłeś zadanie.';
		return true;
	}

	public function restore()
	{
		if (!$this->getData())
		{
			return;
		}

		if ('started' == $this->receive['status'])
		{
			$this->message = 'warn::Wystąpił błąd w trakcie zmiany statusu, przyjęcie już jest otwarte. ?????';
			return false;
		}

		$data = ['receiveId' => $this->receiveId];
		$this->db->run("UPDATE receives SET finished = NULL, status = 'started' WHERE id = :receiveId", $data);

		$this->message = 'success::Przyjęcie zostało otwarte.';
		return true;
	}

	public function recover()
	{
		if (!$this->getData())
		{
			return;
		}

		$data = ['receiveId' => $this->receiveId];
		$this->db->run('UPDATE receives SET deleted = 0 WHERE id = :receiveId', $data);

		$this->message = 'success::Przyjęcie zostało przywrócone.';
		return true;
	}

	public function delete()
	{
		if (!$this->getData() || $this->isLocked())
		{
			return;
		}

		$data = ['receiveId' => $this->receiveId];
		$this->db->run('UPDATE receives SET deleted = 1 WHERE id = :receiveId', $data);

		$this->message = 'success::Przyjęcie zostało usunięte.';
		return true;
	}
	
	// public function setReceive(int $receiveId)
	// {
	// 	$this->receiveId = $receiveId;
	// 	$this->getReceiveData();
	// }

	public function __getMessage() // do usunięcia
	{
		return $this->message;
	}

	public function setServicesList(ServicesList $servicesList)
	{
		$this->services = $servicesList;
	}

	public function create(array $data)
	{
		$tag = $this->generateReceiveTag();

		$this->db->beginTransaction();

		$data = [
			'tag' => $tag,
			'device_id' => $data['device_id'],
			'creator_id' => getFromSession('workerId'),
			'worker_id' => 0,
			'password' => $data['password'],
			'issue' => $data['issue'],
			'notice' => $data['notice'],
			'extra_items' => (int) isset($data['cb-extra_items']),
			'express' => (int) isset($data['cb-express']),
			'confirmation_receipt' => (int) isset($data['cb-confirmation_receipt']),
			'draft' => (int) isset($data['cb-draft']),
			'advance_value' => $data['advance_value'],
			'predicted_datetime' => $data['predicted_datetime']
		];

		$receiveId = $this->db->insert('receives', $data);

		foreach ($data['solution'] as $service)
		{
			$data = [
				'receive_id' => $receiveId,
				'creator_id' => 99,
				'name' => $service['name'],
				'price' => $service['price']
			];

			$this->db->insert('services', $data);
		}

		$this->db->commit();
		return $receiveId;
		// return true;

	}

	private function isLocked()
	{
		if ($this->receive['status'] !== NULL)
		{
			$this->message = 'warn::Nie można wykonać tej akcji, przyjęcie już jest otwarte lub zablokowane.';
			return true;
		}
		return false;
	}

	private function generateReceiveTag()
	{
		$lastTag = $this->db->query('SELECT tag FROM receives ORDER BY id DESC LIMIT 1')->fetchColumn();

		if ((int) strlen($lastTag) < 5)
		{
			throw new Exception('Receive :: deviceTag not correct');
			exit;
		}
	
		$month = substr($lastTag, -4, 2);
		$year = substr($lastTag, -2, 2);
		$deviceTag = substr($lastTag, 0, -4);
		
		$deviceTag = $year <> date('y') ? 1 : $deviceTag;
		$deviceTag = $month <> date('m') ? 1 : ++$deviceTag;
		
		return $deviceTag . date('my');
	}


	// private function getReceiveData()
	// {
	// 	if (empty($this->receive))
	// 	{
	// 		$data = ['id' => $this->receiveId];
	// 		$exec = $this->db->run(
	// 			'SELECT r.id AS receive_id, r.*, d.customer_id, d.producer, d.model, c.telephone
	// 			FROM receives AS r 
	// 			LEFT JOIN devices AS d ON r.device_id = d.id 
	// 			LEFT JOIN customers AS c ON d.customer_id = c.id 
	// 			WHERE r.id = :id', $data)->fetch();

	// 		if (!$exec)
	// 		{
	// 			// throw new Exception('Receive :: record not exist');
	// 			$this->message = 'error::Przyjęcie o podanym identyfikatorze nie istnieje.';
	// 			return false;
	// 		}

	// 		$this->receive = $exec;
	// 	}

	// 	return $this->receive;
	// }

	public function getData()
	{
		if ((int) $this->receiveId < 1)
		{
			$this->message = 'warn::Niepoprawny identyfikator klienta.';
			return false;
		}

		if ($this->receive)
		{
			return $this->receive;
		}

		$data = ['receiveId' => $this->receiveId];
		$receive = $this->db->run(
			'SELECT r.id AS receive_id, r.*, d.customer_id, d.producer, d.model, c.phone
			FROM receives AS r 
			LEFT JOIN devices AS d ON r.device_id = d.id 
			LEFT JOIN customers AS c ON d.customer_id = c.id 
			WHERE r.id = :receiveId', $data)->fetch();

		if (!$receive)
		{
			$this->message = 'warn::Przyjęcie o podanym identyfikatorze nie istnieje.';
			return false;
		}

		$this->receive = $receive;
		return $this->receive;
	}

	public function setReceiveId(int $receiveId)
	{
		$this->receiveId = $receiveId;
	}
}