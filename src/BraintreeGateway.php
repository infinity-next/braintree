<?php namespace InfinityNext\Braintree;

use InfinityNext\Braintree\Contracts\Billable as BillableContract;

use \Braintree_Configuration;
use \Braintree_Transaction;

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
		
		Braintree_Configuration::environment($this->getBraintreeEnvironment());
		Braintree_Configuration::merchantId($this->getBraintreeMerchantId());
		Braintree_Configuration::publicKey($this->getBraintreePublicKey());
		Braintree_Configuration::privateKey($this->getBraintreePrivateKey());
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
		$options = array_merge([
			'currency' => $this->getCurrency(),
		], $options);
		
		$options['amount'] = $amount;
		
		if (! array_key_exists('source', $options) && $this->billable->hasStripeId()) {
			$options['customer'] = $this->billable->getStripeId();
		}
		
		if (! array_key_exists('source', $options) && ! array_key_exists('customer', $options)) {
			throw new InvalidArgumentException("No payment source provided.");
		}
		
		try {
			$response = StripeCharge::create($options);
		} catch (StripeErrorCard $e) {
			return false;
		}
		
		return $response;
	}
	
	/**
	 *
	 *
	 *
	 */
	public function getBraintreeEnvironment()
	{
		
	}
	
	/**
	 *
	 *
	 *
	 */
	public function getBraintreeMerchantId()
	{
		
	}
	
	/**
	 *
	 *
	 *
	 */
	public function getBraintreePublicKey()
	{
		
	}
	
	/**
	 *
	 *
	 *
	 */
	public function getBraintreePrivateKey()
	{
		
	}
	
}