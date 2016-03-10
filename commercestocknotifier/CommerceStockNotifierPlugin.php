<?php

class CommerceStockNotifierPlugin extends BasePlugin
{
	// ...

	protected $threshold = 100;
	protected $originalStocks;

	public function init()
	{
		// Register event listeners
		craft()->on('commerce_orders.beforeOrderComplete', [$this, 'onBeforeOrderComplete']);
		craft()->on('commerce_orders.orderComplete', [$this, 'onOrderComplete']);
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

			if (
				$variant instanceof Commerce_VariantModel &&
				!$variant->unlimitedStock &&
				$variant->stock > $this->threshold
			)
			{
				$this->originalStocks[$variant->id] = $variant->stock;
			}
		}
	}

	public function onOrderComplete(Event $event)
	{
		/** @var Commerce_OrderModel $order */
		$order = $event->params['order'];

		// Loop through each of the line items and note the quantities
		// (remember that a single variant could be associated with multiple line items if its 'options' were different)
		$purchaseQuantities = [];

		foreach ($order->getLineItems() as $lineItem)
		{
			// Do we care about this variant?
			$variantId = $lineItem->purchasableId;
			if (isset($this->originalStocks[$variantId]))
			{
				if (!isset($purchaseQuantities[$variantId]))
				{
					$purchaseQuantities[$variantId] = 0;
				}

				$purchaseQuantities[$variantId] += $lineItem->qty;
			}
		}

		// Now that we know the total quantities of each line item purchased,
		// see which (if any) just went below our notification threshold
		foreach ($this->originalStocks as $variantId => $originalStock)
		{
			if (isset($purchaseQuantities[$variantId]))
			{
				$newStock = $originalStock - $purchaseQuantities[$variantId];

				if ($newStock <= $this->threshold)
				{
					// Send email
				}
			}
		}
	}
}
