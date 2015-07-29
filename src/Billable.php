<?php namespace InfinityNext\Braintree;

use DateTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait Billable
{
	
	/**
	 * The Braintree API key.
	 *
	 * @var string
	 */
	protected static $BraintreeKeyEnvironment;
	
	/**
	 * The Braintree API key.
	 *
	 * @var string
	 */
	protected static $BraintreeMerchantId;
	
	/**
	 * The Braintree API key.
	 *
	 * @var string
	 */
	protected static $BraintreePublicKey;
	
	/**
	 * The Braintree API key.
	 *
	 * @var string
	 */
	protected static $BraintreePrivateKey;
	
	/**
	 * Get the name that should be shown on the entity's invoices.
	 *
	 * @return string
	 */
	public function getBillableName()
	{
		return $this->email;
	}

	/**
	 * Write the entity to persistent storage.
	 *
	 * @return void
	 */
	public function saveBillableInstance()
	{
		$this->save();
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
		return (new BraintreeGateway($this))->charge($amount, $options);
	}

	/**
	 * Get a new billing gateway instance for the given plan.
	 *
	 * @param  string|null  $plan
	 * @return \InfinityNext\Braintree\BraintreeGateway
	 */
	public function subscription($plan = null)
	{
		return new BraintreeGateway($this, $plan);
	}

	/**
	 * Invoice the billable entity outside of regular billing cycle.
	 *
	 * @return bool
	 */
	public function invoice()
	{
		return $this->subscription()->invoice();
	}
	
	/**
	 * Find an invoice by ID.
	 *
	 * @param  string  $id
	 * @return \InfinityNext\Braintree\Invoice|null
	 */
	public function findInvoice($id)
	{
		$invoice = $this->subscription()->findInvoice($id);

		if ($invoice && $invoice->customer == $this->getBraintreeId()) {
			return $invoice;
		}
	}

	/**
	 * Find an invoice or throw a 404 error.
	 *
	 * @param  string  $id
	 * @return \InfinityNext\Braintree\Invoice
	 */
	public function findInvoiceOrFail($id)
	{
		$invoice = $this->findInvoice($id);

		if (is_null($invoice)) {
			throw new NotFoundHttpException;
		} else {
			return $invoice;
		}
	}

	/**
	 * Get an SplFileInfo instance for a given invoice.
	 *
	 * @param  string  $id
	 * @param  array  $data
	 * @return \SplFileInfo
	 */
	public function invoiceFile($id, array $data)
	{
		return $this->findInvoiceOrFail($id)->file($data);
	}

	/**
	 * Create an invoice download Response.
	 *
	 * @param  string  $id
	 * @param  array   $data
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function downloadInvoice($id, array $data)
	{
		return $this->findInvoiceOrFail($id)->download($data);
	}

	/**
	 * Get an array of the entity's invoices.
	 *
	 * @param  array  $parameters
	 * @return array
	 */
	public function invoices($parameters = array())
	{
		return $this->subscription()->invoices(false, $parameters);
	}

	/**
	 *  Get the entity's upcoming invoice.
	 *
	 * @return @return \InfinityNext\Braintree\Invoice|null
	 */
	public function upcomingInvoice()
	{
		return $this->subscription()->upcomingInvoice();
	}

	/**
	 * Update customer's credit card.
	 *
	 * @param  string  $token
	 * @return void
	 */
	public function updateCard($token)
	{
		return $this->subscription()->updateCard($token);
	}

	/**
	 * Apply a coupon to the billable entity.
	 *
	 * @param  string  $coupon
	 * @return void
	 */
	public function applyCoupon($coupon)
	{
		return $this->subscription()->applyCoupon($coupon);
	}

	/**
	 * Determine if the entity is within their trial period.
	 *
	 * @return bool
	 */
	public function onTrial()
	{
		if (! is_null($this->getTrialEndDate())) {
			return Carbon::today()->lt($this->getTrialEndDate());
		} else {
			return false;
		}
	}

	/**
	 * Determine if the entity is on grace period after cancellation.
	 *
	 * @return bool
	 */
	public function onGracePeriod()
	{
		if (! is_null($endsAt = $this->getSubscriptionEndDate())) {
			return Carbon::now()->lt(Carbon::instance($endsAt));
		} else {
			return false;
		}
	}

	/**
	 * Determine if the entity has an active subscription.
	 *
	 * @return bool
	 */
	public function subscribed()
	{
		if ($this->requiresCardUpFront()) {
			return $this->BraintreeIsActive() || $this->onGracePeriod();
		} else {
			return $this->BraintreeIsActive() || $this->onTrial() || $this->onGracePeriod();
		}
	}

	/**
	 * Determine if the entity's trial has expired.
	 *
	 * @return bool
	 */
	public function expired()
	{
		return ! $this->subscribed();
	}

	/**
	 * Determine if the entity has a Braintree ID but is no longer active.
	 *
	 * @return bool
	 */
	public function cancelled()
	{
		return $this->readyForBilling() && ! $this->BraintreeIsActive();
	}

	/**
	 * Determine if the user has ever been subscribed.
	 *
	 * @return bool
	 */
	public function everSubscribed()
	{
		return $this->readyForBilling();
	}

	/**
	 * Determine if the entity is on the given plan.
	 *
	 * @param  string  $plan
	 * @return bool
	 */
	public function onPlan($plan)
	{
		return $this->BraintreeIsActive() && $this->subscription()->planId() == $plan;
	}

	/**
	 * Determine if billing requires a credit card up front.
	 *
	 * @return bool
	 */
	public function requiresCardUpFront()
	{
		if (isset($this->cardUpFront)) {
			return $this->cardUpFront;
		}

		return true;
	}

	/**
	 * Determine if the entity is a Braintree customer.
	 *
	 * @return bool
	 */
	public function readyForBilling()
	{
		return ! is_null($this->getBraintreeId());
	}

	/**
	 * Determine if the entity has a current Braintree subscription.
	 *
	 * @return bool
	 */
	public function BraintreeIsActive()
	{
		return $this->Braintree_active;
	}

	/**
	 * Set whether the entity has a current Braintree subscription.
	 *
	 * @param  bool  $active
	 * @return \InfinityNext\Braintree\Contracts\Billable
	 */
	public function setBraintreeIsActive($active = true)
	{
		$this->Braintree_active = $active;

		return $this;
	}

	/**
	 * Set Braintree as inactive on the entity.
	 *
	 * @return \InfinityNext\Braintree\Contracts\Billable
	 */
	public function deactivateBraintree()
	{
		$this->setBraintreeIsActive(false);

		$this->Braintree_subscription = null;

		return $this;
	}

	/**
	 * Determine if the entity has a Braintree customer ID.
	 *
	 * @return bool
	 */
	public function hasBraintreeId()
	{
		return ! is_null($this->braintree_id);
	}

	/**
	 * Get the Braintree ID for the entity.
	 *
	 * @return string
	 */
	public function getBraintreeId()
	{
		return $this->braintree_id;
	}
	
	/**
	 * Generates a new Braintree ID for the instance.
	 *
	 * @return string
	 */
	public function createBraintreeId()
	{
		$this->setBraintreeId( (new BraintreeGateway($this))->createBraintreeId() );
	}
	
	/**
	 * Get the name of the Braintree ID database column.
	 *
	 * @return string
	 */
	public function getBraintreeIdName()
	{
		return 'braintree_id';
	}

	/**
	 * Set the Braintree ID for the entity.
	 *
	 * @param  string  $braintree_id
	 * @return \InfinityNext\Braintree\Contracts\Billable
	 */
	public function setBraintreeId($braintree_id)
	{
		$this->braintree_id = $braintree_id;

		return $this;
	}

	/**
	 * Get the current subscription ID.
	 *
	 * @return string
	 */
	public function getBraintreeSubscription()
	{
		return $this->Braintree_subscription;
	}

	/**
	 * Set the current subscription ID.
	 *
	 * @param  string  $subscription_id
	 * @return \InfinityNext\Braintree\Contracts\Billable
	 */
	public function setBraintreeSubscription($subscription_id)
	{
		$this->Braintree_subscription = $subscription_id;

		return $this;
	}

	/**
	 * Get the Braintree plan ID.
	 *
	 * @return string
	 */
	public function getBraintreePlan()
	{
		return $this->Braintree_plan;
	}

	/**
	 * Set the Braintree plan ID.
	 *
	 * @param  string  $plan
	 * @return \InfinityNext\Braintree\Contracts\Billable
	 */
	public function setBraintreePlan($plan)
	{
		$this->Braintree_plan = $plan;

		return $this;
	}

	/**
	 * Get the last four digits of the entity's credit card.
	 *
	 * @return string
	 */
	public function getLastFourCardDigits()
	{
		return $this->last_four;
	}

	/**
	 * Set the last four digits of the entity's credit card.
	 *
	 * @return \InfinityNext\Braintree\Contracts\Billable
	 */
	public function setLastFourCardDigits($digits)
	{
		$this->last_four = $digits;

		return $this;
	}

	/**
	 * Get the date on which the trial ends.
	 *
	 * @return \DateTime
	 */
	public function getTrialEndDate()
	{
		return $this->trial_ends_at;
	}

	/**
	 * Set the date on which the trial ends.
	 *
	 * @param  \DateTime|null  $date
	 * @return \InfinityNext\Braintree\Contracts\Billable
	 */
	public function setTrialEndDate($date)
	{
		$this->trial_ends_at = $date;

		return $this;
	}

	/**
	 * Get the subscription end date for the entity.
	 *
	 * @return \DateTime
	 */
	public function getSubscriptionEndDate()
	{
		return $this->subscription_ends_at;
	}

	/**
	 * Set the subscription end date for the entity.
	 *
	 * @param  \DateTime|null  $date
	 * @return \InfinityNext\Braintree\Contracts\Billable
	 */
	public function setSubscriptionEndDate($date)
	{
		$this->subscription_ends_at = $date;

		return $this;
	}

	/**
	 * Get the Braintree supported currency used by the entity.
	 *
	 * @return string
	 */
	public function getCurrency()
	{
		return 'usd';
	}

	/**
	 * Get the locale for the currency used by the entity.
	 *
	 * @return string
	 */
	public function getCurrencyLocale()
	{
		return 'en_US';
	}

	/**
	 * Get the tax percentage to apply to the subscription.
	 *
	 * @return int
	 */
	public function getTaxPercent()
	{
		return 0;
	}

	/**
	 * Format the given currency for display, without the currency symbol.
	 *
	 * @param  int  $amount
	 * @return mixed
	 */
	public function formatCurrency($amount)
	{
		return number_format($amount / 100, 2);
	}

	/**
	 * Add the currency symbol to a given amount.
	 *
	 * @param  string  $amount
	 * @return string
	 */
	public function addCurrencySymbol($amount)
	{
		return '$'.$amount;
	}
	
	/**
	 * Get the Braintree API key.
	 *
	 * @return string
	 */
	public static function getBraintreeEnvironment()
	{
		return static::$BraintreeKeyEnvironment ?: Config::get('services.braintree.environment');
	}
	
	/**
	 * Set the Braintree API key.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public static function setBraintreeEnvironment($key)
	{
		static::$BraintreeKeyEnvironment = $key;
	}
	
	/**
	 * Get the Braintree API key.
	 *
	 * @return string
	 */
	public static function getBraintreeMerchantId()
	{
		return static::$BraintreeMerchantId ?: Config::get('services.braintree.merchant');
	}
	
	/**
	 * Set the Braintree API key.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public static function setBraintreeMerchantId($key)
	{
		static::$BraintreeMerchantId = $key;
	}
	
	/**
	 * Get the Braintree API key.
	 *
	 * @return string
	 */
	public static function getBraintreePublicKey()
	{
		return static::$BraintreePublicKey ?: Config::get('services.braintree.public');
	}
	
	/**
	 * Set the Braintree API key.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public static function setBraintreePublicKey($key)
	{
		static::$BraintreePublicKey = $key;
	}
	
	/**
	 * Get the Braintree API key.
	 *
	 * @return string
	 */
	public static function getBraintreePrivateKey()
	{
		return static::$BraintreePrivateKey ?: Config::get('services.braintree.secret');
	}
	
	/**
	 * Set the Braintree API key.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public static function setBraintreePrivateKey($key)
	{
		static::$BraintreePrivateKey = $key;
	}
	
}
