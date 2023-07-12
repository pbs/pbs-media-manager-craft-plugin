<?php
/**
 * Media Manager
 *
 * @package       PaperTiger:MediaManager
 * @author        Paper Tiger
 * @copyright     Copyright (c) 2020 Paper Tiger
 * @link          https://www.papertiger.com/
 */

namespace papertiger\mediamanager\helpers\aftersavesettings;

use Craft;
use yii\base\Application;

use papertiger\mediamanager\MediaManager;
use papertiger\mediamanager\base\ConstantAbstract;
use papertiger\mediamanager\helpers\SettingsHelper;

class OldSettingsHelper
{
    // Private Properties
    // =========================================================================
    
    private static $settingsToStore = [
        'apiColumnFields',
        'mediaSection',
        'fieldLayout',
        'showSection',
        'showApiColumnFields',
        'showFieldLayout'
    ];


    // Public Static Methods
    // =========================================================================

    public static function process()
    { 
        foreach( self::$settingsToStore as $settingName ) {
            
            $settingValue = SettingsHelper::get( $settingName );
            MediaManager::getInstance()->oldsettings->save( $settingName, $settingValue );
            
        }
    }
}
