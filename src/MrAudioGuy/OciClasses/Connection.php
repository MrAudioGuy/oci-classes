<?php

	namespace MrAudioGuy\OciClasses;

	use MrAudioGuy\Commons\Error;
	use Config;

	class Connection
	{
		protected $resource;
		protected $errorStack;
		protected $connectionString;
		protected $connectionSettings;
		protected $attributes;

		protected function & setConnectionString ()
		{
			isset($this->connectionSettings['protocol']) ? : $this->connectionSettings['protocol'] = "TCP";
			isset($this->connectionSettings['host']) ? : $this->connectionSettings['host'] = "localhost";
			isset($this->connectionSettings['port']) ? : $this->connectionSettings['port'] = "1521";

			$this->connectionString = <<<"CONN"
		(DESCRIPTION =
			(ADDRESS =
				(PROTOCOL = {$this->connectionSettings['protocol']})
				(HOST = {$this->connectionSettings['host']})
				(PORT = {$this->connectionSettings['port']})
			)
			(CONNECT_DATA =
				(SERVICE_NAME = {$this->connectionSettings['service_name']})
			)
		)
CONN;
			/// (SERVER = {$this->connectionSettings['database']})
			/// (INSTANCE_NAME = {$this->connectionSettings['instance_name']})
			return $this;
		}

		protected function & connect ($new = false, $suppress = false)
		{
			$this->setConnectionString();
			isset($this->connectionSettings['session_mode']) ? : $this->connectionSettings['session_mode'] = null;

			if ($new)
			{
				$resource = oci_new_connect($this->connectionSettings['username'],
											$this->connectionSettings['password'],
											$this->connectionString,
											$this->connectionSettings['charset'],
											$this->connectionSettings['session_mode']);
				if (!$resource)
				{
					$t                             = count($this->errorStack);
					$this->errorStack[$t]['error'] = oci_error($this->resource);
					$this->errorStack[$t]['trace'] = debug_backtrace(0, 3);
					Error::getMessage(null, $this->errorStack[$t]['trace'], $this->errorStack[$t]['error'], $suppress);
				}

				return $resource;
			}
			else
			{
				$this->resource = oci_connect($this->connectionSettings['username'], $this->connectionSettings['password'], $this->connectionString, $this->connectionSettings['charset'], $this->connectionSettings['session_mode']);
				if (!$this->resource)
				{
					$t                             = count($this->errorStack);
					$this->errorStack[$t]['error'] = oci_error($this->resource);
					$this->errorStack[$t]['trace'] = debug_backtrace(0, 3);
					Error::getMessage(null, $this->errorStack[$t]['trace'], $this->errorStack[$t]['error'], $suppress);
				}

				return $this->resource;
			}
		}

		public function & createConnection ($new = false)
		{
			$connection = $this->connect($new);

			return $connection;
		}

		protected function & getResource ()
		{
			if ($this->resource == null)
			{
				$this->createConnection();
			}

			return $this->resource;
		}

		public function & close ()
		{
			if (is_resource($this->resource) && (get_resource_type($this->resource) == "oci8 connection"))
			{
				oci_close($this->resource);
			}
			else
			{
				unset($this->resource);
			}

			return $this;
		}

		public function getLastError ()
		{
			return $this->errorStack[count($this->errorStack) - 1];
		}

		public function & flushErrors ()
		{
			$this->errorStack = [];

			return $this;
		}

		public function & __get ($name)
		{
			$method = 'get' . ucfirst($name);
			if (method_exists($this, $method))
			{
				return $this->$method();
			}
			else
			{
				if (array_key_exists($name, $this->attributes))
				{
					return $this->attributes[$name];
				}
			}
			$trace = debug_backtrace(1);
			trigger_error(
				'Undefined property via __get(): ' . $name .
				' in ' . $trace[0]['file'] .
				' on line ' . $trace[0]['line'],
				E_USER_NOTICE);
			$k = null;

			return $k;
		}

		public function & __set ($name, $value)
		{
			$method = 'set' . ucfirst($name);
			if (method_exists($this, $method))
			{
				$this->$method($value);
			}
			else
			{
				$this->attributes[$name] = $value;

				return $this->attributes[$name];
			}
			$k = null;

			return $k;
		}

		public function __construct ($connection = null)
		{
			if (!isset($connection))
			{
				$connection = Config::get('oci-classes::defaults.connection');
			}
			$this->errorStack         = [];
			$this->connectionSettings = Config::get("oci-classes::connections.$connection");
			$this->setConnectionString();
		}
	}