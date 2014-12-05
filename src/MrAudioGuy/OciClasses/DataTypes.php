<?php

	namespace MrAudioGuy\OciClasses;

	/**
	 * Class DataTypes
	 *
	 * @package MrAudioGuy\OciClasses
	 */
	class DataTypes
	{
		/**
		 * @param $var
		 *
		 * @return int
		 */
		public static function sizeof ($var)
		{
			$start_memory = memory_get_usage();
			$tmp          = unserialize(serialize($var));

			return memory_get_usage() - $start_memory;
		}

		/**
		 * @param $var
		 *
		 * @return int
		 */
		public static function sizeofType ($var)
		{
			switch(gettype($var))
			{
				case "boolean":
				{
					return PHP_INT_SIZE;
				}
				case "integer":
				{
					return PHP_INT_SIZE;
				}
				case "double":
				{
					return (PHP_INT_SIZE * 2);
				}
				case "string":
				{
					return strlen($var) * (PHP_INT_SIZE * 2);
				}
				case "array":
				{
					return static::sizeof($var);
				}
				case "object":
				{
					return static::sizeof($var);
				}
				case "resource":
				{
					return PHP_INT_SIZE;
				}
				case "NULL":
				{
					return PHP_INT_SIZE;
				}
				case "unknown type":
				{
					return static::sizeof($var);
				}
			}
			return static::sizeof($var);
		}

		/**
		 * @param $phpType
		 *
		 * @return bool|int
		 */
		public static function convert2Oracle ($phpType)
		{
			switch ($phpType)
			{
				case "boolean":
				{
					return SQLT_BOL;
				}
				case "integer":
				{
					return SQLT_LNG;
				}
				case "double":
				{
					return SQLT_FLT;
				}
				case "string":
				{
					return SQLT_CHR;
				}
				case "array":
				{
					return false;
				}
				case "object":
				{
					return false;
				}
				case "resource":
				{
					return SQLT_RSET;
				}
				case "NULL":
				{
					return SQLT_CHR;
				}
				case "unknown type":
				{
					return false;
				}
			}
			return false;
		}
	}