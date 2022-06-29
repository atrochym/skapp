<?php

class PartModel {

	private $model;
	private $part;
	private $partId;
	// private $services;
	// private $result = [];

	public function __construct(Model $model, int $partId = 0) {

		$this->model = $model;
		$this->partId = $partId;

		if ($partId) {
			$this->setPart();
		}
		// $this->db = $this->model->db();
	}






	private function setPart() {

		$part = $this->model->db->query('SELECT * FROM parts AS p
										LEFT JOIN parts_details AS pd ON pd.id = p.id
										WHERE p.id = ' . $this->partId);
		$part = $part->fetch();

		if (!$part) {
			return ['success' => false,
					'messageContent' => 'Wskazana część magazynowa nie istnieje lub wystąpił błąd pobierania danych.', 
					'messageType' => 'red'];
		}

		$this->part = $part;
		return ['success' => true];
	}

	public function saveOrder(array $data)
	{

// ogarnąć dodawanie po ilości sztuk
// co z plombami?
// external_id z linka allegro

// state ordered? returned?
		$data['item'] = [...$data['item']];

		$validate = new Validate;
		$validate->add('orderDate', $data['order-date'], 'date require');
		$validate->add('seller', $data['seller'], 'text require 3 50');
		$validate->add('deliveryCost', $data['delivery-cost'], 'float 3 6');

		foreach ($data['item'] as $key => $item)
		{
			$validate->add('category_'.$key, $item['category'], 'integer require');
			$validate->add('url_'.$key, $item['url'], 'url require 5 300');
			$validate->add('note_'.$key, $item['note'], 'text 0 1000');
			$validate->add('name_'.$key, $item['name'], 'text require 3 300');
			$validate->add('price_'.$key, $item['price'], 'float require 4 8');
			$validate->add('amount_'.$key, $item['amount'], 'integer require 1 2');
		}

		if (!$validate->getValid())
		{
			return ['success' => false,
					'messageContent' => 'Utworzenie przyjęcia nie powiodło się (valid failed). ' .$validate->_error,
					'messageType' => 'red'];
		}

		try {

			$this->model->db->beginTransaction();

			$lastGroupId = $this->model->db->query('SELECT group_id FROM parts ORDER BY id DESC LIMIT 1')->fetchColumn();
			$lastGroupId++;
			$itemsCount = count($data['item']);

			for ($i = 0; $i < $itemsCount; $i++)
			{
				$isPartUsed = isset($item[$i]['cb-is_used']) ? 1 : 0;

				do
				{
					$part = [
						'id' => NULL,
						'category_id' => $validate->{'category_' . $i},
						'group_id' => $lastGroupId,
						'name' => $validate->{'name_' . $i},
						'price' => $validate->{'price_' . $i},];


					$receive = $this->model->db->prepare(insert('parts', $part));
					$receive->execute($part);

					$partDetails = [
						'id' => $this->model->db->lastInsertId(),
						'seller' => $validate->seller,
						'url' => $validate->{'url_' . $i},
						'is_used' => $isPartUsed,
						'note' => $validate->{'note_' . $i},
						'bought' => $validate->orderDate . ' 01:01:01',
						'creator_id' => $_SESSION['workerId'],];

					$receive2 = $this->model->db->prepare(insert('parts_details', $partDetails));
					$receive2->execute($partDetails);

					$validate->{'amount_' . $i}--;
				}
				while ($validate->{'amount_' . $i} > 0);
			}

			$this->model->db->commit();

			return ['success' => true,
					'messageContent' => 'Zamówienie zostało zapisane.',
					'messageType' => 'green'];


		} catch (PDOException $e) {
			$this->model->db->rollBack();
			
			return ['success' => false,
					'messageContent' => 'Utworzenie przyjęcia nie powiodło się. <br>' . $e,
					'messageType' => 'red'];
			

			// exit ('Error:' .$e);
		}
	}


	public function saveCategory(array $data) {

		$category = trim(strip_tags($data['category']));

		if (strlen($category) < 3) {
			return ['success' => false,
					'messageContent' => 'Nazwa kategorii jest zbyt krótka, minum to 3 znaki.', 
					'messageType' => 'yellow'];
		}

		$result = $this->model->db->prepare('SELECT name FROM parts_categories WHERE name = ? AND deleted = 0');
		$result->bindValue(1, strtolower($category));
		$result->execute();

		if ($result->fetch()) {
			return ['success' => false,
					'messageContent' => 'Kategoria o tej nazwie już istnieje.', 
					'messageType' => 'yellow'];
		}

		$result = $this->model->db->prepare('INSERT INTO parts_categories (id, name) VALUES (NULL, ?)');
		$result->bindValue(1, $category);
		$result = $result->execute();

		if($result) {
			return ['success' => true,
					'messageContent' => 'Kategoria '. $category .' została utworzona.', 
					'messageType' => 'green',
					'categoryName' => $category,
					'categoryId' => $this->model->db->lastInsertId()];
		} else {
			return ['success' => false,
					'messageContent' => 'Wystąpił błąd przy zapisie danych.', 
					'messageType' => 'red'];
		}
	}

	// chyba powinna być gdzie indziej

	public function partCategories() {

		$categories = $this->model->db->query('SELECT id, name FROM parts_categories WHERE deleted = 0')->fetchAll();

		if (!$categories) {
			$categories = null;
		}

		return ['partCategories' => $categories];
	}
}

?>