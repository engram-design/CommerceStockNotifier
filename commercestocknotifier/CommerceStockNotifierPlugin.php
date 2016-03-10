<?php
namespace Craft;

class CommerceStockNotifierPlugin extends BasePlugin
{
	protected $originalStocks;
	protected $lowStockVariants;

	public function init()
	{
		// Register event listeners
		craft()->on('commerce_orders.beforeOrderComplete', [$this, 'onBeforeOrderComplete']);
		craft()->on('commerce_variants.onOrderVariant', [$this, 'onOrderVariant']);
	}

	public function onBeforeOrderComplete(Event $event)
	{
		/** @var Commerce_OrderModel $order */
		$order = $event->params['order'];

		// Loop through each of the line items and record the original variant stocks,
		// for any variants that are currently above our notification threshold
		$this->originalStocks = [];

		foreach ($order->getLineItems() as $lineItem)
		{
			$variant = $lineItem->getPurchasable();

			if (!$variant)
			{
				continue;
			}

			if (
				$variant instanceof Commerce_VariantModel &&
				!$variant->unlimitedStock &&
				$variant->stock > $this->getSettings()->threshold
			)
			{
				$this->originalStocks[$variant->id] = $variant->stock;
			}
		}
	}

	public function onOrderVariant(Event $event)
	{
		/** @var Commerce_OrderModel $order */
		$variant = $event->params['variant'];

		if (!$variant->id)
		{
			return;
		}

		$this->lowStockVariants = [];
		$threshold = $this->getSettings()->threshold;

		if (isset($this->originalStocks[$variant->id]))
		{
			// see if the original stock has moved from being above the threshold to
			// below the threshold, and send an email.
			if ($this->originalStocks[$variant->id] > $threshold && $variant->stock <= $threshold)
			{
				$this->lowStockVariants[$variant->id] = $variant;
			}
		}

		if (count($this->lowStockVariants) > 0)
		{
			$this->_sendEmail();
		}
	}

	/**
	 * Returns the plugin’s version number.
	 *
	 * @return string The plugin’s version number.
	 */
	public function getVersion()
	{
		return '1.0.0';
	}

	/**
	 * @return string The plugin’s schema version number.
	 */
	public function getSchemaVersion()
	{
		return '1.0.0';
	}

	/**
	 * @return string
	 */
	public function getDeveloper()
	{
		return 'Pixel & Tonic';
	}

	/**
	 * @return string
	 */
	public function getDeveloperUrl()
	{
		return 'http://pixelandtonic.com';
	}

	/**
	 * @inheritDoc IComponentType::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return "Commerce Stock Notifier";
	}

	/**
	 * @return string
	 */
	public function getPluginUrl()
	{
		return 'https://github.com/pixelandtonic/CommerceStockNotifier';
	}

	/**
	 * @return string
	 */
	public function getDocumentationUrl()
	{
		return $this->getPluginUrl().'/blob/master/README.md';
	}


	/**
	 * @return string
	 */
	public function getReleaseFeedUrl()
	{
		return 'https://raw.githubusercontent.com/pixelandtonic/CommerceStockNotifier/master/releases.json';
	}

	/**
	 * @inheritDoc IPlugin::getDescription()
	 *
	 * @return string|null
	 */
	public function getDescription()
	{
		return "Notify people when the stock of a Craft Commerce product is below a certain threshold.";
	}

	/**
	 * @inheritDoc IPlugin::hasSettings()
	 *
	 * @return bool Whether the plugin has settings
	 */
	public function hasSettings()
	{
		return true;
	}

	/**
	 * @return mixed
	 */
	public function getSettingsHtml()
	{
		return craft()->templates->render('commercestocknotifier/_settings', [
			'settings' => $this->getSettings()
		]);
	}

	/**
	 * Define Commerce Settings.
	 *
	 * @return array
	 */
	protected function defineSettings()
	{
		return [
			'toEmail'   => AttributeType::String,
			'threshold' => AttributeType::Number
		];
	}

	/**
	 * @inheritDoc IPlugin::hasCpSection()
	 *
	 * @return bool
	 */
	public function hasCpSection()
	{
		return false;
	}


	private function _sendEmail()
	{
		$variants = $this->lowStockVariants;

		if (empty($this->getSettings()->toEmail))
		{
			return;
		}

		// make to emails into array
		$notify = explode(',',str_replace(';', ',', $this->getSettings()->toEmail));
		$to = $notify[0];
		unset($notify[0]);
		$ccEmails = [];
		foreach ($notify as $email)
		{
			$ccEmails[] = ['email' => $email];
		}

		$body = "Hi, this is a notification that the following items stock has fallen below the threshold set to ".$this->getSettings()->threshold.":<br><br>";

		/** @var Commerce_VariantModel $variant */
		foreach ($variants as $variant)
		{
			$body .= "SKU: ".$variant->sku."<br>";
			$body .= "Description: ".$variant->getDescription()."<br>";
			$body .= "Stock Remaining: ".$variant->stock."<br>";
			$body .= "Edit Link: ".$variant->getCpEditUrl()."<br>";
		}

		$body .= "<br>";

		$email = new EmailModel();
		$email->toEmail = $to;
		$email->body = $email->htmlBody = $body;
		$email->cc = $ccEmails;
		$email->subject = count($variants)." commerce products have dropped below the stock threshold.";

		if (!craft()->email->sendEmail($email))
		{
			$error = Craft::t('Commerce Stock threshold notification could not be sent to {email}. Email Body: {body}',
				['email' => $to, 'body' => $$body]);

			CommerceStockNotifierPlugin::log($error, LogLevel::Error, true);
		}
	}
}
