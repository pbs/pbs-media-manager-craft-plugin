<?php
/**
 * Media Manager
 *
 * @package       PaperTiger:MediaManager
 * @author        Paper Tiger
 * @copyright     Copyright (c) 2020 Paper Tiger
 * @link          https://www.papertiger.com/
 */

namespace papertiger\mediamanager\jobs;

use Craft;
use craft\helpers\Db;
use craft\queue\BaseJob;
use craft\elements\Entry;
use craft\elements\Asset;
use craft\elements\Tag;
use craft\helpers\ElementHelper;
use craft\helpers\Assets as AssetHelper;

use DateTime;
use papertiger\mediamanager\MediaManager;
use papertiger\mediamanager\helpers\SettingsHelper;

class IdentifyStaleMedia extends BaseJob
{

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $date;

    /**
     * @var string
     */
    public $tags;

    /**
     * @var int
     */
    public $sectionId;

    /**
     * @var int|array
     */
    public $siteId;

    // Public Methods
    // =========================================================================

    public function execute( $queue )
    {

        if (!$this->tags) {
            // too generic, exit
            return;
        }


        $relatedMediaObjects = Entry::find()->sectionId($this->sectionId)->lastSynced("< {$this->date}")->relatedTo(['and', $this->tags]);

        foreach(Db::each($relatedMediaObjects) as $media) {
            Craft::warning("Marking entry ID {$media->id} for deletion.", __METHOD__);
            $media->setFieldValue('markedForDeletion', 1);
            $media->setFieldValue('lastSynced', (new DateTime()));
            Craft::$app->getElements()->saveElement($media);
        }
    }

    // Protected Methods
    // =========================================================================

    protected function defaultDescription(): string
    {
        return Craft::t( 'mediamanager', "Marking entries for deletion." );
    }

    // Private Methods
    // =========================================================================
}
