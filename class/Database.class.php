<?php

class Database extends PDO
{
	public $pdo;
	public $test = 'xD';

	public function __construct()
	{
		try
		{
			parent::__construct(DB_DSN, DB_USER, DB_PASS);
			parent::setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			parent::setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		
			// if(debugMode())
			// {
			// 	parent::setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			// }
			// else
			// {
			// 	parent::setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
			// }
		}
		catch (PDOException $e)
		{
			if(!debugMode())
			{
				exit('PDO Exception, check in debug mode.');
			}

			exit('PDO error connection.');
		}
	}

	public function run(string $query, array $data): mixed
	{
		try
		{
			$exec = parent::prepare($query);

			foreach ($data as $key => $value)
			{
				$exec->bindValue($key, $value);
			}
			
			$exec->execute();

			// if (!$exec->rowCount())
			// {
			// 	throw new PDOException('no rows affected');
			// 	exit;
			// }

			return $exec;
		}
		catch (PDOException $e)
		{
			if(!debugMode())
			{
				exit('PDO Exception, check in debug mode.');
			}

			echo $e->getMessage() . '<br>';
			echo $e->getTrace()[1]['file'] . ' :: '. $e->getTrace()[1]['line'] . '<br>';
			echo json_encode($data);
			exit;
		}
	}

	public function insert(string $table, array $data): int
	{
		try
		{
			$columns = implode(', ', array_keys($data));
			$placeholders = ':'.implode(', :', array_keys($data));	
			$exec = parent::prepare("INSERT INTO $table ($columns) VALUES ($placeholders)");
			$exec->execute($data);

			return parent::lastInsertId();
		}
		catch (PDOException $e)
		{
			if(!debugMode())
			{
				exit('PDO Exception, check in debug mode.');
			}

			echo $e->getMessage() . '<br>';
			echo $e->getTrace()[0]['file'] . ' :: '. $e->getTrace()[0]['line'] . '<br>';
			echo json_encode($data);
			exit;
		}
	}
}