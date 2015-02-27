<?php
/**
 * Class to get and process Stamps.com postage, labels, etc. via the Stamps API
 **/
class StampsSWS
{
	// private $callURL = "https://swsim.testing.stamps.com/swsim/SwsimV34.asmx";
	
	public function __construct()
	{
		DEV_SERVER ?
			$this->server = "https://swsim.testing.stamps.com/swsim/swsimv34.asmx?wsdl" :	// Staging
			$this->server = "https://swsim.stamps.com/swsim/swsimv34.asmx?wsdl" ;	// Production
		
		try
		{
			$this->client = new SoapClient($this->server);
		}
		catch (SoapFault $error)
		{
			trigger_error("Could not create SOAP client.". $error, E_USER_WARNING);
			$this->message = $error->faultstring; // "Could not create SOAP client."; 
			return false;
		}
	}
	
	public function authenticate($fullReturnString = false)
	{
		$this->checkClient();
		
		try
		{
			$response = $this->client->AuthenticateUser( array('Credentials' => $this->credentials) );
			
			if ($fullReturnString)
				return $response;
			
			$auth = $response->Authenticator;
			return $auth; 
		}
		catch (SoapFault $error)
		{
			trigger_error("Could not authenticate user.". $error, E_USER_WARNING);
			$this->message = $error->faultstring; //"Could not authenticate user."; 
			return false;
		}
	}
	
	public function cleanAddress($fullReturnString = false)
	{
		$this->checkClient();
		
		try
		{
			$response = $this->client->CleanseAddress(array(
									'Authenticator' => $this->authenticate(),
									'Address' 		=> $this->address
								));
			
			// TODO: international?
			
			if ($fullReturnString)
				return $response;
			
			if ($response->AddressMatch && $response->CityStateZipOK)
			{
				unset($this->address);
				$this->address = $response->Address;
				$this->CleanseHash = $response->CleanseHash;
				$this->OverrideHash = $response->OverrideHash;
				
				return true;
			}
			else if (!$response->AddressMatch && $response->CityStateZipOK)
			{
				unset($this->address);
				$this->address = $response->Address;
				$this->OverrideHash = $response->Address->OverrideHash;

				$this->message = "City, State, and ZIP Code are correct but that the street address was not found. Would you like to override this message?";
				return false; 
			}
			else
			{
				$this->message = "This address cannot be shipped to. City, state, and ZIP Code are invalid.";
				return false;
			}
		}
		catch (SoapFault $error)
		{
			trigger_error("Could not clean address.". $error, E_USER_WARNING);
			$this->message = $error->faultstring; //"Could not clean address.";
			return false;
		}
	}
	
	public function getRates($serviceType = null, $fullReturnString = false)
	{
		$this->checkClient();
		
		try
		{
			$response = $this->client->GetRates(array(
					'Authenticator' => $this->authenticate(),
					'Rate'	 		=> $this->rateInfo
			));
			
			if ($fullReturnString)
				return $response;
			
			unset($this->rates);
			$this->rates = $response->Rates;
			
			if (isset($serviceType))
				return $this->getRateByType($serviceType);

			return $response->Rates;
		}
		catch (SoapFault $error)
		{
			trigger_error("Could not get rates.". $error, E_USER_WARNING);
			$this->message = $error->faultstring; // "Could not get rates."
			return false;
		}
	}
	
	public function getRateByType($serviceType)
	{
		if (!isset($this->rates))
		{
			$this->message = "Could not get type - Rates are not yet set.";
			return false;
		}
		
		foreach ($this->rates->Rate as $rate)
		{
			if ($rate->ServiceType == $serviceType)
			{
				return $rate;
			}
		}
		
		// If you're here it means there were no rates for $serviceType
		$this->message = "There were no rates for your selected service type: \"".$serviceType."\"";
		return false;
	}
	
	public function setAddOns()
	{
		if (!isset($this->rate))
		{
			$this->message = "Could not get add-ons - Rate has not been set.";
			return false;
		}
		
		if (!isset($this->rate->AddOns->AddOnV5))
		{
			trigger_error("AddOnV5 is missing from Stamps.com rate object!!!", E_USER_WARNING);
			$this->message = "Could not get add-ons - information was missing.";
			return false;
		}
		
		$selectedAddOns = func_get_args();
		
		if (count($selectedAddOns) > 0)
		{
			foreach ($this->rate->AddOns->AddOnV5 as $k => $addon)
			{
				if (in_array($addon->AddOnType, $selectedAddOns))
				{
					if (empty($addon->Amount))
					{
						$addons[] = array(
									'AddOnType' => $addon->AddOnType
							);
					}
					else
					{
						$addons[] = array(
									'Amount'	=> $addon->Amount,
									'AddOnType'	=> $addon->AddOnType
							);
					}
				}
			}
		}
		
		unset($this->rate->AddOns->AddOnV5);
		$this->rate->AddOns->AddOnV5 = $addons;
		
		if (!isset($addons))
		{
			$this->message = "Could not set add-ons because selected addons were not available.";
			return false;
		}
		else 
		{
			return true;
		}
	}
	
	public function createShipment($fullReturnString = false)
	{
		$this->checkClient();
		
		try
		{
			$response = $this->client->CreateIndicium(array(
					'Authenticator'	=> $this->authenticate(),
					'IntegratorTxID'=> $this->transactionID,
					'Rate'	 		=> $this->rate,
					'From'	 		=> $this->from,
					'To'	 		=> $this->address,
					'ImageType'		=> 'Pdf',
					'PaperSize'		=> 'LabelSize'
			));
			
			if ($fullReturnString)
				return $response;
			
			unset($this->rates);
			$this->rates = $response->Rates;
			
			if (isset($serviceType))
				return $this->getRateByType($serviceType);
			
			unset($response->Authenticator); // Removing this for SEC
			
			return $response;
		}
		catch (SoapFault $error)
		{
			trigger_error("Could not create shipment.". $error, E_USER_WARNING);
			$this->message = $error->faultstring; //"Could not create shipment. If this problem persists please contact an administrator.";
			return false;
		}
	}
	
	public function cancelShipment($stampsTxID, $fullReturnString = false)
	{
		$this->checkClient();
		
		try
		{
			$response = $this->client->CancelIndicium(array(
					'Authenticator'	=> $this->authenticate(),
					'StampsTxID'	=> $stampsTxID
			));
				
			if ($fullReturnString)
				return $response;
			
			if ($response->Authenticator)
				return true;
		}
		catch (SoapFault $error)
		{
			trigger_error("Could not cancel shipment.". $error, E_USER_WARNING);
			$this->message = $error->faultstring; //"Could not cancel shipment."
			return false;
		}
	}
	
	public function trackShipment($trackingNumber, $fullReturnString = false)
	{
		$this->checkClient();
		
		try
		{
			$response = $this->client->TrackShipment(array(
					'Authenticator'	=> $this->authenticate(),
					'TrackingNumber'=> $trackingNumber
			));
		
			if ($fullReturnString)
				return $response;
			
			return $response->TrackingEvents;
		}
		catch (SoapFault $error)
		{
			trigger_error("Could not get shipment tracking info.". $error, E_USER_WARNING);
			$this->message = $error->faultstring; //"Could not get shipment tracing info."; 
			return false;
		}
	}
	
	public function getAccountInfo($fullReturnString = false)
	{
		$this->checkClient();
		
		try
		{
			$response = $this->client->getAccountInfo(array(
					'Authenticator'	=> $this->authenticate()
			));
			
			if ($fullReturnString)
				return $response;
			
			return $response->AccountInfo;
		}
		catch (SoapFault $error)
		{
			trigger_error("Could not get account info.". $error, E_USER_WARNING);
			$this->message = $error->faultstring; //"Could not get account info."; 
			return false;
		}
	}
	
	public function purchasePostage($amount, $fullReturnString = false)
	{
		// TODO: test or limit purchase amount?
		try
		{
			if (!$controlTotal = $this->getAccountInfo()->PostageBalance->ControlTotal)
			{
				return false;
			}
			
			$response = $this->client->PurchasePostage(array(
					'Authenticator'	=> $this->authenticate(),
					'PurchaseAmount'=> $amount,
					'ControlTotal'	=> $controlTotal
			));
			
			if ($fullReturnString)
				return $response;
			
			unset($response->Authenticator); // Removing this for SEC
			return $response;
		}
		catch (SoapFault $error)
		{
			trigger_error("Could not get purchase postage.". $error, E_USER_WARNING);
			$this->message = $error->faultstring;// "Could not purchase postage."; 
			return false;
		}
	}
	
	public function purchasePostageStatus($transactionID, $fullReturnString = false)
	{
		try
		{
			$controlTotal = $this->getAccountInfo()->PostageBalance->ControlTotal;
				
			$response = $this->client->GetPurchaseStatus(array(
					'Authenticator'	=> $this->authenticate(),
					'TransactionID'	=> $transactionID,
			));
				
			if ($fullReturnString)
				return $response;
				
			unset($response->Authenticator); // Removing this for SEC
			return $response;
		}
		catch (SoapFault $error)
		{
			trigger_error("Could not get purchase status.". $error, E_USER_WARNING);
			$this->message = $error->faultstring; //"Could not get purchase status.";
			return false;
		}
	}
	
	
	// Utility functions
	public function getMessage()
	{
		$msg = $this->message;
		unset($this->message);
		return $msg;
	}
	
	// Getter/Setter Magic 
	public function __get($name)
	{
		// echo "GETTING: " . $this->$name . "<br>";
		return $this->$name;
	}
	
	public function __set($name, $val)
	{
		// echo "SETTING: " . $name . " - " . $val . "<br>";
		$this->$name = $val;
	}
	
	
	////////////////////////////
	protected function checkClient()
	{
		if (!isset($this->client))
		{
			trigger_error("Cannot authenticate user - SoapClient is not set.", E_USER_WARNING);
			$this->message = "Cannot authenticate user - SoapClient is not set."; 
			exit;
		}
	}
}



