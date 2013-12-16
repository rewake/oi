<?php
/**
 * Class MageConnector
 *
 * Connects to specified Magento DB to push & pull data as needed.
 * @author rkomatz
 */
class MageConnector extends MerlinDataModels
{
    private $_db;
    private $_orderIDs			= array();
    private $_orderData			= array();
    private $_shippingData 		= array();
    private $_itemData			= array();
    private $_canceledOrders	= array();

    private $_apiUrl, $_apiKey, $_apiUser, $_apiSession;
    private $_apiClient;

    /*------------------------------------------------
      Constructor Method
    -------------------------------------------------*/
	
	/**
	 * MageConnector handles order data from Magento sites based on specified DB connection class.
	 * @param dbMySQL $dbClass
	 */
	public function __construct($dbClass)
	{
		parent::__construct();
		
		$this->setDB($dbClass);
	}


    /*------------------------------------------------
      Public Methods
    -------------------------------------------------*/

    /**
     * Get orders from Magento DB based on MAGE_ORDERS data model.
     * @param null $orderState
     * @return array
     */
    public function getOrders($lastOrderID, $orderState = null)
	{
		// Set state to "new" if $orderState isn't passed/set
		if (!isset($orderState) && empty($orderState))
			$orderState = 'new';
		
		$qry =	"SELECT ".
				$this->selectCols($this->getDataModel(MAGE_ORDERS)).
				"FROM `sales_flat_order` JOIN `customer_group` ".
				"ON `sales_flat_order`.`customer_group_id` = `customer_group`.`customer_group_id` ".
				"WHERE `entity_id` > '".$lastOrderID."' ".
				"ORDER BY `sales_flat_order`.`entity_id` ASC ".
                "LIMIT 500"
        ;
		
		$res = $this->db()->query( $qry );
		
		while ($row = $this->db()->getRow($res))
		{
			$this->addOrderData($row);
			$this->addOrderID($row['entity_id']);
		}
		
		return $this->_orderData;
	}

    /**
     * Get addresses from Magento DB based on MAGE_ORDERS_ADDRESS data model.
     * @param null $orders
     * @return array
     */
    public function getAddresses($orders = null)
	{
		// If there's no order data we can't pull referenced data. Print error.
		if (!$this->hasOrders())
		{
			echo "No Order Data";
			exit;
		}
		
		$qry =	"SELECT ".
				$this->selectCols($this->getDataModel(MAGE_ORDERS_ADDRESS)).
				"FROM `sales_flat_order_address` ".
				"WHERE " . $this->getOrderIDs('mysql', 'parent_id')
        ;
		
		$res = $this->db()->query( $qry );
		
		while ($row = $this->db()->getRow($res))
		{
            if ($row['address_type'] == 'shipping')
                $row['shipto_address'] = 1;

            $row['region'] = $this->getStateCode($row['region']);
			$this->addShippingData($row);
		}

		return $this->_shippingData;
	}

    /**
     * Get items from Magento DB based on MAGE_ORDERS_ITEMS data model.
     * @param null $orders
     * @return array
     */
    public function getItems($orders = null)
	{
		// TODO: trigger error?
		if (!$this->hasOrders())
		{
			echo "No Order Data";
			exit;
		}
		
		$qry =	"SELECT ".
				$this->selectCols($this->getDataModel(MAGE_ORDERS_ITEMS)).
                "FROM `sales_flat_order_item` ".
				"WHERE " . $this->getOrderIDs('mysql', 'order_id')
        ;

		$res = $this->db()->query( $qry );

		while ($row = $this->db()->getRow($res))
		{
            // ERJ client uses SKU for ISBN for some reason.
            // $row['isbn'] = $this->getItemAttribute('isbn', $row['product_id']);
			$this->addItemData($row);
		}

		return $this->_itemData;
	}

    /**
     * @param $attributeName
     * @param $itemID
     * @return mixed
     * @depreciated
     */
    public function getItemAttribute($attributeName, $itemID)
    {
        $qry =  "SELECT IFNULL((".
                    "SELECT `value` ".
                    "FROM `catalog_product_entity_varchar` ".
                    "JOIN `eav_attribute` ".
                    "ON `catalog_product_entity_varchar`.`attribute_id` = `eav_attribute`.`attribute_id` ".
                    "WHERE `attribute_code`='".$attributeName."' ".
                    "AND `entity_id`='".$itemID."' ".
                    "LIMIT 1".
                "), NULL) AS `attribute`";
        ;

        $res = $this->db()->query( $qry );
        $row = $this->db()->getRow($res);

        return $row['attribute'];
    }

    /**
     * Get canceled orders from Magento DB based on MAGE_CANCELED_ORDERS data model.
     * @return array
     */
    public function getCanceledOrders()
	{
		$qry = "SELECT ".
				$this->selectCols($this->getDataModel(MAGE_CANCELED_ORDERS)).
				"FROM `sales_flat_order` ".
				"WHERE `state`='canceled'"
        ;
		
		$res = $this->db()->query( $qry );
		
		while ($row = $this->db()->getRow($res))
		{
			$this->addCanceledOrder($row);
		}
		
		return $this->_canceledOrders;
	}

    /**
     * Flush stored data from previous queries.
     */
    public function flush_cache()
    {
        unset($this->_orderIDs);
        unset($this->_orderData);
        unset($this->_shippingData);
        unset($this->_itemData);
        unset($this->_canceledOrders);
    }

    /**
     * @param $orderState
     * @param $orderStatus
     * @param $mageOrderID
     * @depreciated
     */
    public function updateOrderDirect($orderState, $orderStatus, $mageOrderID)
    {
        $qry =  "UPDATE `sales_flat_order` ".
                "JOIN `sales_flat_order_grid` ".
                "ON `sales_flat_order`.`entity_id`=`sales_flat_order_grid`.`entity_id` ".
                "SET ".
                    "`sales_flat_order`.`state`='".$orderState."', ".
                    "`sales_flat_order`.`status`='".$orderStatus."', ".
                    "`sales_flat_order_grid`.`status`='".$orderStatus."' ".
                "WHERE `sales_flat_order`.`entity_id`='".$mageOrderID."'"
        ;

        $res = $this->db()->query( $qry );

        $qry =  "INSERT INTO `sales_flat_order_status_history` (".
                    "`parent_id`, `is_customer_notified`, `is_visible_on_front`, `comment`".
                ") ".
                "VALUES (".
                    "'".$mageOrderID."', ".
                    "2, 0, ".
                    "'OI Update: Setting order state/status to: ".$orderState."/".$orderStatus."'".
                ")"
        ;

        $res = $this->db()->query( $qry );
    }
	
    public function apiConfig($apiUrl, $apiUser, $apiKey)
    {
        $this->_apiUrl  = $apiUrl;
        $this->_apiUser = $apiUser;
        $this->_apiKey  = $apiKey;

        $this->_apiClient = new SoapClient($this->_apiUrl);
    }

    public function api()
    {
        return $this->_apiClient;
    }

    public function apiSession()
    {
        return $this->_apiClient->login($this->_apiUser, $this->_apiKey);
    }

    /*------------------------------------------------
      Protected Methods
    -------------------------------------------------*/

    /**
     * Returns array or order IDs or generates MySQL WHERE OR string for use in queries.
     * @param null $returnType
     * @param null $IDColName
     * @return array|string
     */
    protected function getOrderIDs($returnType = null, $IDColName = null)
	{
		// Return OrderIDs as Array
		if (!isset($returnType) && empty($returnType))
		{
			return $this->_orderIDs;
		}
		
		// Return MySQL "OR" queries as String
		if ($returnType === 'mysql' & isset($IDColName) && !empty($IDColName))
		{
			foreach ($this->_orderIDs as $k => $v)
			{
				$qryOR[] = "`".$IDColName."`='".$v."'";
			}
			
			return implode(" OR ", $qryOR);
		}
	}

    /**
     * Add order data to $orderData array for future use.
     * @param $orderData
     */
    protected function addOrderData($orderData)
	{
		array_push($this->_orderData, $orderData);
	}

    /**
     * Add order ID to $orderID array for future use.
     * @param $orderID
     */
    protected function addOrderID($orderID)
	{
		array_push($this->_orderIDs, $orderID);
	}

    /**
     * Add shipping data to $shippingData array for future use.
     * @param $shippingData
     */
    protected function addShippingData($shippingData)
	{
		array_push($this->_shippingData, $shippingData);
	}

    /**
     * Add items to $itemData array for future use.
     * @param $itemData
     */
    protected function addItemData($itemData)
	{
		array_push($this->_itemData, $itemData);
	}

    /**
     * Add canceled orders to $canceledOrderData array for future use.
     * @param $canceledOrderData
     */
    protected function addCanceledOrder($canceledOrderData)
	{
		array_push($this->_canceledOrders, $canceledOrderData);
	}


    /*------------------------------------------------
      Private Methods
    -------------------------------------------------*/

    /**
     * Returns TRUE if there is stored order data, FALSE if there is no order data stored.
     * @return bool
     */
    private function hasOrders()
	{
		if (empty($this->_orderData))
			return false;
		else
			return true;
			
	}

    /**
     * Sets dbMySQL class for Merlin to use.
     * @param $db
     */
    private function setDB($db)
	{
		$this->_db = $db;
	}

    /**
     * Returns dbMySQL class to use as chained method.
     * @return mixed
     */
    private function db()
	{
		return $this->_db;
	}
}
?>

