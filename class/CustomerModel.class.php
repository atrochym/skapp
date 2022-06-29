<?php

class CustomerModel {
	private $model;
	// private $db;
	// private $message;
	private $customerId;
	private $customer;

	public function __construct(Model $model) {

		$this->model = $model;
		// $this->db = $this->model->db();
		// $this->message = $this->model->message->set();
		$this->message = $this->model->mess();


		// ogarnąć odwołanie do modelu do ustawienia komunikatu message

	}

	public function set (int $customerId) {
		$this->customerId = $customerId;

		$customer = $this->model->db->prepare('SELECT * FROM customers WHERE id=?');
		$customer->bindValue(1, $customerId);
		$customer->execute();

		if(!$customer->rowCount()) {
			// $this->model->message->set(' - taki klient nie istnieje..', 'yellow');
			$this->message->set(['messageContent' => ' - taki klient nie istnieje..', 
								'messageType' => 'yellow']);

			redirect('receive');
		}
		
		$this->customer = $customer->fetch();
	}
	

	public function getAll() {
		$customers = $this->model->db->query('SELECT * FROM customers')->fetchAll();

		if (!$customers) {
			return ['success' => false,
					'messageContent' => 'Brak zarejestrowanych klientów.', 
					'messageType' => 'blue',
					'customers' => null];
		}

		return ['success' => true,
				'customers' => $customers];

	}


	public function devices($customerId) {
		$devices = $this->model->db->query('SELECT d.id AS device_id, producer, model, serial_number, d.created, d.notice, r.id AS receive_id, issue, sticker
								FROM devices AS d 
								LEFT OUTER JOIN receives AS r ON d.id = r.device_id 
								WHERE r.deleted=0 AND d.customer_id='.$customerId);


		// // tymczasowe rozwiązanie

		// $customer = $this->model->db->prepare('SELECT * FROM customers WHERE id='.$customerId);
		// $customer->bindValue(1, $customerId);
		// $customer->execute();
		// $customer = $customer->fetch();

		$this->set($customerId);

		// tu błąd: jeśli jest samo urządzenie bez przyjęcia lub przyjęcie jest usunięte to nie pokaże się w sekcji urządzenia (WHERE r.deleted=0)

		if(!$devices->rowCount()) {
			// $this->model->message->set(' - taki klient nie istnieje 2.. (ale to nie prawda, spr)', 'yellow');
			// redirect('receive');

			return ['success' => false,
					'messageContent' => ' - taki klient nie istnieje 2.. (ale to nie prawda, spr)', 
					'messageType' => 'yellow'];
		}

		return ['devices' => $devices->fetchAll(),
				'name' => $this->customer['name'],
				'phone' => $this->customer['phone'],
				'email' => $this->customer['email'],
				'nonPolish' => $this->customer['non_polish'],
				'customerId' => $customerId,];
	}



	public function create(array $data) {
		// gdzieś walidacja
		$phone = trim($data['phone']);
		$name = trim($data['name']);
		$email = trim($data['email']);
		$language = (int)$data['non_polish'];
	
		$customer = $this->model->db->prepare('SELECT id, deleted, name FROM customers WHERE phone = :phone LIMIT 1');
		$customer->bindValue(':phone', $phone);
		$customer->execute();

		if($customer->rowCount() == 0) {
			$new = $this->model->db->prepare('INSERT INTO customers (id, creator_id, phone, name, email, non_polish) VALUES (NULL, :creator_id, :phone, :name, :email, :non_polish)');
			$data = array (
				'creator_id' => $this->model->get('workerId'),
				'phone' => $phone,
				'name' => $name,
				'email' => $email,
				'non_polish' => $language
			);
			
			if($new->execute($data) > 0) {
				// $this->model->message->set('Klient '.$phone.' został zarejestrowany.', 'success');
				// redirect('receive/customer/'.$this->model->db->lastInsertId());	

				$message = ['success' => true,
							'messageContent' => 'Klient '.$phone.' został zarejestrowany.', 
							'messageType' => 'success',
							'customerId' => $this->model->db->lastInsertId()];
				return $message;

			} else {
				// $this->model->message->set('pizda '.$customer->rowCount(), 'warn');
				$message = ['success' => false,
							'messageContent' => 'Błąd podczas rejestracji klienta.', 
							'messageType' => 'red'];
				return $message;
			}
		} else {
			$customer = $customer->fetch();
	
			if($customer['deleted']) {
				// $this->model->message->set('Takiego ziomka już mamy ale usunięte, zwał się <i>'.$customer['name'].'</i>. Odzyskać?', 'yellow');
				// redirect('receive');

				$message = ['success' => false,
							'messageContent' => 'Takiego ziomka już mamy ale usunięte, zwał się <i>'.$customer['name'].'</i>. Odzyskać?', 
							'messageType' => 'yellow'];
				return $message;
			}
	
			// $this->model->message->set('Takiego ziomka już mamy. Oto kompy.', 'info');
			// redirect('receive/customer/'.$customer['id']);
			
			$message = ['success' => true,
						'messageContent' => 'Takiego ziomka już mamy. Oto kompy.', 
						'messageType' => 'info',
						'customerId' => $customer['id']];
			return $message;
		}
	}
	
	
	public function edit(int $customerId) {
		$customer = $this->model->db->query('SELECT * FROM customers WHERE id='.$customerId);
		$customer = $customer->fetch();
		
		if(!$customer) {
			return ['success' => false,
					'messageContent' => 'Klient nie istnieje.', 
					'messageType' => 'red'];
		}


		return ['success' => true,
				'customerId' => $customerId,
				'phone' => $customer['phone'],
				'name' => $customer['name'],
				'email' => $customer['email'],
				'nonPolish' => $customer['non_polish']];
	}

	public function save(array $data) {

		$customerId = $data['customer_id'];
		$phone = $data['phone'];
		$name = $data['name'];
		$email = $data['email'];
		$language = (int)$data['non_polish'];
	
		$customer = $this->model->db->prepare('SELECT id FROM customers WHERE phone = :phone LIMIT 1');
		$customer->bindValue(':phone', $phone);
		$customer->execute();
		
		$test = $customer->fetch();

		if($test['id'] !== $customerId && $customer->rowCount() > 0) {

			return ['success' => false,
					'messageContent' => 'Klient o takim numerze już istnieje. Połączenie kont lub przeniesienie historii między klientami nie jest jeszcze możliwe.',
					'messageType' => 'red'];
		}

		$new = $this->model->db->prepare('UPDATE customers SET phone = :phone, name = :name, email = :email, non_polish = :non_polish WHERE id = :customer_id');

		$data = ['customer_id' => $customerId,
				'phone' => $phone,
				'name' => $name,
				'email' => $email,
				'non_polish' => $language];

		if(!$new->execute($data)) {
			return ['success' => false,
					'messageContent' => 'Wystąpił błąd podczas zapisywania zmian.',
					'messageType' => 'red'];

		}
		
		return ['success' => true,
				'messageContent' => 'Zmiany dla '.$phone.' zostały zapisane.',
				'messageType' => 'green'];

	}


	// metoda nie używana, "konflikt nie istnieje" bo jedno ID pochodzi z $_POST, a nie z bazy :<
	// edit mogę w kontrolerze podać numer i tutaj go podmienić w $data i zwrócić jako konflikt
	public function conflict(array $data) {
		if(!(int)$data['main'] || !(int)$data['conflict']) {
			throw new Exception('ERR: model conflict input data wrong');
		}

		$customer1 = $this->model->db->prepare('SELECT id, name, phone, email, non_polish FROM customers WHERE id = ? LIMIT 1');
		$customer1->bindValue(1, $data['main']);
		$customer1->execute();
		$customer1 = $customer1->fetch();

		$customer2 = $this->model->db->prepare('SELECT id, name, phone, email, non_polish FROM customers WHERE id = ? LIMIT 1');
		$customer2->bindValue(1, $data['conflict']);
		$customer2->execute();		
		$customer2 = $customer2->fetch();

		if($customer1['phone'] !== $customer2['phone']) {
			// $this->model->message->set('Wskazany konflikt nie istnieje.', 'red');
			// redirect('receive/customer/'.$data['main']);

			return ['success' => true,
					'messageContent' => 'Wskazany konflikt nie istnieje.',
					'messageType' => 'yellow'];
		}

		return ['customer_id' => $customer1['id'],
				'phone' => $customer1['phone'],
				'name' => $customer1['name'],
				'email' => $customer1['email'],
				'non_polish' => $customer1['non_polish'],

				'customer_id_2' => $customer2['id'],
				'phone_2' => $customer2['phone'],
				'name_2' => $customer2['name'],
				'email_2' => $customer2['email'],
				'non_polish_2' => $customer2['non_polish'],];
	}

	public function details(int $customerId) {

		$customer = $this->model->db->prepare('SELECT id, name, phone, email, non_polish FROM customers WHERE id = ? LIMIT 1');
		$customer->bindValue(1, $customerId);
		$customer->execute();
		$customer = $customer->fetch();

		if(!$customer) {
			return ['success' => false,
					'messageContent' => 'Kient nie istnieje.',
					'messageType' => 'red'];
		}

		return ['success' => true,
				'customer' => $customer];
	}
}

?>