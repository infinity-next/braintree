<?php namespace InfinityNext\Braintree;

use InfinityNext\Braintree\Contracts\Billable as BillableContract;

use \Braintree_Configuration as Braintree;
use \Braintree_ClientToken as BraintreeClientToken;
use \Braintree_Customer as BraintreeCustomer;
use \Braintree_Transaction as BraintreeCharge;
use \Braintree_Subscription as BraintreeSubscription;

use Carbon\Carbon;
use InvalidArgumentException;

class BraintreeGateway
{
	
	/**
	 * The billable instance.
	 *
	 * @var \Laravel\Cashier\Contracts\Billable
	 */
	protected $billable;
	
	/**
	 * The name of the plan.
	 *
	 * @var string
	 */
	protected $plan;
	
	/**
	 * The coupon to apply to the subscription.
	 *
	 * @var string
	 */
	protected $coupon;
	
	/**
	 * Indicates if the plan change should be prorated.
	 *
	 * @var bool
	 */
	protected $prorate = true;
	
	/**
	 * Indicates the "quantity" of the plan.
	 *
	 * @var int
	 */
	protected $quantity = 1;
	
	/**
	 * The trial end date that should be used when updating.
	 *
	 * @var \Carbon\Carbon
	 */
	protected $trialEnd;
	
	/**
	 * Indicates if the trial should be immediately cancelled for the operation.
	 *
	 * @var bool
	 */
	protected $skipTrial = false;
	
	/**
	 * Create a new Stripe gateway instance.
	 *
	 * @param  \Laravel\Cashier\Contracts\Billable   $billable
	 * @param  string|null  $plan
	 * @return void
	 */
	public function __construct(BillableContract $billable, $plan = null)
	{
		$this->plan = $plan;
		$this->billable = $billable;
		
		Braintree::environment($this->getBraintreeEnvironment());
		Braintree::merchantId($this->getBraintreeMerchantId());
		Braintree::publicKey($this->getBraintreePublicKey());
		Braintree::privateKey($this->getBraintreePrivateKey());
	}
	
	/**
	 * Make a "one off" charge on the customer for the given amount.
	 *
	 * @param  int  $amount
	 * @param  array  $options
	 * @return bool|mixed
	 */
	public function charge($amount, array $options = array())
	{
		// $options = array_merge([
		// 	'currency' => $this->getCurrency(),
		// ], $options);
		
		$options['amount'] = $amount / 100;
		
		// if (! array_key_exists('source', $options) && $this->billable->hasBraintreeId()) {
		// 	$options['customer'] = $this->billable->getBraintreeId();
		// }
		
		// if (! array_key_exists('source', $options) && ! array_key_exists('customer', $options)) {
		// 	throw new InvalidArgumentException("No payment source provided.");
		// }
		
		// try {
			$response = BraintreeCharge::sale($options);
		// }
		// catch (StripeErrorCard $e) {
		// 	return false;
		// }
		
		return $response;
	}
	/**
	 * Subscribe to the plan for the first time.
	 *
	 * @param  string  $token
	 * @param  array   $properties
	 * @param  object|null  $customer
	 * @return void
	 */
	public function create($token, array $properties = array(), $customer = null)
	{
		$parameters = array_merge([
			'paymentMethodNonce' => $token,
		], $properties);
		
		$response = BraintreeCustomer::create($parameters);
		
		
		return $response;
	}
	
	
	/**
	 * Create a Braintree API customer id for the instance.
	 *
	 * @return string
	 */
	public function createBraintreeId()
	{
		return BraintreeClientToken::generate();
	}
	
	/**
	 * Get the Braintree API environment type for the instance.
	 *
	 * @return string
	 */
	public function getBraintreeEnvironment()
	{
		return $this->billable->getBraintreeEnvironment();
	}
	
	/**
	 * Get the Braintree API merchant id for the instance.
	 *
	 * @return string
	 */
	public function getBraintreeMerchantId()
	{
		return $this->billable->getBraintreeMerchantId();
	}
	
	/**
	 * Get the Braintree API public key for the instance.
	 *
	 * @return string
	 */
	public function getBraintreePublicKey()
	{
		return $this->billable->getBraintreePublicKey();
	}
	
	/**
	 * Get the Braintree API private key for the instance.
	 *
	 * @return string
	 */
	public function getBraintreePrivateKey()
	{
		return $this->billable->getBraintreePrivateKey();
	}
	
	/**
	 * Get the currency for the billable entity.
	 *
	 * @return string
	 */
	protected function getCurrency()
	{
		return $this->billable->getCurrency();
	}
}