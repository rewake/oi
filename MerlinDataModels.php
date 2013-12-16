<?php
/**
 * Class MerlinDataModels
 *
 * Defines data models for use between Magento & other DB's
 * @author rkomatz
 **/
class MerlinDataModels extends MerlinUtils
{
	public static $stateCodes = array(
			'Alabama'=>'AL',
			'Alaska'=>'AK',
			'Arizona'=>'AZ',
			'Arkansas'=>'AR',
			'California'=>'CA',
			'Colorado'=>'CO',
			'Connecticut'=>'CT',
			'Delaware'=>'DE',
			'District of Columbia'=>'DC',
			'Florida'=>'FL',
			'Georgia'=>'GA',
			'Hawaii'=>'HI',
			'Idaho'=>'ID',
			'Illinois'=>'IL',
			'Indiana'=>'IN',
			'Iowa'=>'IA',
			'Kansas'=>'KS',
			'Kentucky'=>'KY',
			'Louisiana'=>'LA',
			'Maine'=>'ME',
			'Maryland'=>'MD',
			'Massachusetts'=>'MA',
			'Michigan'=>'MI',
			'Minnesota'=>'MN',
			'Mississippi'=>'MS',
			'Missouri'=>'MO',
			'Montana'=>'MT',
			'Nebraska'=>'NE',
			'Nevada'=>'NV',
			'New Hampshire'=>'NH',
			'New Jersey'=>'NJ',
			'New Mexico'=>'NM',
			'New York'=>'NY',
			'North Carolina'=>'NC',
			'North Dakota'=>'ND',
			'Ohio'=>'OH',
			'Oklahoma'=>'OK',
			'Oregon'=>'OR',
			'Pennsylvania'=>'PA',
			'Rhode Island'=>'RI',
			'South Carolina'=>'SC',
			'South Dakota'=>'SD',
			'Tennessee'=>'TN',
			'Texas'=>'TX',
			'Utah'=>'UT',
			'Vermont'=>'VT',
			'Virginia'=>'VA',
			'Washington'=>'WA',
			'West Virginia'=>'WV',
			'Wisconsin'=>'WI',
			'Wyoming'=>'WY',
			'Puerto Rico'=>'PR'
	);
	
	
    /*------------------------------------------------
      Mage Data Models
      Matches Magento DB - colName => tableName
    -------------------------------------------------*/
	
	protected static $MAGE_Orders = array(
			'entity_id'				=>	'sales_flat_order',
			'state'					=>	'sales_flat_order',
			'status'				=>	'sales_flat_order',
			'shipping_description'	=>	'sales_flat_order',
			'store_id'				=>	'sales_flat_order',
			'customer_id'			=>	'sales_flat_order',
			'total_qty_ordered'		=>	'sales_flat_order',
			'billing_address_id'	=>	'sales_flat_order',
			'customer_group_id'		=>	'sales_flat_order',
			'shipping_address_id'	=>	'sales_flat_order',
			'customer_email'		=>	'sales_flat_order',
			'customer_firstname'	=>	'sales_flat_order',
			'customer_lastname'		=>	'sales_flat_order',
			'customer_middlename'	=>	'sales_flat_order',
			'customer_prefix'		=>	'sales_flat_order',
			'customer_suffix'		=>	'sales_flat_order',
			'discount_description'	=>	'sales_flat_order',
			'shipping_method'		=>	'sales_flat_order',
			'store_name'			=>	'sales_flat_order',
			'created_at'			=>	'sales_flat_order',
			'updated_at'			=>	'sales_flat_order',
			'total_item_count'		=>	'sales_flat_order',
			'increment_id'		    =>	'sales_flat_order',
			'customer_group_code'	=>	'customer_group',
			'customer_group_id'		=>	'customer_group'
	);

    protected static $MAGE_OrdersAddress = array(
			'entity_id'				=>	'sales_flat_order_address',
			'parent_id'				=>	'sales_flat_order_address',
			'customer_address_id'	=>	'sales_flat_order_address',
			'customer_id'			=>	'sales_flat_order_address',
			'region'				=>	'sales_flat_order_address',
			'postcode'				=>	'sales_flat_order_address',
			'lastname'				=>	'sales_flat_order_address',
			'street'				=>	'sales_flat_order_address',
			'city'					=>	'sales_flat_order_address',
			'email'					=>	'sales_flat_order_address',
			'telephone'				=>	'sales_flat_order_address',
			'country_id'			=>	'sales_flat_order_address',
			'firstname'				=>	'sales_flat_order_address',
			'address_type'			=>	'sales_flat_order_address',
			'prefix'				=>	'sales_flat_order_address',
			'middlename'			=>	'sales_flat_order_address',
			'suffix'				=>	'sales_flat_order_address',
			'company'				=>	'sales_flat_order_address'
	);

    protected static $MAGE_OrdersItems = array(
			'item_id'			=>	'sales_flat_order_item',
			'order_id'			=>	'sales_flat_order_item',
			'parent_item_id'	=>	'sales_flat_order_item',
			'store_id'			=>	'sales_flat_order_item',
			'created_at'		=>	'sales_flat_order_item',
			'updated_at'		=>	'sales_flat_order_item',
			'product_id'		=>	'sales_flat_order_item',
			'product_type'		=>	'sales_flat_order_item',
			'qty_ordered'		=>	'sales_flat_order_item',
			'is_virtual'		=>	'sales_flat_order_item',
			'sku'				=>	'sales_flat_order_item',
			'name'				=>	'sales_flat_order_item',
			'description'		=>	'sales_flat_order_item',
//            'attribute_id'      =>  'catalog_product_entity_varchar',
//            'entity_id'         =>  'catalog_product_entity_varchar',
//            'value'             =>  'catalog_product_entity_varchar',
//            'attribute_id'      =>  'eav_attribute',
//            'attribute_code'    =>  'eav_attribute'
	);

    protected static $MAGE_CanceledOrders = array(
			'entity_id'		=>	'sales_flat_order',
			'updated_at'	=>	'sales_flat_order'
	);


    /*------------------------------------------------
      Merlin Data Models
      Matches Merlin DB - colName => tableName
    -------------------------------------------------*/

	protected static $Merlin_Orders = array(
			'mo_id'					=>	'mage_orders',
			'entity_id'				=>	'mage_orders',
			'state'					=>	'mage_orders',
			'status'				=>	'mage_orders',
			'shipping_description'	=>	'mage_orders',
			'store_id'				=>	'mage_orders',
			'customer_id'			=>	'mage_orders',
			'total_qty_ordered'		=>	'mage_orders',
			'billing_address_id'	=>	'mage_orders',
			'shipping_address_id'	=>	'mage_orders',
			'customer_email'		=>	'mage_orders',
			'customer_firstname'	=>	'mage_orders',
			'customer_lastname'		=>	'mage_orders',
			'customer_middlename'	=>	'mage_orders',
			'customer_prefix'		=>	'mage_orders',
			'customer_suffix'		=>	'mage_orders',
			'discount_description'	=>	'mage_orders',
			'shipping_method'		=>	'mage_orders',
			'store_name'			=>	'mage_orders',
			'created_at'			=>	'mage_orders',
			'updated_at'			=>	'mage_orders',
			'total_item_count'		=>	'mage_orders',
			'customer_group_id'		=>	'mage_orders',
			'customer_group_code'	=>	'mage_orders',
            'merlin_status'         =>  'mage_orders',
			'import_date'			=>	'mage_orders'
	);

    protected static $Merlin_OrdersAddress = array(
			'moa_id'				=>	'mage_orders_address',
			'entity_id'				=>	'mage_orders_address',
			'parent_id'				=>	'mage_orders_address',
			'customer_address_id'	=>	'mage_orders_address',
			'customer_id'			=>	'mage_orders_address',
			'region'				=>	'mage_orders_address',
			'postcode'				=>	'mage_orders_address',
			'lastname'				=>	'mage_orders_address',
			'street'				=>	'mage_orders_address',
			'city'					=>	'mage_orders_address',
			'email'					=>	'mage_orders_address',
			'telephone'				=>	'mage_orders_address',
			'country_id'			=>	'mage_orders_address',
			'firstname'				=>	'mage_orders_address',
			'address_type'			=>	'mage_orders_address',
			'prefix'				=>	'mage_orders_address',
			'middlename'			=>	'mage_orders_address',
			'suffix'				=>	'mage_orders_address',
			'company'				=>	'mage_orders_address',
			'import_date'			=>	'mage_orders_address',
			'shipto_address'		=>	'mage_orders_address'
	);

    protected static $Merlin_OrdersItems = array(
			'moi_id'			=>	'mage_orders_items',
			'item_id'			=>	'mage_orders_items',
			'order_id'			=>	'mage_orders_items',
			'parent_item_id'	=>	'mage_orders_items',
			'store_id'			=>	'mage_orders_items',
			'created_at'		=>	'mage_orders_items',
			'product_id'		=>	'mage_orders_items',
			'product_type'		=>	'mage_orders_items',
			'product_options'	=>	'mage_orders_items',
			'is_virtual'		=>	'mage_orders_items',
			'sku'				=>	'mage_orders_items',
			'name'				=>	'mage_orders_items',
			'description'		=>	'mage_orders_items',
			'import_date'		=>	'mage_orders_items'
	);

    protected static $Merlin_CanceledOrders = array(
			'mco_id'		=>	'mage_orders_canceled',
			'entity_id'		=>	'mage_orders_canceled',
			'updated_at'	=>	'mage_orders_canceled',
			'import_date'	=>	'mage_orders_canceled'
	);


	/*------------------------------------------------
	  Constructor Method
	-------------------------------------------------*/

    public function __construct()
	{
        parent::__construct();

		define('MAGE_ORDERS',				'sales_flat_order JOIN customer_group');
		define('MAGE_ORDERS_ADDRESS',		'sales_flat_order_address');
		define('MAGE_ORDERS_ITEMS',			'sales_flat_order_item');
		define('MAGE_CANCELED_ORDERS',		'sales_flat_order');

		define('MERLIN_ORDERS', 			'mage_orders');
		define('MERLIN_ORDERS_ADDRESS', 	'mage_orders_address');
		define('MERLIN_ORDERS_ITEMS', 		'mage_orders_items');
		define('MERLIN_CANCELED_ORDERS', 	'mage_orders_canceled');

	}

    /*------------------------------------------------
      Public Methods
    -------------------------------------------------*/

    /**
     * Returns specified data model as an array or FALSE if data model does not exist.
     * @param $dataModel
     * @return array|bool
     */
    public function getDataModel($dataModel)
	{

		switch ($dataModel)
		{
            // Magento data models
			case MAGE_ORDERS:
				return self::$MAGE_Orders;
				break;
			case MAGE_ORDERS_ADDRESS:
				return self::$MAGE_OrdersAddress;
				break;
			case MAGE_ORDERS_ITEMS:
				return self::$MAGE_OrdersItems;
				break;
			case MAGE_CANCELED_ORDERS:
				return self::$MAGE_CanceledOrders;
				break;

            // Merlin data models
			case MERLIN_ORDERS:
				return self::$Merlin_Orders;
				break;
			case MERLIN_ORDERS_ADDRESS:
				return self::$Merlin_OrdersAddress;
				break;
			case MERLIN_ORDERS_ITEMS:
				return self::$Merlin_OrdersItems;
				break;
			case MERLIN_CANCELED_ORDERS:
				return self::$Merlin_CanceledOrders;
				break;

			default: return false; // TODO: trigger error?
				break;
		}
	}

    /**
     * Compares $dataArray against $dataModel. Returns the differences as an array, or NULL if models match.
     * @param $dataArray
     * @param $dataModel
     * @return array|null
     */
    public function compareDataModels($dataArray, $dataModel)
	{
		if (!isset($dataArray) || empty($dataArray))
		{
			echo "No data";
			exit;
		}

		$diff = array_diff(
				array_keys($this->getDataModel($dataModel)),
				array_keys($dataArray)
		);

		if (is_array($diff) && !empty($diff))
			return $diff;
		else
			return null;
	}

	
	public function getStateCode($stateName)
	{
		if (isset(self::$stateCodes[$stateName]))
			return self::$stateCodes[$stateName];
		else
			return $stateName;
	}
	
	public function getStateName($stateCode)
	{
		if (array_search($stateCode, self::$stateCodes))
			return array_search($stateCode, self::$stateCodes);
		else
			return $stateCode;
	}
}

?>
