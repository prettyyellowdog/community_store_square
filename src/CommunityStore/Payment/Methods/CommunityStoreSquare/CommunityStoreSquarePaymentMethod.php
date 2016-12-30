<?php
namespace Concrete\Package\CommunityStoreSquare\Src\CommunityStore\Payment\Methods\CommunityStoreSquare;

use Concrete\Package\CommunityStore\Controller\SinglePage\Dashboard\Store;
use Core;
use Log;
use Config;
use Exception;
use \Square\Charge;
use Square\Error;

use \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as StorePaymentMethod;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Utilities\Calculator as StoreCalculator;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Customer\Customer as StoreCustomer;
use \SquareConnect\Api\TransactionApi as TransactionApi;

class CommunityStoreSquarePaymentMethod extends StorePaymentMethod
{

    public function dashboardForm()
    {
        $this->set('squareMode', Config::get('community_store_square.mode'));
        $this->set('squareCurrency',Config::get('community_store_square.currency'));
        $this->set('squareSandboxApplicationId',Config::get('community_store_square.sandboxApplicationId'));
        $this->set('squareSandboxAccessToken',Config::get('community_store_square.sandboxAccessToken'));
        $this->set('squareSandboxLocation',Config::get('community_store_square.sandboxLocation'));
        $this->set('squareLiveApplicationId',Config::get('community_store_square.liveApplicationId'));
        $this->set('squareLiveAccessToken',Config::get('community_store_square.liveAccessToken'));
        $this->set('squareLiveLocation',Config::get('community_store_square.liveLocation'));
        $this->set('form',Core::make("helper/form"));

        $gateways = array(
            'square_form'=>'Form'
        );

        $this->set('squareGateways',$gateways);

        $currencies = array(
        	'USD'=>t('US Dollars'),
        	'CAD'=>t('Canadian Dollar')
        );

        $this->set('squareCurrencies',$currencies);
    }

    public function save(array $data = [])
    {
        Config::save('community_store_square.mode',$data['squareMode']);
        Config::save('community_store_square.currency',$data['squareCurrency']);
        Config::save('community_store_square.sandboxApplicationId',$data['squareSandboxApplicationId']);
        Config::save('community_store_square.sandboxAccessToken',$data['squareSandboxAccessToken']);
        Config::save('community_store_square.sandboxLocation',$data['squareSandboxLocation']);
        Config::save('community_store_square.liveApplicationId',$data['squareLiveApplicationId']);
        Config::save('community_store_square.liveAccessToken',$data['squareLiveAccessToken']);
        Config::save('community_store_square.liveLocation',$data['squareLiveLocation']);
    }
    public function validate($args,$e)
    {
        return $e;
    }
    public function checkoutForm()
    {
        $mode = Config::get('community_store_square.mode');
        $this->set('mode',$mode);
        $this->set('currency',Config::get('community_store_square.currency'));

        if ($mode == 'live') {
            $this->set('publicAPIKey',Config::get('community_store_square.liveApplicationId'));
        } else {
            $this->set('publicAPIKey',Config::get('community_store_square.sandboxApplicationId'));
        }

        $customer = new StoreCustomer();

        $this->set('email', $customer->getEmail());
        $this->set('form', Core::make("helper/form"));
        $this->set('amount', number_format(StoreCalculator::getGrandTotal() * 100, 0, '', ''));

        $pmID = StorePaymentMethod::getByHandle('community_store_square')->getID();
        $this->set('pmID',$pmID);
        $years = array();
        $year = date("Y");
        for($i=0;$i<15;$i++){
            $years[$year+$i] = $year+$i;
        }
        $this->set("years",$years);
    }

    public function submitPayment()
    {
        // Alert for debugging purposes only
        // Log::addEntry("Start with submitPayment", t('Community Store Square'));
        $customer = new StoreCustomer();
        $currency = Config::get('community_store_square.currency');
        $mode =  Config::get('community_store_square.mode');

        if ($mode == 'sandbox') {
            $privateKey = Config::get('community_store_square.sandboxAccessToken');
            $locationKey = Config::get('community_store_square.sandboxLocation');
        } else {
            $privateKey = Config::get('community_store_square.liveAccessToken');
            $locationKey = Config::get('community_store_square.liveLocation');
        }

        $token = $_POST['squareToken'];
		    $nonce = $_POST['nonce'];

        // Alert for debugging purposes only
        // Log::addEntry("Nonce: " . $nonce , t('Community Store Square'));

        $genericError = false;
    		if (is_null($nonce)) {
    		  echo "Invalid card data";
    		  http_response_code(422);
    		  return;
    		}
    		$transaction_api = new TransactionApi();
    		$request_body = array (
    		  "card_nonce" => $nonce,
    		  # Monetary amounts are specified in the smallest unit of the applicable currency.
    		  # This amount is in cents. It's also hard-coded for $1.00, which isn't very useful.
    		  "amount_money" => array (
    			"amount" => StoreCalculator::getGrandTotal()*100,
    			"currency" => $currency
    		  ),
    		  # Every payment you process with the SDK must have a unique idempotency key.
    		  # If you're unsure whether a particular payment succeeded, you can reattempt
    		  # it with the same idempotency key without worrying about double charging
    		  # the buyer.
    		  "idempotency_key" => uniqid()
    		);

  		try {
        // Alert for debugging purposes only
        // Log::addEntry("Init actual credit card payment", t('Community Store Square'));
  		  $result = $transaction_api->charge($privateKey, $locationKey, $request_body);

        // Alert for debugging purposes only
        Log::addEntry('Square info.'."\n".'Result is:' . $result . "\n", t('Community Store Square'));

        // credit card payment was successful - updating database - order record
        return array('error'=>0, 'transactionReference'=>$result);
  		} catch (\SquareConnect\ApiException $e) {
        $respBody = $e->getResponseBody();
        if (is_object($respBody)) {
          $errTxt = '';
          foreach($respBody->errors as $respError) {
            $errTxt = $errTxt . $respError->detail;
          }
          $totalError = json_encode($respBody);
          // Log error to backend (Reports - Logs)
          Log::addEntry ("JSON from response body: ".$totalError, t('Community Store Square'));
          // Display error to frontend
          return array('error'=>1,'errorMessage'=> 'Payment Error: '.$errTxt);
        } else {
          // Log error to backend (Reports - Logs)
          Log::addEntry("Something went wrong during the payment process:". $e, t('Community Store Square'));
          // Display error to frontend
          return array ('error'=>1,'errorMessage'=>'Something went wrong during the payment process!');
        }
  		}
    }

    public function getPaymentMethodName(){
        return 'Square';
    }

    public function getPaymentMethodDisplayName()
    {
        return $this->getPaymentMethodName();
    }

    public function getName()
    {
        return $this->getPaymentMethodName();
    }

}

return __NAMESPACE__;
