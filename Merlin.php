<?php
/**
 * Class Merlin
 *
 * Manages data in Merlin user interface using specified DB.
 * @author rkomatz
 */
class Merlin extends MerlinDataModels
{
    private $_db;
    private $_client;
    private $_clientID;
    private $_password;
    private $_company;
    private $_user;

    private $_invoiceUrl, $_shipmentUrl;

    /*------------------------------------------------
      Constructor Method
    -------------------------------------------------*/

    /**
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
     * Utility function to pass multiple sets of data to Merlin at once.
     * @param $orderDataArray
     * @param $addressDataArray
     * @param $itemDataArray
     * @param $canceledOrderArray
     * @returns true | Merlin::error
     */
    public function processOrders($orderDataArray, $addressDataArray, $itemDataArray, $canceledOrderArray)
	{
		$err = array(); // TODO: DB class doesn't return errors

		// TODO: verify data, etc.

		foreach ($orderDataArray as $order)
		{
			$ins = $this->insertOrder($order);
			if (!ins) array_push($err, $ins);
		}

		foreach ($addressDataArray as $address)
		{
			$ins = $this->insertAddress($address);
			if (!ins) array_push($err, $ins);
		}

		foreach ($itemDataArray as $item)
		{
			$ins = $this->insertItem($item);
			if (!ins) array_push($err, $ins);
		}

		foreach ($canceledOrderArray as $canceledOrder)
		{
			$ins = $this->insertCanceledOrder($canceledOrder);
			if (!ins) array_push($err, $ins);
		}

        if (!empty($err))
        {
            return $this->error("Errors while processing orders: ".implode(", ", $err), __FUNCTION__);
        }
        else
        {
//             $this->logAction('SYSTEM', "PROCESS_ORDERS");
            return true;
        }
	}

    /**
     * Inserts data into Merlin based on MAGE_ORDERS data model.
     * @param $orderDataArray
     * @return mixed
     */
    public function insertOrder($orderDataArray)
	{
		$diffs = $this->compareDataModels($orderDataArray, MAGE_ORDERS);

        if (!empty($diffs))
            return $this->error("Data models don't match: ".implode(", ", $diffs), __FUNCTION__);

		$cols = '`' . implode('`,`', array_keys($orderDataArray)) . '`';

		foreach (array_values($orderDataArray) as $v)
		{
			$vals[] = '"'.addslashes($v).'"';
		}

		$vals = implode(',', $vals);

		$qry =	"INSERT INTO `mage_orders` ".
				"(".$cols.", `merlin_import_date`)".
				" VALUES ".
				"(".$vals.", NOW())"
        ;

        $res = $this->db()->query( $qry );

        // TODO: check for errors/duplicate orderID's? Table row is set to unique so it won;t duplicate, but may give incorrect # of orders imported

		return $res;
    }

    /**
     * Returns array of order data from Merlin based on $filterArray and $dataModel. Default data model is MERLIN_ORDERS.
     * @param null $dataModel
     * @param null $filterArray
     * @param null $whereType
     * @return array
     */
    public function getOrders($dataModel = null, $filterArray = null, $whereType = null)
	{
		// If wrong data model is passed set it to orders
		if (is_null($dataModel) || !$this->getDataModel($dataModel))
			$dataModel = MERLIN_ORDERS;

		$qry =	"SELECT ".
				$this->selectCols($this->getDataModel($dataModel)).
				"FROM `".$dataModel."`".
                $this->where($filterArray, $whereType);
        ;

        if (!$res = $this->db()->query( $qry ))
            return $this->error("Bad MySQL query: ".$qry, __FUNCTION__);

        while ($row = $this->db()->getRow($res))
        {
            $orderData[] = $row;
        }

		return $orderData;
	}

    /**
     * Inserts data into Merlin based on MAGE_ORDERS_ADDRESS data model.
     * @param $addressDataArray
     * @return mixed
     */
    public function insertAddress($addressDataArray)
	{
		$diffs = $this->compareDataModels($addressDataArray, MAGE_ORDERS_ADDRESS);

        if (!empty($diffs))
            return $this->error("Data models don't match: ".implode(", ", $diffs), __FUNCTION__);

		$cols = '`' . implode('`,`', array_keys($addressDataArray)) . '`';

		foreach (array_values($addressDataArray) as $v)
		{
			$vals[] = '"'.addslashes($v).'"';
		}

		$vals = implode(',', $vals);

		$qry =	"INSERT INTO `mage_orders_address` ".
				"(".$cols.", `merlin_import_date`)".
				" VALUES ".
				"(".$vals.", NOW())"
        ;

		return $this->db()->query( $qry );
	}

    /**
     * Returns array of address data from Merlin based on $filterArray and $dataModel. Default data model is MERLIN_ORDERS_ADDRESS.
     * @param null $dataModel
     * @param null $filterArray
     * @param null $whereType
     * @return array
     */
    public function getAddress($dataModel = null, $filterArray = null, $whereType = null)
	{
		if (is_null($dataModel) || !$this->getDataModel($dataModel))
			$dataModel = MERLIN_ORDERS_ADDRESS;

        $qry =	"SELECT ".
            $this->selectCols($this->getDataModel($dataModel)).
            "FROM `".$dataModel."`".
            $this->where($filterArray, $whereType);
        ;

        if (!$res = $this->db()->query( $qry ))
            return $this->error("Bad MySQL query: ".$qry, __FUNCTION__);

        while ($row = $this->db()->getRow($res))
        {
            $addressData[] = $row;
        }

        return $addressData;
	}

    /**
     * Inserts data into Merlin based on MAGE_ORDERS_ITEMS data model.
     * @param $itemDataArray
     * @return mixed
     */
    public function insertItem($itemDataArray)
	{
		$diffs = $this->compareDataModels($itemDataArray, MAGE_ORDERS_ITEMS);

        if (!empty($diffs))
            return $this->error("Data models don't match: ".implode(", ", $diffs), __FUNCTION__);

		$cols = '`' . implode('`,`', array_keys($itemDataArray)) . '`';

		foreach (array_values($itemDataArray) as $v)
		{
			$vals[] = '"'.addslashes(trim($v)).'"';
		}

		$vals = implode(',', $vals);

		$qry =	"INSERT INTO `mage_orders_items` ".
				"(".$cols.", `merlin_import_date`)".
				" VALUES ".
				"(".$vals.", NOW())"
        ;

		return $this->db()->query( $qry );
	}

    /**
     * Returns array of item data from Merlin based on $filterArray and $dataModel. Default data model is MERLIN_ORDERS_ITEMS.
     * @param null $dataModel
     * @param null $filterArray
     * @param null $whereType
     * @return array
     */
    public function getItems($dataModel = null, $filterArray = null, $whereType = null)
	{
		if (is_null($dataModel) || !$this->getDataModel($dataModel))
			$dataModel = MERLIN_ORDERS_ITEMS;

        $qry =	"SELECT ".
            $this->selectCols($this->getDataModel($dataModel)).
            "FROM `".$dataModel."`".
            $this->where($filterArray, $whereType);
        ;

        if (!$res = $this->db()->query( $qry ))
            return $this->error("Bad MySQL query: ".$qry, __FUNCTION__);

        while ($row = $this->db()->getRow($res))
        {
            $itemData[] = $row;
        }

        return $itemData;
	}

    /**
     * Inserts data into Merlin based on MAGE_CANCELED_ORDERS data model.
     * @param $orderDataArray
     * @return mixed
     */
    public function insertCanceledOrder($orderDataArray)
	{
		$diffs = $this->compareDataModels($orderDataArray, MAGE_CANCELED_ORDERS);

        if (!empty($diffs))
            return $this->error("Data models don't match: ".implode(", ", $diffs), __FUNCTION__);

		// Format Data Insert
		$cols = '`' . implode('`,`', array_keys($orderDataArray)) . '`';

		foreach (array_values($orderDataArray) as $v)
		{
			$vals[] = '"'.addslashes($v).'"';
		}

		$vals = implode(',', $vals);

		$qry =	"INSERT INTO `mage_orders_canceled` ".
				"(".$cols.", `merlin_import_date`)".
				" VALUES ".
				"(".$vals.", NOW())"
        ;

		return $this->db()->query( $qry );
	}

    /**
     * Returns array of canceled order data from Merlin based on $filterArray and $dataModel. Default data model is MERLIN_CANCELED_ORDERS.
     * @param null $dataModel
     * @param null $filterArray
     * @param null $whereType
     * @return array
     */
    public function getCanceledOrders($dataModel = null, $filterArray = null, $whereType = null)
	{
		if (is_null($dataModel) || !$this->getDataModel($dataModel))
			$dataModel = MERLIN_CANCELED_ORDERS;

        $qry =	"SELECT ".
            $this->selectCols($this->getDataModel($dataModel)).
            "FROM `".$dataModel."`".
            $this->where($filterArray, $whereType);
        ;

        if (!$res = $this->db()->query( $qry ))
            return $this->error("Bad MySQL query: ".$qry, __FUNCTION__);

        while ($row = $this->db()->getRow($res))
        {
            $orderData[] = $row;
        }

        return $orderData;
	}

    /**
     * Get orders to pass to CEMI API
     * @return array|bool
     */
    public function getOrdersForCEMI()
    {
        $qry =  "SELECT ".
                $this->selectCols($this->getDataModel(CEMI_ORDERS)).
                "FROM `mage_orders` JOIN `mage_orders_address` ".
                "ON `mage_orders`.`entity_id` = `mage_orders_address`.`parent_id` ".
				"WHERE `address_type`='billing' ".
				"AND `merlin_status`='CRON_READY' ".
				"GROUP BY `increment_id` ".
				"ORDER BY `created_at` ASC ".
                "LIMIT 500";
        ;
        
        if (!$res = $this->db()->query( $qry ))
            return $this->error("Bad MySQL query: ".$qry, __FUNCTION__);

		$orderDataForCEMI = array();

        while ($row = $this->db()->getRow($res))
        {
            $row['created_at'] = date_format(date_create($row['created_at']), "U");

			foreach($this->getDataModel(CEMI_API_ORDERS) as $k => $v)
				$order[$k] = $row[$v];

			if ($this->getCompany())
                $order['company'] = $this->getCompany();

			array_push($orderDataForCEMI, $order);
        }

        return $orderDataForCEMI;
    }

    /**
     * Get items to pass to CEMI API
     * @param $orderID
     * @return array|bool
     */
    public function getItemsForCEMI($orderID)
    {
        $qry =  "SELECT ".
            $this->selectCols($this->getDataModel(CEMI_ITEMS)).
            "FROM `mage_orders_items`".
            $this->where(array(
                    'order_id'=>$orderID,
                    'product_type'=>'simple'
                ),
                "AND"
            );
        ;
        
        if (!$res = $this->db()->query( $qry ))
            return $this->error("Bad MySQL query: ".$qry, __FUNCTION__);

        while ($row = $this->db()->getRow($res))
        {
            foreach($this->getDataModel(CEMI_API_ITEMS) as $k => $v)
                $item[$k] = $row[$v];

        	$orderDataForCEMI[] = $item;
        }

        return $orderDataForCEMI;
    }

    /**
     * Get shipping info to pass to CEMI API
     * @param $orderID
     * @return bool
     */
    public function getShippingForCEMI($orderID)
    {
        $qry =  "SELECT ".
            $this->selectCols($this->getDataModel(CEMI_SHIPPING)).
            "FROM `mage_orders_address` ".
            $this->where(
				array(
					'parent_id' => $orderID,
					'address_type' => 'shipping',
				),
				"AND"
    		)
        ;
		
        if (!$res = $this->db()->query( $qry ))
            return $this->error("Bad MySQL query: ".$qry, __FUNCTION__);

        $shipDataForCEMI = array();

        while ($row = $this->db()->getRow($res))
        {
            foreach($this->getDataModel(CEMI_API_SHIPPING) as $k => $v)
                $shipInfo[$k] = $row[$v];
            
            $shipInfo['addline1'] = $row['firstname']." ".$row['lastname'];
        }
        
        
        return $shipInfo;
    }

    /**
     * Save matching cemi_itemid in Merlin
     * @param $merlinItemID
     * @param $CEMI_itemid
     * @return bool|resource
     */
    public function saveItemFromCEMI($merlinItemID, $CEMI_itemid)
    {
        $qry =	"UPDATE `mage_orders_items` SET `cemi_itemid`='".
		        $CEMI_itemid."'".
		        "WHERE `mo_id`='".
		        $merlinItemID."'"
		;

        if (!$res = $this->db()->query( $qry ))
            return $this->error("Bad MySQL query: ".$qry, __FUNCTION__);

        return $this->db()->query( $qry );
    }

    /**
     * Save matching cemi_orer_id in Merlin
     * @param $merlinOrderID
     * @param $CEMI_orderid
     * @return bool|resource
     */
    public function saveOrderFromCEMI($merlinOrderID, $CEMI_orderid)
    {
        $qry =	"UPDATE `mage_orders` SET `cemi_orderid`='".
		        $CEMI_orderid."', ".
                "`merlin_process_date`=NOW(), ".
                "`cemi_company`='".$this->getCompany()."', ".
                "`cemi_clientid`='".$this->getClientID()."', ".
                "`merlin_status`='PROCESSING' ".
		        "WHERE `mo_id`='".
		        $merlinOrderID."'"
		;

        if (!$res = $this->db()->query( $qry ))
            return $this->error("Missing orders.", "getOrdersForCEMI");

        return $res;
    }
    
    public function setOrderStatus($moID, $status)
    {
    	$qry =	"UPDATE `mage_orders` ".
    			"SET `merlin_status`='{$status}' ".
    			"WHERE `mo_id`='".$moID."'"
		;
		
		$res = $this->db()->query( $qry );

		return $res;
    }

    /**
     * @param $orderID
     * @return mixed
     */
    public function getIncrementID($orderID)
    {
    	$qry =	"SELECT `increment_id` ".
    			"FROM `mage_orders` ".
    			"WHERE `entity_id`='".$orderID."' ".
    			"LIMIT 1"
    	;
    	
    	$res = $this->db()->query( $qry );
    	$row = $this->db()->getRow($res);
    	
    	return $row['increment_id'];
    }

    /**
     * @param $orderID
     * @return mixed
     */
    public function getEntityID($incrementID)
    {
    	$qry =	"SELECT `entity_id` ".
    			"FROM `mage_orders` ".
    			"WHERE `increment_id`='".$incrementID."' ".
    			"LIMIT 1"
    	;

    	$res = $this->db()->query( $qry );
    	$row = $this->db()->getRow($res);

    	return $row['entity_id'];
    }

    /**
     * @param $client
     * @param null $password
     */
    public function setLogin($client, $password = null)
    {
        $this->_client      = $client;
        $this->_password    = $password;
    }

    /**
     * @return array
     */
    public function getLogin()
    {
        $loginArray = array();

        if (isset($this->_client) && !empty($this->_client))
        {
            if (isset($this->_password))
            {
                $loginArray['client'] = $this->_client;
                $loginArray['api_password'] = $this->_password;
            }
            else
            {
                $loginArray['clientid'] = $this->_client;
            }
        }

        return $loginArray;
    }

    /**
     * @param $username
     * @param $email
     * @param $password
     */
    public function setUser($username, $email = null, $password = null)
    {
    	if (isset($email) && !empty($email) && isset($password) && !empty($password))
    	{
	        $this->_user = array(
	            "username"  =>  $username,
	            "email"     =>  $email,
	            "password"  =>  $password
	        );
    	}
    	else
    	{
    		$this->_user = array(
    			"userid"	=>	$username
    		);
    	}
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->_user;
    }

    /**
     * @param $companyInfo
     */
    public function setCompany($companyInfo)
    {
        $this->_company = $companyInfo;
    }

    /**
     * @return bool
     */
    public function getCompany()
    {
        if (isset($this->_company) && !empty($this->_company))
            return $this->_company;
        else
            return "";
    }

    /**
     * @depreciated
     */
    public function setClient($clientDataArray)
    {
        $this->_client = $clientDataArray;
    }

    /**
     * @depreciated
     */
    public function getClient()
    {
        if (isset($this->_client) && !empty($this->_client))
            return $this->_client;
        else
            return false;
    }

    /**
     * @param $CEMI_clientID
     */
    public function setClientID($CEMI_clientID)
    {
        $this->_clientID = $CEMI_clientID;
    }

    /**
     * @return mixed
     */
    public function getClientID()
    {
        return $this->_clientID;
    }

    /**
     * @param $msg
     * @param null $function
     * @return bool
     */
    public function error($msg, $function = null)
    {
        if (is_array($msg))
            $msg = implode(", ", $msg);

        $msg = __CLASS__."::".$function." - ".$msg;
        echo "<pre>".$msg."</pre>";
//        trigger_error($msg, E_USER_WARNING);
        return false;
    }

    /**
     * @param $orderIncrementID
     * @return array|bool
     */
    public function getTrackingInfo($orderIncrementID)
    {
    	$qry =	"SELECT `shipping_method`, `tracking_number` ".
      			"FROM `mage_orders` ".
      			"WHERE `increment_id`='".$orderIncrementID."' ".
      			"LIMIT 1"
      	;
      	
      	$res = $this->db()->query( $qry );
      	$row = $this->db()->getRow($res);
      	
      	if (!isset($row['tracking_number']) || is_null($row['tracking_number']))
      		return false;
      	
      	$carrier = explode("_", $row['shipping_method']);
      	
    	$data = array();
    	
    	$data['carrier_code']	= $carrier[0];
    	$data['title']			= $carrier[1];
    	$data['number']			= $row['tracking_number'];
    	
    	return $data;
    }

    /**
     * Creates a invoice in Magento for $orderIncrementID based on set invoiceUrl().
     * @param $orderIncrementID
     * @param bool $sendEmail
     * @return mixed|string
     */
    public function createInvoice($orderIncrementID, $sendEmail = false)
    {
    	if (!$this->getInvoiceUrl())
    		return "Invoice URL not set.";
    	 
    	$ch		= curl_init();
    	$url	= $this->getInvoiceUrl()."?oid=".$orderIncrementID."&se=".$sendEmail;
    	
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	 
    	$output	= curl_exec($ch);
    	curl_close($ch);
    	 
    	return $output;
    }

    /**
     * Set the URL for creating invoices.
     * @param $url
     */
    public function setInvoiceUrl($url)
    {
    	$this->_invoiceUrl = $url;
    }

    /**
     * Returns the URL for creating invoices.
     * @return bool
     */
    public function getInvoiceUrl()
    {
    	if (isset($this->_invoiceUrl) && !empty($this->_invoiceUrl))
    		return $this->_invoiceUrl;
    	else
    		return false;
    }

    /**
     * Creates a shipment in Magento for $orderIncrementID based on set shippingUrl().
     * @param $orderIncrementID
     * @param null $trackingInfo
     * @param bool $sendEmail
     * @return mixed|string
     */
    public function createShipment($orderIncrementID, $trackingInfo = null, $itemInfo = null, $sendEmail = false)
    {
    	if (!$this->getShipmentUrl())
    		return "Shipment URL not set.";
    	
    	$qryStr = array(
    		'oid'	=>	$orderIncrementID,
    		'se'	=>	$sendEmail
    	);

    	if (!is_null($trackingInfo))
    		$qryStr = array_merge($qryStr, $trackingInfo);
    	
    	if (!is_null($itemInfo))
    		$qryStr = arrray_merge($qryStr, $itemInfo);

    	$ch		= curl_init();
    	$url	= $this->getShipmentUrl()."?".http_build_query($qryStr);

    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	 
    	$output	= curl_exec($ch);
    	curl_close($ch);
    	 
    	return $output;
    }

    /**
     * Sets the URL for creating shipments.
     * @param $url
     */
    public function setShipmentUrl($url)
    {
    	$this->_shipmentUrl = $url;
    }

    /**
     * Returns the URL for creating shipments.
     * @return bool
     */
    public function getShipmentUrl()
    {
    	if (isset($this->_shipmentUrl) && !empty($this->_shipmentUrl))
    		return $this->_shipmentUrl;
    	else
    		return false;
    }

    /**
     * Returns the highest entity_id from magento orders.
     * @return mixed
     */
    public function getLastMageOrderID()
    {
    	$qry =	"SELECT `entity_id` ".
    			"FROM `merlinDB`.`mage_orders` ".
    			"ORDER BY `entity_id` DESC ".
    			"LIMIT 1"
    	;
    	
    	$res = $this->db()->query( $qry );
    	$row = $this->db()->getRow($res);
    	
    	return $row['entity_id'];
    }
    
    /**
     * Returns the highest entity_id from canceled magento orders.
     * @return mixed
     */
    public function getLastCanceledOrderID()
    {
    	$qry =	"SELECT `entity_id` ".
    			"FROM `merlinDB`.`mage_orders_canceled` ".
    			"ORDER BY `entity_id` DESC ".
    			"LIMIT 1"
    	;
    	
    	$res = $this->db()->query( $qry );
    	$row = $this->db()->getRow($res);
    	
    	return $row['entity_id'];
    }

    /**
     * Returns the number of broken items in an order.
     * @param $orderID
     * @return mixed
     * @depreciated
     */
    public function orderHasBrokenItems($orderID)
    {
        $qry =  "SELECT SUM(IF(!`is_virtual` && !`cemi_itemid`, 1, 0)) AS `broken_items` ".
                "FROM `mage_orders_items` ".
                "WHERE `order_id`='".$orderID."' ".
                "GROUP BY `order_id`"
        ;

        $res = $this->db()->query( $qry );
        $row = $this->db()->getRow($res);

        return $row['broken_items'];
    }
	
    public function recheckOrders($dbIBD)
    {
        $qry = "
        		SELECT 
        			`entity_id`, `sku`, `cemi_itemid`, `product_type`, 'qty_ordered'
        		FROM 
        			`mage_orders_items` JOIN `mage_orders` ON `entity_id` = `order_id`
        		WHERE 
					merlin_status='BAD_CEMI_ITEMID' 
        			OR `merlin_status`='NO_INVENTORY'
       		";
        
        $res = $this->db()->query( $qry );
        
        while ($row = $this->db()->getRow($res))
        {
        	$orders[] = $row;
        	$orderIDs[] = $row['entity_id'];
        }
        
        $orderIDs = array_unique($orderIDs);
        
        mysql_free_result($res);

        
        // Match items against CEMI items & save for processing at end.
        //--------------------------------------------
	    foreach($orders as $ordersItem)
		{
		    $ISBN = trim($ordersItem['sku']);
			
	    	$qry =	"SELECT 
	    				`a`.`itemid`,
	    				`a`.`name2`,
	    				sum(`b`.`qty_on_hand`) as `qoh`,
	    				sum(`b`.`qty_allocated`) as `alloc`, 
	    				(sum(`b`.`qty_on_hand`) - sum(`b`.`qty_allocated`)) as `diff` ".
					"FROM `items` as `a` ".
					"LEFT OUTER JOIN `item_inventory` as `b` ".
					"ON `a`.`itemid` = `b`.`itemid` ".
					"WHERE `clientid`='".$this->getClientID()."' ".
					"AND TRIM(`a`.`name2`)='".$ISBN."' ".
					"AND `a`.`active`=1 ".
					"AND `a`.`deleted`=0 ".
					"GROUP BY `name2` ".
					"LIMIT 1"
			;
						
	        $res = $dbIBD->query( $qry );
	        $CEMI_itemInfo = $dbIBD->getRow($res);
	        
	        if (isset($CEMI_itemInfo['itemid']))
	        {
		        $item['cemi_itemid'] = $CEMI_itemInfo['itemid'];
		    }
		    else
		    {
		        $item['cemi_itemid'] = null;
		    }
			
		    // Save status of order items to determine if order is DIGI, BAD_CEMI_ID, or CRON_READY, etc.
		    if ($ordersItem['product_type'] == 'simple')
		        $orderStatus[$ordersItem['entity_id']]['hasSimple'] = true;
		
		    if ($ordersItem['product_type'] == 'downloadable')
		        $orderStatus[$ordersItem['entity_id']]['hasVirtual'] = true;
			
		    if ($ordersItem['product_type'] == 'simple' && is_null($item['cemi_itemid']))
		        $orderStatus[$ordersItem['entity_id']]['hasBrokenItem'] = true;
		    
		    if ($CEMI_itemInfo['diff'] - (int) $item['qty_ordered'] <= 0)
		    	$orderStatus[$ordersItem['entity_id']]['noInventory'] = true;
		    
		}

		
        // Determine & set order status
        //--------------------------------------------
        foreach ($orderIDs as $orderID)
        {
            if (is_null($orderStatus[$orderID]['hasSimple']))
                $order['merlin_status'] = "DIGI";

            else if ($orderStatus[$orderID]['hasBrokenItem'])
                $order['merlin_status'] = "BAD_CEMI_ITEMID";
            
            else if ($orderStatus[$orderID]['noInventory'])
            	$order['merlin_status'] = "NO_INVENTORY";

            else
                $order['merlin_status'] = "CRON_READY";
			
            
            $qry = "
            		UPDATE 
            			`mage_orders`
            		SET 
            			`merlin_status`='".$order['merlin_status']."'
            		WHERE 
            			`entity_id`='".$orderID."'
            	";
            
            $res = $this->db()->query( $qry );
        }
		
        unset($orders);
        unset($items);
    }
	
    /**
     * @abstract This gets all the Magento items that cannot be found in CEMI.
     * @param string $filterSQL Optional. An SQL ready (lead with AND...) where clause filter (use mo.X for orders table and moi.X for items table).
     * @return array Returns an associative array keyed by SKU of missing CEMI items.
     */
    public function getMissingCEMIItems($filterSQL = "")
    {
    	$results = array();
    	$sql = "
    			SELECT
					CAST(SUM(moi.qty_ordered) AS UNSIGNED) AS total_qty_ordered
    				, (SELECT COUNT(DISTINCT mo.entity_id) FROM merlinDB.mage_orders LEFT JOIN mage_orders_items ON entity_id = order_id WHERE product_id = moi.product_id GROUP BY product_id) AS num_bad_orders
					, moi.sku
					, moi.name
				FROM
					merlinDB.mage_orders mo
					LEFT JOIN mage_orders_items moi ON mo.entity_id = moi.order_id
				WHERE
					(mo.merlin_status = 'BAD_CEMI_ITEMID' AND moi.cemi_itemid = 0)
					OR mo.merlin_status = 'NO_INVENTORY'
    				{$filterSQL}
				GROUP BY
					moi.sku
				ORDER BY
					num_bad_orders desc
					, total_qty_ordered desc
					, moi.name
    	";
//     	dumpArray($sql);
    	$res = $this->db()->query( $sql );
    	while ($row = $this->db()->getRow($res)) {
    		$results[trim($row['sku'])] = $row;
    	}
    	return $results;
    } 
    /**
     * @abstract This gets all the Magento items that cannot be found in CEMI.
     * @param string $filterSQL Optional. An SQL ready (lead with AND...) where clause filter (use mo.X for orders table and moi.X for items table).
     * @return int Returns the number of items not found in CEMI.
     */
    public function getMissingCEMIItemsCount($filterSQL = "")
    {
    	$result = false;
    	$sql = "
				SELECT
					count(distinct moi.sku) as num_bad_orders
				FROM
					merlinDB.mage_orders mo
					LEFT JOIN mage_orders_items moi ON mo.entity_id = moi.order_id
				WHERE
				 	(mo.merlin_status = 'BAD_CEMI_ITEMID' and cemi_itemid=0) 
					OR mo.merlin_status = 'NO_INVENTORY'
    				{$filterSQL}
    	";
    	if ($res = $this->db()->query( $sql )) {
    		while ($row = $this->db()->getRow($res)) {
    			$result = $row['num_bad_orders'];
    		}
    	}
    	return $result;
    }   
    /**
     * @abstract This gets all the Magento orders count that cannot be processed because one or more of its items cannot be found in CEMI.
     * @param string $filterSQL Optional. An SQL ready (lead with AND...) where clause filter (use mo.X for orders table and moi.X for items table).
     * @return int Returns the number of unprocessable orders.
     */
    public function getUnprocessableMerlinOrderCount($filterSQL = "")
    {
    	$result = false;
    	$sql = "
				SELECT
    				count(distinct mo.entity_id) as num_bad_orders
				FROM
					merlinDB.mage_orders mo
					LEFT JOIN mage_orders_items moi ON mo.entity_id = moi.order_id
				WHERE
					(mo.merlin_status = 'BAD_CEMI_ITEMID' AND moi.cemi_itemid = 0)
					OR mo.merlin_status = 'NO_INVENTORY'
					{$filterSQL}
    	";
    	if ($res = $this->db()->query( $sql )) {
	    	while ($row = $this->db()->getRow($res)) {
	    		$result = $row['num_bad_orders'];
	    	}
    	}
    	return $result;
    }
    /**
     * @abstract This gets all the Magento orders that are pending address verification.
     * @param string $filterSQL Optional. An SQL ready (lead with AND...) where clause filter (use mo.X for orders table and moi.X for items table).
     * @return array Returns an associative array keyed by SKU of missing CEMI items.
     */
    public function getPendingAddressVerificationMerlinOrders($filterSQL = "")
    {
    	$results = array();
   				/* -- these are all the address fields...
					-- mo.*,
					moa_id,
				    moa.entity_id,
				    moa.parent_id,
				    moa.customer_address_id,
				    moa.customer_id,
				    moa.region,
				    moa.postcode,
				    moa.lastname,
				    moa.street,
				    moa.city,
				    moa.email,
				    moa.telephone,
				    moa.country_id,
				    moa.firstname,
				    moa.address_type,
				    moa.prefix,
				    moa.middlename,
				    moa.suffix,
				    moa.company,
				    moa.merlin_import_date,
				    moa.shipto_address
				/* */
    	$sql = "
				SELECT DISTINCT
					mo.increment_id
					, moa.*
				FROM
					mage_orders_address moa
					LEFT JOIN mage_orders mo ON moa.parent_id = mo.entity_id
				WHERE
					mo.mo_id IN (SELECT mo_id FROM merlinDB.mage_orders WHERE merlin_status = 'ADDR_VERIFY')
					AND address_type IN ('shipping', 'CS_ADDRESS')
    				{$filterSQL}
				ORDER BY
					mo.entity_id
					, moa.moa_id
				-- LIMIT 100
    	";
//     	if ($filterSQL) dumpArray($sql);
		$lastMOParentID = 0;
    	$res = $this->db()->query( $sql );
    	while ($row = $this->db()->getRow($res)) {
    		if ($lastMOParentID != $row['increment_id']) {
    			$lastMOParentID = $row['increment_id'];
    			$results[$lastMOParentID] = array(
//     					'billing'				=> array(),
    					'shipping'				=> array(),
    					'possible_addresses'	=> array()
    			);
    		}
    		switch ($row['address_type']) {
    			case 'CS_ADDRESS':
    				$results[$lastMOParentID]['possible_addresses'][] = $row;
    				break;
//     			case 'billing':
    			case 'shipping':
    				$results[$lastMOParentID][$row['address_type']] = $row;
    				break;
    		}
    	}
    	return $results;
    }
    /**
     * @abstract This gets the count of Magento orders that are pending address verification.
     * @param string $filterSQL Optional. An SQL ready (lead with AND...) where clause filter.
     * @return int Returns the number of unprocessable orders.
     */
    public function getPendingAddressVerificationMerlinOrderCount($filterSQL = "")
    {
    	$result = false;
    	$sql = "
				SELECT
					COUNT(merlin_status) AS count_addr_verify
				FROM
					merlinDB.mage_orders
				WHERE
					merlin_status = 'ADDR_VERIFY'
    				{$filterSQL}
    	";
    	if ($res = $this->db()->query( $sql )) {
    		while ($row = $this->db()->getRow($res)) {
    			$result = $row['count_addr_verify'];
    		}
    	}
    	return $result;
    }
    /**
     * @abstract This sets the ship-to address to use for the given Magento order.
     * @param int $orderid Required. The orderid to update.
     * @param int $addId Required. The address id to set as the ship-to address.
     * @param array $errors Required. Array of errors passed by reference used to capture any errors.
     * @return Rerturns true or false if the updates work or fail respectively.
     */
    public function updateOrderSelectedShipToAddress($orderid, $addId, &$errors) {
    	$sql = "
    			UPDATE mage_orders_address
    			SET shipto_address = 0
    			WHERE parent_id = {$orderid}
    	";
    	if ($result = $this->db()->query($sql)) {
	    	$sql = "
	    			UPDATE mage_orders_address
	    			SET shipto_address = 1
	    			WHERE moa_id = {$addId}
	    	";
	    	if ($result = $this->db()->query($sql)) {
	    		$sql = "
			    		UPDATE mage_orders
			    		SET merlin_status = 'CRON_READY'
			    		WHERE entity_id = {$orderid}
	    		";
	    		if ($this->db()->query($sql)) {
	    			return true;
	    		} else {
	    			$errors[] = "Could not reset order for processing for orderid {$orderid}.";
	    			return false;
	    		}
	    	} else {
	    		$errors[] = "Could not update address for orderid {$orderid}.";
	    		return false;
	    	}
    	} else {
    		$errors[] = "Could not clear addresses for orderid {$orderid}.";
    		return false;
    	}
    }
    public function getBackorderItemsAndCounts() {
    	$results = array();
    	$sql = "
    			SELECT
    				moi.cemi_itemid
    				, COUNT(moi.cemi_itemid) bo_count
    				, SUM(moi.qty_ordered) total_qty_ordered
				FROM
					mage_orders mo
					LEFT JOIN mage_orders_items moi ON mo.entity_id = moi.order_id
				WHERE
					mo.merlin_status = 'NO_INVENTORY'
					AND cemi_itemid != 0
				GROUP BY
					moi.cemi_itemid
    	";
    	if ($res = $this->db()->query( $sql )) {
    		while ($row = $this->db()->getRow($res)) {
    			$cemi_itemid = $row['cemi_itemid'];
    			unset($row['cemi_itemid']);
    			$results[$cemi_itemid] = $row;
    		}
    	}
    	return $results;
    }
    public static function formatISBN($isbn) {
		$len = strlen($isbn);
		if ($len > 4) {
			return substr($isbn, 0, -4)."-".substr($isbn, -4);
		} else {
			return $isbn;
		}
	}
    /*------------------------------------------------
      Protected Methods
    -------------------------------------------------*/

    /**
     * Logs actions in Merlin.
     * @param $user
     * @param $action
     * @param null $idArray
     * @param null $note
     */
    protected function logAction($user, $action, $idArray = null, $note = null)
	{
		$qry =	"INSERT INTO `action_log` ".
				"(`user`, `action`, `created`, `note`) ".
				"VALUES ".
				"('".$user."', '".$action."', NOW(), '".$note."')"
        ;

		$this->db()->query( $qry );
	}

    /**
     * Add orders to $orderData array for future use.
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
     * Add item to $itemData array for future use.
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
     * Sets dbMySQL class for Merlin to use.
     * @param $db
     */
    private function setDB($db)
	{
		$this->_db = $db;
	}

    /**
     * Returns dbMySQL class to use as chained method.
     * @return dbMySQL
     */
    private function db()
	{
		return $this->_db;
	}



}

?>
