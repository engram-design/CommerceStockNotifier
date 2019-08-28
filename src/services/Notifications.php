<?php
namespace pixelandtonic\commercestocknotifier\services;

use pixelandtonic\commercestocknotifier\Plugin;

use Craft;
use craft\base\Component;

use commerce\elements\Variant;

use yii\base\Event;

class Notifications extends Component
{
    // Properties
    // =========================================================================

    protected $originalStocks;
    protected $lowStockVariants;


    // Public Methods
    // =========================================================================

    public function onBeforeOrderComplete(Event $event)
    {
        $order = $event->sender;

        $threshold = Plugin::getInstance()->getSettings()->threshold;

        // Loop through each of the line items and record the original variant stocks,
        // for any variants that are currently above our notification threshold
        $this->originalStocks = [];

        foreach ($order->getLineItems() as $lineItem) {
            $variant = $lineItem->getPurchasable();

            if (!$variant) {
                continue;
            }

            if ($variant instanceof Variant && !$variant->unlimitedStock && $variant->stock > $threshold) {
                $this->originalStocks[$variant->id] = $variant->stock;
            }
        }
    }

    public function onAfterOrderComplete(Event $event)
    {
        $order = $event->sender;

        $this->lowStockVariants = [];
        $threshold = Plugin::getInstance()->getSettings()->threshold;

        foreach ($order->getLineItems() as $lineItem) {
            $variant = $lineItem->getPurchasable();

            if (!$variant) {
                continue;
            }

            if (isset($this->originalStocks[$variant->id])) {
                // see if the original stock has moved from being above the threshold to
                // below the threshold, and send an email.
                if ($this->originalStocks[$variant->id] > $threshold && $variant->stock <= $threshold) {
                    $this->lowStockVariants[$variant->id] = $variant;
                }
            }

            if (count($this->lowStockVariants) > 0) {
                $this->_sendEmail();
            }
        }
    }

    // Private Methods
    // =========================================================================

    private function _sendEmail()
    {
        $variants = $this->lowStockVariants;

        if (empty(Plugin::getInstance()->getSettings()->toEmail)) {
            return;
        }

        // make to emails into array
        $notify = explode(',', str_replace(';', ',', Plugin::getInstance()->getSettings()->toEmail));
        $to = $notify[0];
        unset($notify[0]);
        $ccEmails = [];

        foreach ($notify as $email) {
            $ccEmails[] = ['email' => $email];
        }

        $body = "Hi, this is a notification that the following items stock has fallen below the threshold set to " . Plugin::getInstance()->getSettings()->threshold . ":<br><br>";

        foreach ($variants as $variant) {
            $body .= "SKU: " . $variant->sku . "<br>";
            $body .= "Description: " . $variant->getDescription() . "<br>";
            $body .= "Stock Remaining: " . $variant->stock . "<br>";
            $body .= "Edit Link: " . $variant->getCpEditUrl() . "<br>";
        }

        $body .= "<br>";

        $email = new Message();
        $email->setTo($to);
        $email->setHtmlBody($body);
        $email->setCc($ccEmails);
        $email->setSubject(count($variants) . " commerce products have dropped below the stock threshold.");

        if (!Craft::$app->getMailer()->send($email)) {
            $error = Craft::t('commerce', 'Commerce Stock threshold notification could not be sent to {email}. Email Body: {body}', [
                'email' => $to,
                'body' => $body,
            ]);

            Craft::error($error, __METHOD__);
        }
    }
}
