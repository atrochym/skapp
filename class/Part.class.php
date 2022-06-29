<?php

class Part
{
	private int $partId;
	private array $part;
	public string $message;

	public function __construct(private Database $db)
	{}


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