<?php

// Hmm... we're using stuff that will possibly never exist...
if (!defined(DEV_SERVER))
	define("DEV_SERVER", false);

if (!function_exists(dumpArray)) {
	function dumpArray($array, $asString = false, $print_r = true, $backTrace = true)
	{
		$evalContent = $print_r ? '<? print_r($array) ?>' : '<? var_dump($array) ?>';
		$bt = function_exists('dumpBacktrace') ? dumpBacktrace() : var_export(debug_backtrace(), true);
		if ($asString) {
			$totallyMessedUpVariableThatCouldNeverBeSetByAnyOneEllse = $backTrace;
			extract($GLOBALS);
			$backTrace = $totallyMessedUpVariableThatCouldNeverBeSetByAnyOneEllse;
			ob_start();
			ob_clean();
			eval("?>$evalContent");
			$returnString = ob_get_contents();
			ob_clean();
			return $backTrace ? "<pre>" . $returnString . "<br>\nBackTrace:<br>\n" . $bt . "</pre>" : "<pre>" . $returnString . "</pre>";
		}
		echo "<pre>";
		eval('?>' . $evalContent);
		echo ($backTrace) ? "<br>\nBackTrace:<br>\n" . $bt . "</pre>" : "</pre>";
	}
}
// End of possibly maybe already created wondrous things


class EpaceDataModels
{
	private $o  = array();
	protected $message	= array();
	
	public function __construct($dataModel = null)
	{
		if (isset($dataModel) && !empty($dataModel))
		{
			if (!$this->modelLoaded($dataModel))
				$this->loadModels($dataModel);
		}
	}
	
	public function getFieldInfo($dataModel, $fieldNames = null, $mustSyncOnly = false)
	{
		if (!$this->modelLoaded($dataModel))
			$this->loadModels($dataModel);

		if (is_null($fieldNames))
		{
			if ($mustSyncOnly)
			{
				foreach ($this->o['data'][$dataModel] as $field)
				{
					if ($field['must_sync'])
						$returnArray[$field['field_name']] = $field;
				}

				return $returnArray;
			}
			else
			{
				return $this->o['data'][$dataModel];
			}

		}
		else if (is_array($fieldNames))
		{
			foreach ($fieldNames as $field)
			{
				if ($mustSyncOnly)
					if (!$field['must_sync'])
						continue;

				array_key_exists($field, $this->o['data'][$dataModel]) ?
					$returnArray[$field] = $this->o['data'][$dataModel][$field] :
					$returnArray[$field] = "[{$field}] does not exist in [{$dataModel}]." ;
			}

			return $returnArray;
		}
		else
		{
			if (array_key_exists($fieldNames, $this->o['data'][$dataModel]))
			{
				if ($mustSyncOnly && !$this->o['data'][$dataModel][$fieldNames]['must_sync'])
				{
					return false;
				}
				else
				{
					$returnArray = $this->o['data'][$dataModel][$fieldNames];
				}
			}
			else
			{
				$returnArray = "[{$fieldNames}] does not exist in [{$dataModel}].";
			}

			return $returnArray;
		}
	}

    /**
     * Loads the model class(es) passed in as args. Ex: $this->loadModels('Job'); or $this->loadModels('Job', 'JobPart').
     * Note that passing multiple args is essentially depreciated due to the way the ePace API works
     * @return bool
     */
	public function loadModels()
	{
		foreach (func_get_args() as $modelClass)
		{
			if (class_exists($modelClass, true))
			{
				$model = new $modelClass();
				$this->o['data'][$modelClass] = $this->mapDataModel($model->data);
			}
			else
			{
				trigger_error("Trying to include class that does not exist: ".dumpArray($modelClass, true), E_USER_WARNING);
				$this->message[] = DEV_SERVER ?
					"Trying to include class that does not exist: ".dumpArray($modelClass, true) :
					"There was an issue getting data. (0x1)" ;
				return false;
			}
		}

		return true;
	}

    /**
     * Loops through field array and returns a formatted array
     * @param $fieldArray
     * @return bool
     */
	public function mapDataModel($fieldArray)
	{
		foreach ($fieldArray as $fd)
		{
			if (isset($fd['primary_key']) && $fd['primary_key'] == true)
			{
				$returnArray['primary_keys'][$fd['field_name']] = $fd['field_name'];
			}

			$returnArray[$fd['field_name']] = $fd;
		}

        if (isset($returnArray) && !empty($returnArray))
		    return $returnArray;
        else
            return false;
	}

	/**
	 * Flushes any data from model object ($this->o)
	 * @param $dataModel
	 * @return bool
	 */
	public function flushModels($dataModel = null)
	{
		if (isset($dataModel) && !empty($dataModel))
		{
			unset($this->o['data'][$dataModel]);
		}
		else
		{
			unset($this->o);
		}
	}

	/**
	 * Compiles & returns an array based on current object model ($this->o) vs passed $fieldArray.
	 * If $returnPrimaryKeys = false; the primary keys and values will be stripped from the return array. Us this if you're creating/inserting to API.
	 * @param $fieldArray
	 * @param null $dataModel
	 * @param bool $returnPrimaryKeys
	 * @param bool $mustSyncOnly
	 * @param bool $flushDataModels
	 * @internal param null $returnMapping
	 * @return bool
	 */
	public function getDataArray($fieldArray, $dataModel = null, $returnPrimaryKeys = true, $mustSyncOnly = false, $flushDataModels = false)
	{
		if (isset($dataModel) && !empty($dataModel))
		{
			if (!$this->modelLoaded($dataModel))
				$this->loadModels($dataModel);
		}
		else
		{
			if (!isset($this->o) || empty($this->o))
			{
				trigger_error("Data model object was empty, which means it was never instantiated: ", E_USER_WARNING);
				$this->message[] = DEV_SERVER ?
					"Data model object was empty, which means it was never instantiated: " :
					"There was an issue getting data. (0x3)" ;
				return false;
			}
			else if (count(array_keys($this->o['data'])) > 1)
			{
				// If there's more than one data model loaded but $dataModel wasn't passed I have no way
				// of knowing which model to return, so let's get out of here.
				trigger_error("Trying to getDataArray but dataModel was not passed & there's more than one model loaded: ".dumpArray(array_keys($this->o['data']), true), E_USER_WARNING);
				$this->message[] = DEV_SERVER ?
					"Trying to getDataArray but dataModel was not passed & there's more than one model loaded: ".dumpArray(array_keys($this->o['data']), true) :
					"There was an issue getting data. (0x33)" ;
				return false;
			}
			else
			{
				$dataModel = array_pop(array_keys($this->o['data']));
			}
		}

		if (!$primaryKeys = $this->getPrimaryKeys($dataModel))
		{
			trigger_error("Warning: There was a problem getting primary keys for this object: ", E_USER_WARNING);
			$this->message[] = DEV_SERVER ?
				"Warning: There was a problem getting primary keys for this object: " :
				"There was an issue getting data. (0x8)" ;
		}

		if ($returnPrimaryKeys && is_array($primaryKeys))
		{
			foreach ($primaryKeys as $pk)
			{
				if (!array_key_exists($pk, $fieldArray))
					$err[] = "Primary key requested but not found in the field array passed.".$pk;
			}

			if (isset($err) && !empty($err))
			{
				trigger_error("Required primary key(s) missing: ".dumpArray(implode(',', $err), true), E_USER_WARNING);
				$this->message[] = DEV_SERVER ?
					dumpArray(array(implode(',', $err), '$pk'=>$pk, '$fieldArray'=>$fieldArray), true) :
					"There was an issue getting data. (0x7)" ;
				return false;
			}
		}
		else
		{
			foreach ($primaryKeys as $pk)
			{
				unset($fieldArray[$pk]);
			}
		}

		// This creates the return array.
		foreach ($fieldArray as $f => $v)
		{
			if ($fieldReturn = $this->checkValue($f, $v, $dataModel))
			{
				if (isset($this->o['data'][$dataModel][$f]) && !empty($this->o['data'][$dataModel][$f]))
				{

					if ($mustSyncOnly)
					{
						$syncFieldArray = $this->getFieldInfo($dataModel, $f);

						if (!$syncFieldArray['must_sync'])
							continue;
					}

					$returnArray[$this->o['data'][$dataModel][$f]['field_name']] = current($fieldReturn);
				}
				else
				{
					trigger_error("Trying to map a field that's empty/unset: ".dumpArray(array($this->mapping, $f, current($return)), true), E_USER_WARNING);
					$this->message[] = DEV_SERVER ?
						"Trying to map a field that's empty/unset: ".dumpArray(array($this->mapping, $f, current($return)), true) :
						"There was an issue getting data. (0x4)" ;
				}
			}
			else
			{
				// TODO: what if no return field?
			}
		}

		if ($flushDataModels)
			$this->flushModels(); // TODO: pass datamodel?

        if (isset($returnArray) && !empty($returnArray))
            return $returnArray;
        else
            return false;
	}

    /**
     * Returns the primary keys of specified data model or all currently loaded primary keys
     * @param null $dataModel
     * @return bool
     */
	public function getPrimaryKeys($dataModel = null)
	{
		if (isset($dataModel) && !empty($dataModel))
		{
			if (!$this->modelLoaded($dataModel))
				$this->loadModels($dataModel);

			return $this->o['data'][$dataModel]['primary_keys'];
		}
		else
		{
			if (isset($this->o) && !empty($this->o))
			{
				foreach ($this->o['data'] as $model => $fieldArray)
				{
					$pkArray[$model] = $fieldArray['primary_keys'];
				}

				return $pkArray;
			}
			else
			{
				trigger_error("No data model was passed when trying to get primary keys and there are no models loaded.".dumpArray("getPrimaryKeys"));
				$this->message = DEV_SERVER ?
					"No data model was passed when trying to get primary keys and there are no models loaded.".dumpArray("getPrimaryKeys") :
					"There was an issue getting data. (0x5)" ;
				return false;
			}
		}
	}

	/**
	 * Returns true if specified model is currently loaded
	 * @param $dataModel
	 * @return bool
	 */
	public function modelLoaded($dataModel)
	{
		return isset($this->o['data'][$dataModel]) ? true : false ;
	}

    /**
     * Returns any system to end user messages
     * @return bool|string
     */
	public function getMessage()
	{
		if (!isset($this->message) || empty($this->message))
			return false;

		$msg = implode("\n", $this->message);

		unset($this->message);
		return $msg;
	}

    /**
     * Returns true if any system to user messages exist
     * @return bool
     */
	public function hasMessage()
	{
		if (!isset($this->message) || empty($this->message))
			return false;
		else
			return true;
	}

    /*------------------------------------------------
      Protected Methods
    -------------------------------------------------*/

    /**
     * Checks the values passed & conforms them to match the data model fields.
     * NOTE: values must be cleansed prior to sending to this method
     * @param $field
     * @param $value
     * @return array|bool
     */
	protected function checkValue($field, $value, $dataModel)
	{
		// See if it's a valid field
		if (!in_array($field, array_keys($this->o['data'][$dataModel])))
		{
			$this->message[] = "Warning: [{$field}]: Field is not part of object."/*.dumpArray(array('$this->o'=>$this->o), true)/**/;
			return false;
		}

		$type = $this->o['data'][$dataModel][$field]['type'];

		switch ($type)
		{
			case "String":
							if (!is_string($value))
							{
								$this->message[] = "Warning: [{$field}]: Mis-matched data type in field. Should be: [{$type}].";
							}
				break;
				// :String

			case "Boolean":
							if (!is_bool($value))
							{
								$this->message[] = "Warning: [{$field}]: Mis-matched data type in field. Should be: [{$type}].";
							}
				break;
				// :Boolean

			case "Integer":
							if (!is_int($value))
							{
								$this->message[] = "Warning: [{$field}]: Mis-matched data type in field. Should be: [{$type}].";
							}
				break;
				// :Integer

			case "Date":
				break;
			case "Time":
				break;
			case "Percent":
				break;
			case "Currency":
				break;
			case "Attachment":
				break;

			default:
				break;
		}

		// Truncate value to field's max length
		if ($this->o['data'][$dataModel][$field]['max_length'] >= 0  && $this->o['data'][$dataModel][$field]['max_length'] < strlen($value))
		{
			$this->message[] = "Warning: [{$field}]: Max length greater than value length.";
			$value = substr($value, 0, $this->o['data'][$dataModel][$field]['max_length']);
		}

		return array($field => $value);
	}
}

