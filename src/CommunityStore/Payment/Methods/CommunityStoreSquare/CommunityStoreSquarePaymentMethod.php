<?php
namespace Concrete\Package\CommunityStoreSquare\Src\CommunityStore\Payment\Methods\CommunityStoreSquare;

use \Concrete\Package\CommunityStore\Controller\SinglePage\Dashboard\Store;
use Core;
use Log;
use Config;
use Package;
use \Square\Environment;
use \Square\Exceptions\ApiException;
use \Square\SquareClient;
use \Square\Models\CreatePaymentRequest;
use \Square\Models\Money;
use \Sujip\Guid\Guid;

use \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as StorePaymentMethod;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Utilities\Calculator as StoreCalculator;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Customer\Customer as StoreCustomer;

class CommunityStoreSquarePaymentMethod extends StorePaymentMethod
{
    public function dashboardForm()
    {
        $this->set('squareMode', Config::get('community_store_square.mode'));
        $this->set('squareCurrency', Config::get('community_store_square.currency'));
        $this->set('squareSandboxApplicationId', Config::get('community_store_square.sandboxApplicationId'));
        $this->set('squareSandboxAccessToken', Config::get('community_store_square.sandboxAccessToken'));
        $this->set('squareSandboxLocation', Config::get('community_store_square.sandboxLocation'));
        $this->set('squareLiveApplicationId', Config::get('community_store_square.liveApplicationId'));
        $this->set('squareLiveAccessToken', Config::get('community_store_square.liveAccessToken'));
        $this->set('squareLiveLocation', Config::get('community_store_square.liveLocation'));
        $this->set('form', Core::make("helper/form"));

        $gateways = array(
            'square_form'=>'Form'
        );

        $this->set('squareGateways', $gateways);

        $currencies = array(
            'AUD'=>t('Australian Dollar'),
            'CAD'=>t('Canadian Dollar'),
            'USD'=>t('US Dollar')
        );

        $this->set('squareCurrencies', $currencies);
    }

    public function save(array $data = [])
    {
        Config::save('community_store_square.mode', $data['squareMode']);
        Config::save('community_store_square.currency', $data['squareCurrency']);
        Config::save('community_store_square.sandboxApplicationId', $data['squareSandboxApplicationId']);
        Config::save('community_store_square.sandboxAccessToken', $data['squareSandboxAccessToken']);
        Config::save('community_store_square.sandboxLocation', $data['squareSandboxLocation']);
        Config::save('community_store_square.liveApplicationId', $data['squareLiveApplicationId']);
        Config::save('community_store_square.liveAccessToken', $data['squareLiveAccessToken']);
        Config::save('community_store_square.liveLocation', $data['squareLiveLocation']);
    }
    public function validate($args, $e)
    {
        return $e;
    }
    public function checkoutForm()
    {
        $mode = Config::get('community_store_square.mode');
        $this->set('mode', $mode);
        $this->set('currency', Config::get('community_store_square.currency'));

        if ($mode == 'live') {
            $this->set('publicAPIKey', Config::get('community_store_square.liveApplicationId'));
            $this->set('locationKey', Config::get('community_store_square.liveLocation'));
        } else {
            $this->set('publicAPIKey', Config::get('community_store_square.sandboxApplicationId'));
            $this->set('locationKey', Config::get('community_store_square.sandboxLocation'));
        }

        $pkgPath = Package::getByHandle('community_store_square')->getRelativePath();

        $this->set('jsPath', $pkgPath . '/elements/community_store_square/js/checkout_form.js');
        $this->set('squareJsUrl', 'https://' . ($mode == 'live' ? '' : 'sandbox.') . 'web.squarecdn.com/v1/square.js');

        $customer = new StoreCustomer();

        $this->set('email', $customer->getEmail());
        $this->set('form', Core::make("helper/form"));
        $this->set('amount', number_format(StoreCalculator::getGrandTotal() * 100, 0, '', ''));

        $pmID = StorePaymentMethod::getByHandle('community_store_square')->getID();
        $this->set('pmID', $pmID);
    }

    public function submitPayment()
    {
        $customer = new StoreCustomer();
        $currency = Config::get('community_store_square.currency');
        $mode =  Config::get('community_store_square.mode');

        if ($mode == 'sandbox') {
            $accessToken = Config::get('community_store_square.sandboxAccessToken');
            $locationKey = Config::get('community_store_square.sandboxLocation');
        } else {
            $accessToken = Config::get('community_store_square.liveAccessToken');
            $locationKey = Config::get('community_store_square.liveLocation');
        }

        $api_client = new SquareClient([
            'accessToken' => $accessToken,
            'environment' => $mode == 'live' ? Environment::PRODUCTION : Environment::SANDBOX
        ]);

        if (empty($_POST['nonce'])) {
            return array('error'=>1,'errorMessage'=>'Something went wrong during the payment process!');
        }

        $idempotency_key = Guid::create();

        $payments_api = $api_client->getPaymentsApi();
        $money = new Money();
        // value is always in cents - no decimal places
        $money->setAmount(StoreCalculator::getGrandTotal()*100);
        $money->setCurrency($currency);

        $create_payment_request = new CreatePaymentRequest($_POST['nonce'], $idempotency_key, $money);
        $create_payment_request->setLocationId($locationKey);

        try {
            $apiResponse = $payments_api->createPayment($create_payment_request);
            if ($apiResponse->isSuccess()) {
                $result = $apiResponse->getResult();
                $payment = $result->getPayment();
                if ($payment && $payment->getStatus()) {
                    $money = $payment->getTotalMoney();
                    $logEntry = join(" ", [
                        $idempotency_key,
                        $payment->getStatus(),
                        $money->getAmount(),
                        $money->getCurrency()
                    ]);
                    Log::addEntry('Square Card - ' . $logEntry, t('Community Store Square'));
                    return ['error'=>0, 'transactionReference'=>$idempotency_key];
                }
            }
            $errors = $apiResponse->getErrors();
            if (is_array($errors)) {
                return ['error' => 1, 'errorMessage' => $errors[0]->getDetail()];
            }
        } catch (ApiException $e) {
        }
        Log::addEntry("Something went wrong during the payment process.", t('Community Store Square'));
        return array('error'=>1,'errorMessage'=>'Something went wrong during the payment process!');
    }

    public function getPaymentMinimum()
    {
        return 1;
    }
    
    public function getPaymentMethodName()
    {
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
