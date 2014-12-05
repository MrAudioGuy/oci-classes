<?php

	namespace MrAudioGuy\OciClasses;

	/**
	 * Class Cursor
	 *
	 * @package MrAudioGuy\OciClasses
	 */
	class Cursor extends Statement
	{
		/**
		 * @param Connection $connection
		 * @param null       $paging
		 * @param null       $resource
		 */
		public function __construct (Connection $connection, $paging = null, & $resource = null)
		{
			parent::__construct($connection, $paging, $resource);

			if (isset($resource))
			{
				if (is_resource($resource))
				{
					$this->resource = $resource;
				}
				else
				{
					$this->resource = oci_new_cursor($this->connection->resource);
					$trace          = debug_backtrace();
					trigger_error(
						'Invalid input via __construct(): value is not a valid resource for ("this->resource' .
						'") in ' . $trace[0]['file'] .
						' on line ' . $trace[0]['line'],
						E_USER_NOTICE);
				}
			}
			else
			{
				$this->resource = oci_new_cursor($this->connection->resource);
			}
		}
	}