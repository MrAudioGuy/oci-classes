<?php

	namespace MrAudioGuy\OciClasses;

	use Exception;
	use Config;
	use MrAudioGuy\Commons\Error;

	/**
	 *
	 */
	!defined("OCI_B_IN") ? define("OCI_B_IN", 0x1, true) : null;

	/**
	 *
	 */
	!defined("OCI_B_OUT") ? define("OCI_B_OUT", 0x2, true) : null;

	/**
	 * Class Statement
	 *
	 * @package MrAudioGuy\OciClasses
	 */
	class Statement
	{

		// Properties
		/**
		 * @var null
		 */
		protected $resource;

		/**
		 * @var Connection
		 */
		protected $connection;

		/**
		 * @var
		 */
		protected $executed;

		/**
		 * @var
		 */
		protected $attributes;

		/**
		 * @var
		 */
		protected $paging;

		/**
		 * @var
		 */
		protected $savedRows;

		/**
		 * @var
		 */
		protected $affectedRows;

		/**
		 * @var
		 */
		protected $rowCount;

		/**
		 * @var
		 */
		protected $stackError;

		// Maintenance

		/**
		 * @param array      $binding
		 * @param Connection $connection
		 * @param bool       $suppress
		 *
		 * @return $this
		 */
		public function & bind (array & $binding = [], Connection $connection = null, $suppress = false)
		{
			$this->connection = isset($connection) ? $connection : $this->connection;

			foreach ($binding as $bindingKey => & $bindingVal)
			{
				try
				{
					$type = DataTypes::convert2Oracle(gettype($bindingVal));
					$q    = oci_bind_by_name($this->resource,
											 $bindingKey,
											 $bindingVal,
											 DataTypes::sizeof($bindingVal),
											 $type);
					if (!$q)
					{
						$t                             = count($this->errorStack);
						$this->errorStack[$t]['error'] = oci_error($this->connection->resource);
						$this->errorStack[$t]['trace'] = debug_backtrace(0, 3);
						Error::getMessage(null, $this->errorStack[$t]['trace'], $this->errorStack[$t]['error'], $suppress);
					}
				}
				catch (Exception $ex)
				{
					trigger_error($ex->getMessage() .
								  "   --\$binding['$bindingKey']={$binding[$bindingKey]} & \$type = $type ", E_USER_ERROR);
				}
			}

			return $this;
		}

		/**
		 * @param            $query
		 * @param Connection $connection
		 * @param bool       $suppress
		 *
		 * @return $this
		 */
		public function & parse ($query, Connection $connection = null, $suppress = false)
		{
			$this->connection = isset($connection) ? $connection : $this->connection;
			$this->resource   = oci_parse($this->connection->resource, $query);
			if (!$this->resource)
			{
				$t                             = count($this->errorStack);
				$this->errorStack[$t]['error'] = oci_error($this->connection->resource);
				$this->errorStack[$t]['trace'] = debug_backtrace(0, 3);
				Error::getMessage(null, $this->errorStack[$t]['trace'], $this->errorStack[$t]['error'], $suppress);
			}

			return $this;
		}

		/**
		 * @param            $query
		 * @param array      $binding
		 * @param Connection $connection
		 *
		 * @return $this
		 */
		public function & prepare ($query, array & $binding = [], Connection $connection = null)
		{
			$this->connection = isset($connection) ? $connection : $this->connection;
			$this->clean()->parse($query)->bind($binding);

			return $this;
		}

		/**
		 * @param bool $suppress
		 *
		 * @return bool
		 */
		public function execute ($suppress = false)
		{
			try
			{
				$this->executed = oci_execute($this->resource);
			}
			catch (Exception $ex)
			{

				$t                             = count($this->errorStack);
				$this->errorStack[$t]['error'] = oci_error($this->connection->resource);
				$this->errorStack[$t]['trace'] = debug_backtrace(0, 3);
				Error::getMessage($ex, $this->errorStack[$t]['trace'], null, false);
			}
			if (!$this->executed)
			{
				$t                             = count($this->errorStack);
				$this->errorStack[$t]['error'] = oci_error($this->connection->resource);
				$this->errorStack[$t]['trace'] = debug_backtrace(0, 3);
				Error::getMessage(null, $this->errorStack[$t]['trace'], $this->errorStack[$t]['error'], $suppress);
			}

			return $this->executed;
		}

		/**
		 * @return $this
		 */
		public function & clean ()
		{
			if (is_resource($this->resource) && (get_resource_type($this->resource) == "oci8 statement"))
			{
				oci_free_statement($this->resource);
			}
			else
			{
				unset($this->resource);
			}

			return $this;
		}

		/**
		 * @return $this
		 */
		public function & flushErrors ()
		{
			$this->errorStack = [];

			return $this;
		}

		// Obtaining and Accessibility

		/**
		 * @param bool $save
		 *
		 * @return array|bool
		 */
		public function next ($save = false)
		{
			if (isset($this->resource) && is_resource($this->resource))
			{
				$row = oci_fetch_array($this->resource, OCI_ASSOC + OCI_RETURN_NULLS + OCI_RETURN_LOBS);
				if ($row != false)
				{
					if ($save)
					{
						$this->rowCount++;
						if ($this->paging > 0)
						{
							$t = count($this->savedRows) - 1;
							if ($t > -1)
							{
								if (count($this->savedRows[$t]) < $this->paging)
								{
									$this->savedRows[$t][] = $row;
								}
								else
								{
									$this->savedRows[$t + 1][] = $row;
								}
							}
							else
							{
								$this->savedRows[0][] = $row;
							}
						}
						else
						{
							$this->savedRows[] = $row;
						}
					}

					return $row;
				}
				else
				{
					return false;
				}
			}
		}

		/**
		 * @param null $rownum
		 * @param bool $save
		 * @param bool $suppress
		 *
		 * @return array
		 */
		public function fetch ($rownum = null, $save = false, $suppress = false)
		{
			$rows = [];
			if (!isset($rownum))
			{
				$rownum = Config::get('oci-classes::defaults.paging');
			}
			if ($rownum < 1)
			{
				if ($save)
				{
					for ($i = 0; true; $i++)
					{
						$rows[$i] = $this->next($save);
						if ($rows[$i] == false)
						{
							unset($rows[$i]);
							break;
						}
					}
				}
				else
				{
					$this->affectedRows = oci_fetch_all($this->resource, $rows, 0, -1,
														OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC + OCI_RETURN_NULLS +
														OCI_RETURN_LOBS);
					if ($this->affectedRows === false)
					{
						$t                             = count($this->errorStack);
						$this->errorStack[$t]['error'] = oci_error($this->connection->resource);
						$this->errorStack[$t]['trace'] = debug_backtrace(0, 3);
						Error::getMessage(null, $this->errorStack[$t]['trace'], $this->errorStack[$t]['error'], $suppress);

						return $this->affectedRows;
					}

					return $rows;
				}
			}
			else
			{
				for ($i = 0; $i < $rownum; $i++)
				{
					$rows[$i] = $this->next($save);
					if ($rows[$i] == false)
					{
						unset($rows[$i]);
						break;
					}
				}
			}

			return $rows;
		}

		/**
		 * @param $paging
		 */
		public function repage ($paging)
		{
			$newSavedRows = [];
			if ($paging > 0)
			{
				foreach ($this->savedRows as $page)
				{
					foreach ($page as $row)
					{
						$t = count($newSavedRows) - 1;
						if ($t > -1)
						{
							if (count($newSavedRows[$t]) < $paging)
							{
								$newSavedRows[$t][] = $row;
							}
							else
							{
								$newSavedRows[$t + 1][] = $row;
							}
						}
						else
						{
							$newSavedRows[0][] = $row;
						}
					}
				}
			}
			else
			{
				foreach ($this->savedRows as $page)
				{
					foreach ($page as $row)
					{
						$newSavedRows[] = $row;
					}
				}
			}
			$this->savedRows = $newSavedRows;
			$this->paging    = $paging;
		}

		// getters

		/**
		 * @param $page
		 *
		 * @return bool
		 */
		public function getPage ($page)
		{
			if (isset($this->savedRows[$page]))
			{
				return $this->savedRows[$page];
			}
			else
			{
				return false;
			}
		}

		/**
		 * @param $page
		 * @param $row
		 *
		 * @return bool
		 */
		public function getRow ($page, $row)
		{
			if (isset($this->savedRows[$page]) && isset($this->savedRows[$page][$row]))
			{
				return $this->savedRows[$page][$row];
			}
			else
			{
				return false;
			}
		}

		/**
		 * @return Connection
		 */
		protected function & getConnection ()
		{
			return $this->connection;
		}

		/**
		 * @return null
		 */
		protected function & getResource ()
		{
			return $this->resource;
		}

		/**
		 * @return mixed
		 */
		protected function & getExecuted ()
		{
			return $this->executed;
		}

		/**
		 * @return mixed
		 */
		protected function getPaging ()
		{
			return $this->paging;
		}

		/**
		 * @return mixed
		 */
		public function getCount ()
		{
			return $this->rowCount;
		}

		/**
		 * @return mixed
		 */
		public function getAffected ()
		{
			return $this->affectedRows;
		}

		// Setters

		/**
		 * @param $value
		 *
		 * @return null|resource
		 */
		protected function setConnection ($value)
		{
			if (is_resource($value))
			{
				return $this->connection = $value;
			}
			else
			{
				$trace = debug_backtrace();
				trigger_error(
					'Invalid input via __set(): value is not a valid resource for ("$this->connection")' .
					' in ' . $trace[0]['file'] .
					' on line ' . $trace[0]['line'],
					E_USER_NOTICE);

				return null;
			}
		}

		/**
		 * @param $value
		 *
		 * @return null|resource
		 */
		protected function setResource (& $value)
		{
			if (is_resource($value))
			{
				return $this->resource = $value;
			}
			else
			{
				$trace = debug_backtrace();
				trigger_error(
					'Invalid input via __set(): value is not a valid resource for ("$this->resource")' .
					' in ' . $trace[0]['file'] .
					' on line ' . $trace[0]['line'],
					E_USER_NOTICE);

				return null;
			}
		}

		/**
		 * @param $value
		 *
		 * @return null|resource
		 */
		protected function setExecuted (& $value)
		{
			if (is_resource($value))
			{
				return $this->executed = $value;
			}
			else
			{
				$trace = debug_backtrace();
				trigger_error(
					'Invalid input via __set(): value is not a valid resource for ("$this->executed")' .
					' in ' . $trace[0]['file'] .
					' on line ' . $trace[0]['line'],
					E_USER_NOTICE);

				return null;
			}
		}

		/**
		 * @param $value
		 *
		 * @return null|resource
		 */
		protected function setPaging ($value)
		{
			if (is_resource($value))
			{
				return $this->paging = $value;
			}
			else
			{
				$trace = debug_backtrace();
				trigger_error(
					'Invalid input via __set(): value is not a valid resource for ("$this->paging")' .
					' in ' . $trace[0]['file'] .
					' on line ' . $trace[0]['line'],
					E_USER_NOTICE);

				return null;
			}
		}

		// Magic functions

		/**
		 * @param $name
		 *
		 * @return null
		 */
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
			$trace = debug_backtrace();
			trigger_error(
				'Undefined property via __get(): ' . $name .
				' in ' . $trace[0]['file'] .
				' on line ' . $trace[0]['line'],
				E_USER_NOTICE);
			$k = null;

			return $k;
		}

		/**
		 * @param $name
		 * @param $value
		 *
		 * @return null
		 */
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

		/**
		 * @param Connection $connection
		 * @param null       $paging
		 * @param null       $resource
		 */
		public function __construct (Connection $connection = null, $paging = null, & $resource = null)
		{
			$this->errorStack = [];

			// Connection

			isset($connection) ? $this->connection = $connection : null;

			// Paging

			if (isset($paging))
			{
				if (is_numeric($paging))
				{
					$this->paging = (int)$paging;
				}
				else
				{
					$this->paging = Config::get('oci-classes::defaults.paging');
					$trace        = debug_backtrace();
					trigger_error(
						'Invalid input via __construct(): value is not a valid integer for ("this->paging' .
						'") in ' . $trace[0]['file'] .
						' on line ' . $trace[0]['line'],
						E_USER_NOTICE);
				}
			}
			else
			{
				$this->paging = Config::get('oci-classes::defaults.paging');
			}

			// Resource

			if (isset($resource))
			{
				if (is_resource($resource))
				{
					$this->resource = $resource;
				}
				else
				{
					$trace = debug_backtrace();
					trigger_error(
						'Invalid input via __construct(): value is not a valid resource for ("this->resource' .
						'") in ' . $trace[0]['file'] .
						' on line ' . $trace[0]['line'],
						E_USER_NOTICE);
				}
			}
			else
			{
				$this->resource = null;
			}
		}
	}