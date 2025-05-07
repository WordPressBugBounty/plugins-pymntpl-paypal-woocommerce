<?php


namespace PaymentPlugins\PayPalSDK;

/**
 * Class Authorization
 *
 * @package PaymentPlugins\PayPalSDK
 * @property string $id
 * @property string $status
 * @property StatusDetails $status_details
 * @property Amount $amount
 * @property string $invoice_id
 * @property string $custom_id
 * @property SellerProtection $seller_protection,
 * @property string $expiration_time
 * @property Collection $links
 * @property ProcessorResponse $processor_response
 * @property string $create_time
 * @property string $update_time
 */
class Authorization extends AbstractObject {

	const CREATED = 'CREATED';

	const CAPTURED = 'CAPTURED';

	const DENIED = 'DENIED';

	const PARTIALLY_CAPTURED = 'PARTIALLY_CAPTURED';

	const VOIDED = 'VOIDED';

	const PENDING = 'PENDING';

	/**
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param string $id
	 */
	public function setId( $id ) {
		$this->id = $id;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @param string $status
	 */
	public function setStatus( $status ) {
		$this->status = $status;

		return $this;
	}

	/**
	 * @return StatusDetails
	 */
	public function getStatusDetails() {
		return $this->status_details;
	}

	/**
	 * @param StatusDetails $status_details
	 */
	public function setStatusDetails( $status_details ) {
		$this->status_details = $status_details;

		return $this;
	}

	/**
	 * @return Amount
	 */
	public function getAmount() {
		return $this->amount;
	}

	/**
	 * @param Amount $amount
	 */
	public function setAmount( $amount ) {
		$this->amount = $amount;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getInvoiceId() {
		return $this->invoice_id;
	}

	/**
	 * @param string $invoice_id
	 */
	public function setInvoiceId( $invoice_id ) {
		$this->invoice_id = $invoice_id;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCustomId() {
		return $this->custom_id;
	}

	/**
	 * @param string $custom_id
	 */
	public function setCustomId( $custom_id ) {
		$this->custom_id = $custom_id;

		return $this;
	}

	/**
	 * @return SellerProtection
	 */
	public function getSellerProtection() {
		return $this->seller_protection;
	}

	/**
	 * @param SellerProtection $seller_protection
	 */
	public function setSellerProtection( $seller_protection ) {
		$this->seller_protection = $seller_protection;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getExpirationTime() {
		return $this->expiration_time;
	}

	/**
	 * @param string $expiration_time
	 */
	public function setExpirationTime( $expiration_time ) {
		$this->expiration_time = $expiration_time;

		return $this;
	}

	/**
	 * @return Collection
	 */
	public function getLinks() {
		return $this->links;
	}

	/**
	 * @param Collection $links
	 */
	public function setLinks( $links ) {
		$this->links = $links;

		return $this;
	}

	/**
	 * @return \PaymentPlugins\PayPalSDK\ProcessorResponse
	 */
	public function getProcessorResponse() {
		return $this->processor_response;
	}

	/**
	 * @param \PaymentPlugins\PayPalSDK\ProcessorResponse $processor_response
	 */
	public function setProcessorResponse( $processor_response ) {
		$this->processor_response = $processor_response;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCreateTime() {
		return $this->create_time;
	}

	/**
	 * @param string $create_time
	 */
	public function setCreateTime( $create_time ) {
		$this->create_time = $create_time;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getUpdateTime() {
		return $this->update_time;
	}

	/**
	 * @param string $update_time
	 */
	public function setUpdateTime( $update_time ) {
		$this->update_time = $update_time;

		return $this;
	}

	public function isCreated() {
		return $this->status === self::CREATED;
	}

	public function isCaptured() {
		return $this->status === self::CAPTURED;
	}

	public function isDenied() {
		return $this->status === self::DENIED;
	}

	public function isPartiallyCaptured() {
		return $this->status === self::PARTIALLY_CAPTURED;
	}

	public function isVoided() {
		return $this->status === self::VOIDED;
	}

	public function isPending() {
		return $this->status === self::PENDING;
	}

}