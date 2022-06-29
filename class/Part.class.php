<?php

class Part
{
	private int $partId;
	private array $part;
	public string $message;

	public function __construct(private Database $db)
	{}


	public function createOrder(array $data)
	{
		$this->db->beginTransaction();

		$lastGroupId = $this->db->run('SELECT group_id FROM parts ORDER BY id DESC LIMIT 1', [])->fetchColumn();
		$lastGroupId++;
		
		foreach ($data['item'] as $item)
		{			
			do 
			{
				$part = [
					'category_id' => $item['category'],
					'group_id' => $lastGroupId,
					'name' => $item['name'],
					'price' => $item['price'],
				];

				$pardId = $this->db->insert('parts', $part);

				$partDetails = [
					'id' => $pardId,
					'seller' => $data['seller'],
					'url' => $item['url'],
					'is_used' => $item['is_used'],
					'note' => $item['note'],
					'bought' => $data['orderDate'] . ' 02:02:02',
					'creator_id' => getFromSession('workerId'),
				];
				$orderId = $this->db->insert('parts_details', $partDetails);
				$item['amount']--;
			}
			while ($item['amount'] > 0);
		}

		$this->db->commit();
		$this->message = 'success::Zamówienie zostało zapisane.';
		return $orderId;
	}

	public function createCategory(string $name)
	{
		$values = [
			'name' => strtolower($name),
		];
		$result = $this->db->run('SELECT name FROM parts_categories WHERE name = :name AND deleted = 0', $values)->fetch();

		if ($result)
		{
			$this->message = 'warn::Kategoria o tej nazwie już istnieje.';
			return false;
		}

		$values = [
			'name' => $name,
		];
		$result = $this->db->insert('parts_categories', $values);

		$this->message = 'success::Kategoria '. $name .' została utworzona.';
		return $result;
	}

	public function getAllCategories()
	{
		$categories = $this->db->run('SELECT id, name FROM parts_categories WHERE deleted = 0', [])->fetchAll();

		if (!$categories)
		{
			$this->message = 'info::Lista kategorii jest pusta.';
			return [];
		}

		return $categories;
	}
}