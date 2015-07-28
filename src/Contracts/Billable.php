<?php namespace Laravel\Cashier\Contracts;

use DateTime;

interface Billable
{
	
	/**
	 * Get the name that should be shown on the entity's invoices.
	 *
	 * @return string
	 */
	public function getBillableName();
	
	/**
	 * Write the entity to persistent storage.
	 *
	 * @return void
	 */
	public function saveBillableInstance();
	
	/**
	 * Get a new billing builder instance for the given plan.
	 *
	 * @param  string|null  $plan
	 * @return \InfinityNext\Braintree\Builder
	 */
	public function subscription($plan = null);
	
	/**
	 * Invoice the billable entity outside of regular billing cycle.
	 *
	 * @return void
	 */
	public function invoice();
	
	/**
	 * Find an invoice by ID.
	 *
	 * @param  string  $id
	 * @return \InfinityNext\Braintree\Invoice|null
	 */
	public function findInvoice($id);
	
	/**
	 * Get an array of the entity's invoices.
	 *
	 * @param  array  $parameters
	 * @return array
	 */
	public function invoices($parameters = array());
	
	/**
	 * Apply a coupon to the billable entity.
	 *
	 * @param  string  $coupon
	 * @return void
	 */
	public function applyCoupon($coupon);
	
	/**
	 * Determine if the entity is within their trial period.
	 *
	 * @return bool
	 */
	public function onTrial();
	
	/**
	 * Determine if the entity has an active subscription.
	 *
	 * @return bool
	 */
	public function subscribed();
	
	/**
	 * Determine if the entity's trial has expired.
	 *
	 * @return bool
	 */
	public function expired();
	
	/**
	 * Determine if the entity is on the given plan.
	 *
	 * @param  string  $plan
	 * @return bool
	 */
	public function onPlan($plan);
	
	/**
	 * Determine if billing requires a credit card up front.
	 *
	 * @return bool
	 */
	public function requiresCardUpFront();
	
	/**
	 * Determine if the entity is a Braintree customer.
	 *
	 * @return bool
	 */
	public function readyForBilling();
	
	/**
	 * Determine if the entity has a current Braintree subscription.
	 *
	 * @return bool
	 */
	public function BraintreeIsActive();
	
	/**
	 * Set whether the entity has a current Braintree subscription.
	 *
	 * @param  bool  $active
	 * @return \InfinityNext\Braintree\Contracts\Billable
	 */
	public function setBraintreeIsActive($active = true);
	
	/**
	 * Set Braintree as inactive on the entity.
	 *
	 * @return \InfinityNext\Braintree\Contracts\Billable
	 */
	public function deactivateBraintree();
	
	/**
	 * Get the Braintree ID for the entity.
	 *
	 * @return string
	 */
	public function getBraintreeId();
	
	/**
	 * Set the Braintree ID for the entity.
	 *
	 * @param  string  $Braintree_id
	 * @return \InfinityNext\Braintree\Contracts\Billable
	 */
	public function setBraintreeId($Braintree_id);
	
	/**
	 * Get the current subscription ID.
	 *
	 * @return string
	 */
	public function getBraintreeSubscription();
	
	/**
	 * Set the current subscription ID.
	 *
	 * @param  string  $subscription_id
	 * @return \InfinityNext\Braintree\Contracts\Billable
	 */
	public function setBraintreeSubscription($subscription_id);
	
	/**
	 * Get the last four digits of the entity's credit card.
	 *
	 * @return string
	 */
	public function getLastFourCardDigits();
	
	/**
	 * Set the last four digits of the entity's credit card.
	 *
	 * @return \InfinityNext\Braintree\Contracts\Billable
	 */
	public function setLastFourCardDigits($digits);
	
	/**
	 * Get the date on which the trial ends.
	 *
	 * @return \DateTime
	 */
	public function getTrialEndDate();
	
	/**
	 * Get the subscription end date for the entity.
	 *
	 * @return \DateTime
	 */
	public function getSubscriptionEndDate();
	
	/**
	 * Set the subscription end date for the entity.
	 *
	 * @param  \DateTime|null  $date
	 * @return void
	 */
	public function setSubscriptionEndDate($date);
	
	/**
	 * Get the Braintree supported currency used by the entity.
	 *
	 * @return string
	 */
	public function getCurrency();
	
	/**
	 * Get the locale for the currency used by the entity.
	 *
	 * @return string
	 */
	public function getCurrencyLocale();
	
	/**
	 * Get the tax percentage to apply to the subscription.
	 *
	 * @return int
	 */
	public function getTaxPercent();
	
	/**
	 * Format the given currency for display, without the currency symbol.
	 *
	 * @param  int  $amount
	 * @return mixed
	 */
	public function formatCurrency($amount);
	
	/**
	 * Add the currency symbol to a given amount.
	 *
	 * @param  string  $amount
	 * @return string
	 */
	public function addCurrencySymbol($amount);
	
}
