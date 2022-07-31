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
		if(!$this->exist()) return false;

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
		
		$values = ['receiveId' => $this->receiveId];
		$this->db->run('UPDATE receives SET finished = NULL, status = NULL WHERE id = :receiveId', $values);

		$this->message = 'success::Przyjęcie zostało otwarte.';
		return true;
	}

	public function complete()
	{
		if(!$this->exist()) return false;

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

		$values = ['receiveId' => $this->receiveId];
		$this->db->run("UPDATE receives SET finished = NOW(), status = 'finished' WHERE id = :receiveId", $values);

		$this->message = 'success::Przyjęcie ukończone, przekazane do wydania.';
		return true;
	}

	public function start()
	{
		if(!$this->exist()) return false;

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

		$values = [
			'receiveId' => $this->receiveId,
			'worker_id' => $worker
		];

		$this->db->run("UPDATE receives SET worker_id = :worker_id, started = NOW(), status = 'started' WHERE id = :receiveId", $values);

		$this->message = 'info::Rozpocząłeś zadanie.';
		return true;
	}

	public function restore()
	{
		if(!$this->exist()) return false;

		if ('started' == $this->receive['status'])
		{
			$this->message = 'warn::Wystąpił błąd w trakcie zmiany statusu, przyjęcie już jest otwarte. ?????';
			return false;
		}

		$values = ['receiveId' => $this->receiveId];
		$this->db->run("UPDATE receives SET finished = NULL, status = 'started' WHERE id = :receiveId", $values);

		$this->message = 'success::Przyjęcie zostało otwarte.';
		return true;
	}

	public function recover()
	{
		if(!$this->exist()) return false;

		$values = ['receiveId' => $this->receiveId];
		$this->db->run('UPDATE receives SET deleted = 0 WHERE id = :receiveId', $values);

		$this->message = 'success::Przyjęcie zostało przywrócone.';
		return true;
	}

	public function delete()
	{
		if(!$this->exist()) return false;

		$values = ['receiveId' => $this->receiveId];
		$this->db->run('UPDATE receives SET deleted = 1 WHERE id = :receiveId', $values);

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

		$values = [
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

		$receiveId = $this->db->insert('receives', $values);

		foreach ($data['solution'] as $service) // tak samo dodaję usługi w services.class.php
		{
			$values = [
				'receive_id' => $receiveId,
				'creator_id' => 99,
				'name' => $service['name'],
				'price' => $service['price']
			];

			$this->db->insert('services', $values);
		}

		$this->db->commit();
		$this->message = 'success::Przyjęcie zostało utworzone.';
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

	public function get(string $field = null)
	{
		if(!$this->exist()) return false;

		if (!isset($this->receive[$field]))
		{
			$this->message = 'warn::Pole nie istnieje.';
			return false;
		}
		return $this->receive[$field];
	}

	public function exist()
	{
		if (!$this->getData())
		{
			return false;
		}
		return true;
	}

	public function getData()
	{
		if ($this->receive)
		{
			return $this->receive;
		}

		$receive = $this->db->run(
			'SELECT r.id AS receive_id, r.*, d.customer_id, d.producer, d.model, c.phone
			FROM receives AS r 
			LEFT JOIN devices AS d ON r.device_id = d.id 
			LEFT JOIN customers AS c ON d.customer_id = c.id 
			WHERE r.id = :receiveId', $this->receiveId)->fetch();

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