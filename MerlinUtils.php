<?php
/**
 * Class MerlinUtils
 *
 * Defines data models for use between Magento, Merlin, CEMI, Pace, etc.
 * @author rkomatz
 **/
class MerlinUtils
{
    /*------------------------------------------------
	  Constructor Method
	-------------------------------------------------*/

    /**
     * MerlinUtils is a set of utility functions for the Merlin class and subclasses.
     */
    public function __construct()
    {

    }

    /*------------------------------------------------
      Protected Methods
    -------------------------------------------------*/

    /**
     * Generates a select statement based on 'columnName' => 'tableName' and gives you sanitized values `tableName`.`columnName`.
     * @param $columnArray
     * @return string
     */
    protected function selectCols($columnArray)
    {
        $cols = array();

        foreach ($columnArray as $k => $v)
        {
            $cols[] = "`".$v."`.`".$k."`";
        }

        return implode(', ', $cols);
    }

    /**
     * Generates a MySQL WHERE statement based on $filterArray('columnName' => 'value'). Statement
     * defaults to "OR" query, but "AND" may be passed as $whereType argument.
     * @param $filterArray
     * @param null $whereType
     * @return string
     */
    protected function where($filterArray, $whereType = null)
    {
        if (is_null($filterArray)) return;

        if ($whereType === null)
            $whereType = "OR";
        elseif (strtoupper($whereType) !== "AND" && strtoupper($whereType) !== "OR")
            return;

        $whereType = ' '.strtoupper($whereType).' ';

        foreach ($filterArray as $k => $v)
        {
            $cols[] = "`".str_replace('.', '`.`', $k)."`='".$v."'";
        }

        $where = " WHERE ".implode($whereType, $cols);

        return $where;
    }

}

?>
