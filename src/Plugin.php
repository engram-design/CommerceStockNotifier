<?php
namespace pixelandtonic\commercestocknotifier;

use pixelandtonic\commercestocknotifier\base\PluginTrait;
use pixelandtonic\commercestocknotifier\models\Settings;

use Craft;
use craft\base\Plugin as BasePlugin;
use craft\commerce\elements\Order;

use yii\base\Event;

class Plugin extends BasePlugin
{
    // Properties
    // =========================================================================

    public $schemaVersion = '2.0.0';
    public $hasCpSettings = true;


    // Traits
    // =========================================================================

    use PluginTrait;


    // Public Methods
    // =========================================================================

	public function init()
	{
        parent::init();

        self::$plugin = $this;

        $this->_setPluginComponents();
        $this->_registerCommerceEventListeners();
	}


    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }

    protected function settingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('commerce-stock-notifier/settings', [
            'settings' => $this->getSettings(),
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _registerCommerceEventListeners()
    {
        Event::on(Order::class, Order::EVENT_BEFORE_COMPLETE_ORDER, [$this->getNotifications(), 'onBeforeOrderComplete']);
        Event::on(Order::class, Order::EVENT_AFTER_COMPLETE_ORDER, [$this->getNotifications(), 'onAfterOrderComplete']);
    }

}
