<?php

class Note
{
	public $message;
	private $noteId;
	private $note;

	public function __construct(private Database $db)
	{}

	public function create(array $data)
	{
		
		$values = [
			'receive_id' => $data['receiveId'],
			'worker_id' => getFromSession('workerId'),
			'note' => $data['note'],
		];
		$exec = $this->db->insert('receives_notes', $values);

		$this->message = 'info::Notatka została dodana.';
		return $exec;
	}

	public function update(string $data)
	{
		if (!$this->getData() || $this->isFinished() || $this->isDeleted())
		{
			return false;
		}

		$values = [
			'noteId' => $this->noteId,
			'note' => $data,
		];
		$this->db->run('UPDATE receives_notes SET note = :note WHERE id = :noteId', $values);
		$this->message = 'info::Komentarz został zaktualizowany.';

		return true;
	}

	public function pin()
	{
		if (!$this->getData() || $this->isFinished() || $this->isDeleted())
		{
			return false;
		}

		$values = [
			'noteId' => $this->noteId,
		];
		$this->db->run('UPDATE receives_notes SET pinned = 1 WHERE id = :noteId', $values);

		return true;
	}

	public function unpin()
	{
		if (!$this->getData() || $this->isFinished() || $this->isDeleted())
		{
			return false;
		}

		$values = [
			'noteId' => $this->noteId,
		];
		$this->db->run('UPDATE receives_notes SET pinned = 0 WHERE id = :noteId', $values);

		return true;
	}
	
	public function delete()
	{
		if (!$this->getData() || $this->isFinished() || $this->isDeleted())
		{
			return false;
		}

		$values = [
			'noteId' => $this->noteId,
		];
		$this->db->run('UPDATE receives_notes SET deleted = 1 WHERE id = :noteId', $values);
		$this->message = 'success::Komentarz został usuniety.';

		return true;
	}

	public function recover()
	{
		if (!$this->getData() || $this->isFinished())
		{
			return false;
		}

		$values = [
			'noteId' => $this->noteId,
		];
		$this->db->run('UPDATE receives_notes SET deleted = 0 WHERE id = :noteId', $values);
		$this->message = 'success::Komentarz został przywrócony.';

		return true;
	}
	
	private function isFinished()
	{
		if ('finished' == $this->note['receive_status'] || 'canceled' == $this->note['receive_status'])
		{
			$this->message = 'warn::Przyjęcie jest oznaczone jako ukończone. Edycja nie jest możliwa.';
			return true;
		}

		return false;
	}

	private function isDeleted()
	{
		if ($this->note['receive_deleted'])
		{
			$this->message = 'warn::Przyjęcie jest usunięte. Edycja nie jest możliwa.';
			return true;
		}

		return false;
	}
	
	private function setState(string $key, mixed $value)
	{
		$values = [
			'noteId' => $this->noteId,
			'value' => $value
		];
		$this->db->run("UPDATE receives_notes SET $key = :value WHERE id = :noteId", $values);
	}

	public function get(string $field = null)
	{
		if (!$this->getData())
		{
			return false;
		}
		if (!isset($this->note[$field]))
		{
			$this->message = 'warn::Pole nie istnieje.';
			return false;
		}
		return $this->note[$field];
	}

	public function getData()
	{
		if ($this->noteId < 1)
		{
			$this->message = 'warn::Niepoprawny identyfikator notatki.';
			return false;
		}

		if ($this->note)
		{
			return $this->note;
		}

		$note = $this->db->run(
			'SELECT n.*, r.status AS receive_status, r.deleted AS receive_deleted FROM receives_notes AS n
			LEFT JOIN receives AS r ON n.receive_id = r.id
			WHERE n.id = :noteId', $this->noteId)->fetch();

		if (!$note)
		{
			$this->message = 'warn::Usługa o podanym identyfikatorze nie istnieje.';
			return false;
		}

		$this->note = $note;
		return $this->note;
	}
	
	public function setNoteId(int $noteId)
	{
		$this->noteId = $noteId;
	}
}