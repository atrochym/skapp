<?php

class Router {

	private array $urlParams;
	private ?string $module = null;
	private ?string $action = null;
	private ?int $id = null;
	private ?string $resource = null;
	private array $restParams = array();
	private array $securedModules = ['customer', 'receive'];

	public function __construct()
	{
		$this->urlParser();

		// po to by nie zmieniał się locationUrl previous przy żądaniach z JS
		if (str_contains($_SERVER['REQUEST_URI'], 'json')) return;

		// $urlPath = substr($_SERVER['PATH_INFO'], 1);
		$urlPath = $_SERVER['PATH_INFO']; 

		if (isset($_SESSION['locationUrl']['this'])) {
			$_SESSION['locationUrl']['previous'] = $_SESSION['locationUrl']['this'];
		}
		$_SESSION['locationUrl']['this'] = $urlPath;

	}

	private function urlParser()
	{
		$urlPath = trim($_SERVER['PATH_INFO'], '/');
		$this->urlParams = explode('/', $urlPath);

		$this->module = $this->urlParams[0];
		array_shift($this->urlParams);

		foreach ($this->urlParams as $param)
		{
			if (ctype_alpha(str_replace('-', '', $param)) && !$this->action)
			{
				$this->action = $param;
				continue;
			}

			if (ctype_digit($param) && !$this->id)
			{
				$this->id = $param;
				continue;
			}

			if (ctype_xdigit($param) && !$this->resource)
			{
				$this->resource = $param;
				continue;
			}
			
			if (str_contains($param, ','))
			{
				$param = explode(',', $param);
				if (!ctype_alnum(str_replace('-', '', $param[0])) || !ctype_alnum(str_replace('-', '', $param[1])))
				{
					continue;
				}
				$this->restParams[$param[0]] = $param[1];
			}
		}
	}

	public function requestJson()
	{
		return json_decode(file_get_contents('php://input'), true);
	}

	public function redirect(string $destination = HOME_PAGE)
	{
		if ($destination == 'back')
		{
			$destination = $_SESSION['locationUrl']['previous'];
		}

		header('Location: ' . DIR . $destination);
		exit;
	}

	public function errorPage($code)
	{
		http_response_code($code);
		require 'error_page/error_' . $code . '.php';
		exit;
	}

	public function getModule()
	{
		$modulePath = ctype_alpha($this->module) ? "module/$this->module.php" : 'module/dashboard.php';
	
		if (!file_exists($modulePath))
		{
			// exit('router :: missing module');
			$this->errorPage(404);
			// http_response_code(404);
			// exit;
		}

		return $modulePath;
	}

	public function getAction()
	{
		return $this->action;
	}

	private function resourceAccess()
	{
		$res = $this->unmaskUrl();
		if (!$res)
		{
			$this->errorPage(404);
		}

		if (getFromSession('workerId') != $res['workerId'])
		{
			$this->errorPage(403);
		}
		// $date = makeDate();

		// if ($date <= $res['date'])
		// {
		// 	$this->errorPage(403);
		// }

		$this->id = $res['id'];
	}

	private function unmaskUrl(): false|array
	{
		// $module = explode('/', $urlPath)[0];
		
	
		// $module = $urlPath[0];
		$maskId = $this->resource;
	
		$hash = substr($maskId, -3);
		$maskId = substr($maskId, 0, -3);
		
		if (substr(hash('crc32', $maskId . $this->module), 0, 3) != $hash)
		{
			// throw new Exception('idMask hash incorrect');
			return false;
		}
		
		$randOffset = (int) hexdec($maskId[0]);
		$randOffset = strlen($maskId) - 3 < $randOffset ? round($randOffset / 2) : $randOffset;
		
		$maskId = str_split($maskId);
		
		$rand = array_splice($maskId, $randOffset, 3);
		$rand = hexdec(implode($rand));
		
		$offset = (string) $rand;
		$workerIdLength = array_splice($maskId, $offset[1], 1)[0];
		$idLength = array_splice($maskId, $offset[0] - 1, 1)[0];
	
		$maskId = implode($maskId);
	
		$integer1 = (int) substr($rand, 0, 3);
		$integer2 = (int) substr($rand, 0, 2);
		
		$id = hexdec(substr($maskId, 0, $idLength));
		$workerId = hexdec(substr($maskId, $idLength, $workerIdLength));
		$date = hexdec(substr($maskId, $idLength + $workerIdLength));
		
		$result['id'] = $id - $integer1;
		$result['workerId'] = $workerId - $integer2;
		$result['date'] = $date - $rand;
	
		if (!is_int($result['id']) || !is_int($result['workerId']) || !is_int($result['date']))
		{
			return false;
		}

		return $result;
	}

	public function getId()
	{
		if (in_array($this->module, $this->securedModules))
		{
			if (!$this->resource && !$this->id)
			{
				return null;
				// $this->errorPage(404);
			}
	
			$this->resourceAccess();
			return $this->id;
		}

		return $this->id;
	}

	public function getParam($param)
	{
		if (is_numeric($param))
		{
			return $this->getParamNumber($param);
		}

		if (isset($this->restParams[$param]))
		{
			return $this->restParams[$param];
		}
		else
		{
			exit("param $param uregistered. <br><br>");
		}
	}

	private function getParamNumber($number)
	{
		if (isset($this->urlParams[$number]))
		{
			return $this->urlParams[$number];
		}
		else
		{
			exit("param number $number uregistered. <br><br>");
		}
	}
}