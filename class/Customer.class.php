<?php

class Customer
{
	private int $customerId;
	private array $customer = [];
	public string $message;

	public function __construct(private Database $db)
	{}


	public function getAllCustomers() // chcę to tutaj?
	{
		$customers = $this->db->run('SELECT * FROM customers', [])->fetchAll();

		if (!$customers)
		{
			$this->message = 'info::Baza klientów jest pusta.';
			return false;
		}

		return $customers;
	}

	public function create(array $customer)
	{
		$values = [
			'phone' => $customer['phone']
		];
		$customerExist = $this->db->run('SELECT id, name FROM customers WHERE phone = :phone AND deleted = 0 LIMIT 1', $values)->fetch();

		if ($customerExist)
		{
			$this->setCustomerId($customerExist['id']);
			$this->message = 'info::Ten klient już istnieje. Oto lista napraw i urządzeń.';
			return (int) $customerExist['id'];
			// return true;
		}

		$values = [
			'creator_id' => getFromSession('workerId'),
			'phone' => &$customer['phone'],
			'name' => &$customer['name'],
			'email' => &$customer['email'],
			'non_polish' => &$customer['language']
		];
		$customerId = $this->db->insert('customers', $values);

		$this->message = 'success::Zarejestrowano pomyślnie.';
		$this->setCustomerId($customerId);
		// return true;
		return (int) $customerId;
	}

	public function update(array $customer)
	{
		if (!$this->getData())
		{
			return;
		}

		$values = ['phone' => $customer['phone']];
		$exec = $this->db->run('SELECT id FROM customers WHERE phone = :phone LIMIT 1', $values)->fetch();

		if ($exec && $exec['id'] !== (int) $customer['customerId'])
		{
			$this->message = 'warn::Klient o takim numerze już istnieje. Połączenie kont lub przeniesienie historii między klientami nie jest jeszcze możliwe.';
			return false;
		}

		$values = [
			'customer_id' => $customer['customerId'],
			'phone' => $customer['phone'],
			'name' => $customer['name'],
			'email' => $customer['email'],
			'non_polish' => $customer['language']
		];
		$this->db->run('UPDATE customers SET phone = :phone, name = :name, email = :email, non_polish = :non_polish WHERE id = :customer_id', $values);
		
		$this->message = 'success::Zmiany dla ' . $customer['phone'] . ' zostały zapisane.';
	}

	public function devices() // na pewno chcę to tutaj?
	{
		if (!$this->getData())
		{
			return;
		}
	
		$values = ['customerId' => $this->customerId];
		$devices = $this->db->run(
			'SELECT d.id AS device_id, producer, model, serial_number, d.created, d.notice, r.id AS receive_id, issue, sticker, finished
			FROM devices AS d 
			LEFT OUTER JOIN receives AS r ON d.id = r.device_id 
			WHERE d.deleted = 0 AND (r.deleted = 0 OR r.deleted IS NULL) AND d.customer_id = :customerId
			ORDER BY receive_id DESC',
			$values)->fetchAll();

		return $devices;
	}

	public function getData()
	{
		if ((int) $this->customerId < 1)
		{
			$this->message = 'warn::Niepoprawny identyfikator klienta.';
			return false;
		}

		if ($this->customer)
		{
			return $this->customer;
			// return 'wtf';
		}

		$values = ['customerId' => $this->customerId];
		$customer = $this->db->run('SELECT *, id AS customerId FROM customers WHERE id = :customerId', $values)->fetch();

		if (!$customer)
		{
			$this->message = 'warn::Klient nie istnieje.';
			return false;
		}

		$this->customer = $customer;
		return $this->customer;
	}

	public function setCustomerId(int $customerId)
	{
		$this->customerId = $customerId;
	}
}