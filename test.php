<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

function classLoader($className) {
	// $className = strtolower($className);
	$classFile = "class/$className.class.php";

	if (!file_exists($classFile)) {
		throw new Exception('classLoader: missing class '.$className);
	}
	require_once($classFile);
	return;
}

spl_autoload_register('classLoader');

require('functions.php');


function debugMode() {
	// dla v() ve() e() i PDO
	$debugMode = true;

	if (str_contains($_SERVER['PATH_INFO'], 'json'))
		return false;

	return $debugMode;
}





class Service 
{
	private int $receiveId;
	private $servicesForReceive;

	private int $serviceId;
	private array $service;

	public function __construct(

		private object $db)
	{}

	// public function set

	public function getServicesForReceive()
	{
		if ($this->servicesForReceive)
		{
			return $this->servicesForReceive;
		}

		$services = $this->db->prepare(
			'SELECT s.*, p.id AS part_id, p.name AS part_name, p.assigned
				FROM services AS s
				LEFT JOIN parts AS p ON s.part_id = p.id
				WHERE receive_id = ?');

		$services->bindValue(1, $this->receiveId);
		$services->execute();
		$this->servicesForReceive = $services->fetchAll();

		return $this->servicesForReceive;
	}

	public function setReceiveId(int $receiveId)
	{
		$this->receiveId = $receiveId;
		$this->getServiceData();
	}

	private function getServiceData()
	{
		$service = $this->db->query(
			'SELECT s.*, r.status AS receive_status, r.deleted AS receive_deleted FROM services AS s
				LEFT JOIN receives AS r ON s.receive_id = r.id
				WHERE s.id = ' . $this->serviceId);
		$service = $service->fetch();

		$service->bindValue(1, $this->receiveId);
		$service->execute();
		$service = $service->fetch();

		if (!$service)
		{
			$this->message = '{error}Usługa o podanym identyfikatorze nie istnieje.';
			return false;
		}

		$this->service = $service;
	}
}








class ServicesList
{
	private array $servicesList = [];
	private string $serviceName;

	public function __construct(

		private object $db,
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

			if (!is_numeric($service['cost']))
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





// $db = new Database;
// // $validate = new Validate;

// // $account = new Account($db, $validate);



// // $services = new Service($db);

// session_start();
// // $_SESSION['workerId'] = 78;

// $services = new ServicesList($db, 1);
// $receive = new Receive($db, 1);

// $receive->setServicesList($services);


// // var_dump($receive);

// $receive->complete();


// print_r($receive->getMessage());



// $validate = new Validate;
// $worker = new Worker($db, $validate);

// $worker->setLogin('test2');
// $worker->setPassword('74859612');

// $test = $worker->login();

// var_dump($test);
// echo $worker->getMessage();






// devices list jako moetoda w device

// serviceslist jak osobna klasa bo czasem będzie iterować po usługach
// 

// $validate = new Validate;
// $db = new Database;

// // $input = 'vcx';
// // $validate->add('test', $input, 'email 0 20');
// // $validate->getValid();

// // ve($validate->_error);

// $customer = new Customer($db, $validate);

// $customer->setCustomerId(2);

// // var_dump($customer->devices());
// var_dump($customer->getData());

function isReceiveUnlocked($test)
	{
		switch ($test)
		{
			case 'returned':
			case 'finished':
			case 'canceled':
				echo 'error::Usługa nie mogła zostać usunięta.';
				return false;
		}

		return true;
	}

	// var_dump(isReceiveUnlocked('returned'));

	$validate = new Validate;
	$validate->add('test', 'value', '');

	$validate->getValid();

	echo($validate->message);