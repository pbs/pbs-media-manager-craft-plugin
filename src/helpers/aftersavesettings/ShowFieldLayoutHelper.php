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
use craft\elements\Entry;
use craft\models\FieldLayoutTab;

use papertiger\mediamanager\MediaManager;
use papertiger\mediamanager\base\ConstantAbstract;
use papertiger\mediamanager\helpers\SettingsHelper;

class ShowFieldLayoutHelper
{
    // Public Static Methods
    // =========================================================================

    public static function process()
    {   
        // Process Field Layout
        $showSectionHandle  = SettingsHelper::get( 'showSection' );
        $showSection        = Craft::$app->getSections()->getSectionByHandle( $showSectionHandle );
        $entryType          = Craft::$app->getSections()->getEntryTypesBySectionId( $showSection->id )[ 0 ];
        $fieldTabs          = self::_registerTabAndFields( SettingsHelper::get( 'showFieldLayout' ) );
        $fieldLayout        = $entryType->getFieldLayout();
        $fieldLayout->type  = Entry::class;

        $fieldLayout->setTabs( $fieldTabs[ 'layoutTabs' ] );
        $fieldLayout->setFields( $fieldTabs[ 'layoutFields' ] );

        if( !Craft::$app->getSections()->saveEntryType( $entryType ) ) {
            throw new Exception( 'Failed to save show section.' );
        }
    }


    // Private Static Methods
    // =========================================================================
    
    private static function _registerTabAndFields( $fields )
    {
        $layoutTabs   = [];
        $layoutFields = [];
        $sortOrder    = 0;

        foreach( $fields as $key => $fieldItems ) {
            
            $tab            = new FieldLayoutTab();
            $tab->name      = $key;
            $tab->sortOrder = $sortOrder + 1;
            $tabFields      = [];

            if( !empty( $fieldItems ) ) {

                foreach( $fieldItems as $fieldItem ) {

                    $fieldModel = Craft::$app->getFields()->getFieldByHandle( $fieldItem );

                    if( $fieldModel ) {
                        $tabFields[]    = $fieldModel;
                        $layoutFields[] = $fieldModel;
                    }
                }
            }

            $tab->setFields( $tabFields );
            $layoutTabs[] = $tab;
        }

        return [
            'layoutTabs'   => $layoutTabs,
            'layoutFields' => $layoutFields
        ];
    }
}
