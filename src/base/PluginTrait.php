<?php
namespace pixelandtonic\commercestocknotifier\base;

use pixelandtonic\commercestocknotifier\services\Notifications;

use Craft;

trait PluginTrait
{
    // Static Properties
    // =========================================================================

    public static $plugin;


    // Public Methods
    // =========================================================================

    public function getNotifications()
    {
        return $this->get('notifications');
    }

    private function _setPluginComponents()
    {
        $this->setComponents([
            'notifications' => Notifications::class,
        ]);
    }

}