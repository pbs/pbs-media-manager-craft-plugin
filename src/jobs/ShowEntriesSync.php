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
use craft\db\Query;
use craft\errors\ElementNotFoundException;
use craft\queue\BaseJob;
use craft\elements\Entry;
use craft\elements\Asset;
use craft\elements\Tag;
use craft\helpers\FileHelper;
use craft\helpers\ElementHelper;
use craft\helpers\Assets as AssetHelper;

use papertiger\mediamanager\MediaManager;
use papertiger\mediamanager\helpers\SettingsHelper;
use papertiger\mediamanager\helpers\SynchronizeHelper;
use yii\base\Exception;

class ShowEntriesSync extends BaseJob
{

    // Private Properties
    // =========================================================================

    protected $apiBaseUrl;
    protected $sectionId;
    protected $typeId;
    protected $authorId;
    protected $authorUsername;
    protected $mediaFolderId;
    protected $logProcess;
    protected $logFile;


    // Public Properties
    // =========================================================================
    
    public $title;
    public $auth;
    public $apiKey;


    // Private Properties
    // =========================================================================
    
    private $dateWithMs = 'Y-m-d\TH:i:s.uP';


    // Public Methods
    // =========================================================================
	
	/**
	 * @throws \Throwable
	 * @throws Exception
	 * @throws ElementNotFoundException
	 */
	public function execute( $queue ): void
    {
        $this->apiBaseUrl     = SettingsHelper::get( 'apiBaseUrl' );
        $this->sectionId      = SynchronizeHelper::getShowSectionId(); // SECTION_ID
        $this->typeId         = SynchronizeHelper::getShowSectionTypeId(); // TYPE_ID
        $this->authorId       = SynchronizeHelper::getAuthorId(); // AUTHOR_ID
        $this->authorUsername = SynchronizeHelper::getAuthorUsername(); // AUTHOR_USERNAME
        $this->mediaFolderId  = SynchronizeHelper::getAssetFolderId(); // MEDIA_FOLDER_ID
        $this->logProcess     = 1; // LOG_PROCESS
        $this->logFile        = '@storage/logs/sync.log'; // LOG_FILE

        $url      = $this->generateAPIUrl( $this->apiKey );
        $showEntry = $this->fetchShowEntry($url, '');

        $showAttributes = $showEntry->data->attributes;

        $existingEntry       = $this->findExistingShowEntry( $showEntry->data->id );
        $entry               = $this->chooseOrCreateShowEntry( $showAttributes->title, $existingEntry );
				
				$showImages = $showAttributes->images;
				$showImagesKeywords = ['mezzanine', 'poster', 'white', 'black', 'color'];
				$showImageArray = [];
				
				if(isset($showImages) && is_array($showImages)) {
					foreach( $showAttributes->images as $image ) {
						
						foreach($showImagesKeywords as $keyword){
							if(str_contains($image->profile, $keyword)) {
								$asset = $this->createOrUpdateImage( $showAttributes->title, $image );
								
								if( $asset && isset( $asset->id ) ) {
									$showImageArray[$keyword] = $asset->id;
								}
							}
						}
						
					}
				}

        // Set default field Values
        $defaultFields = [];

        // Set field values based on API Column Fields on settings
        $apiColumnFields = SettingsHelper::get( 'showApiColumnFields' );
				
        foreach( $apiColumnFields as $apiColumnField ) {
            
            $apiField = $apiColumnField[0];
						
            switch( $apiField ) {
	              case 'episode_availability':
									// There is probably a much cleaner / more straightforward way of doing this
									// we need to get the latest episode of the latest season so we have to jump through a handful of URLs to get there
									$seasonsUrl = $showEntry->links->seasons;
		              $seasonData = $this->fetchShowEntry($seasonsUrl);
									if(!$seasonData){
										break;
									}
									$episodesUrl = $seasonData[0]->links->episodes;
									$episodesData = $this->fetchShowEntry($episodesUrl);
									if(!$episodesData){
										break;
									}
									$episodeAssetsUrl = $episodesData[0]->links->assets;
		              $episodeAssets = $this->fetchShowEntry($episodeAssetsUrl);
									
									if(!$episodeAssets) {
										break;
									}
									$episodeAsset = $episodeAssets[0] ?? null;
									if($episodeAsset) {
										$availability = new \DateTime($episodeAsset->attributes->availabilities->public->end) ?? null;
										$fieldHandle = SynchronizeHelper::getApiField($apiField, 'showApiColumnFields');
										$defaultFields[ $fieldHandle ] = $availability;
									}
                case 'show_images':

                    $imagesHandle = SynchronizeHelper::getShowImagesField();
                    $fieldRule    = SynchronizeHelper::getApiFieldRule( $apiField, 'showApiColumnFields' );

                    if( isset( $showAttributes->images ) && is_array( $showAttributes->images ) ) {
                        $assets = [];
                        foreach( $showAttributes->images as $image ) {

                            if( $fieldRule ) {
                                preg_match( '/'. $fieldRule .'/', $image->profile, $matches );

                                if( count( $matches ) ) {
                                    $asset = $this->createOrUpdateImage( $showAttributes->title, $image );

                                    if( $asset && isset( $asset->id ) ) {
                                        $assets[] = $asset->id;
                                    }
                                }
                                continue;
                            }

                            $asset = $this->createOrUpdateImage( $showAttributes->title, $image );

                            if( $asset && isset( $asset->id ) ) {
                                $assets[] = $asset->id;
                            }
                        }

                        if( $assets ) {
                            $defaultFields[ $imagesHandle ] = $assets;
                        }
                    }

                break;
										
	              case 'show_mezzanine':
		              $fieldHandle = SynchronizeHelper::getApiField('show_mezzanine', 'showApiColumnFields');
		              
		              if( isset($showImageArray['mezzanine']) ) {
			              $defaultFields[$fieldHandle] = [$showImageArray['mezzanine']];
		              }
								break;
								
	              case 'show_poster':
		              $fieldHandle = SynchronizeHelper::getApiField('show_poster', 'showApiColumnFields');
		              if( isset($showImageArray['poster']) ) {
			              $defaultFields[$fieldHandle] = [$showImageArray['poster']];
		              }
                break;
	
	              case 'show_white_logo':
			            $fieldHandle = SynchronizeHelper::getApiField('show_white_logo', 'showApiColumnFields');
		             
			            if( isset($showImageArray['white']) ) {
				            $defaultFields[$fieldHandle] = [$showImageArray['white']];
			            }
		            break;
									
	              case 'show_black_logo':
			            $fieldHandle = SynchronizeHelper::getApiField('show_black_logo', 'showApiColumnFields');
			            if( isset($showImageArray['black']) ) {
				            $defaultFields[$fieldHandle] = [$showImageArray['black']];
			            }
		            break;
	
	              case 'show_color_logo':
			            $fieldHandle = SynchronizeHelper::getApiField('show_color_logo', 'showApiColumnFields');
			
			            if( isset($showImageArray['color']) ) {
				            $defaultFields[$fieldHandle] = [$showImageArray['color']];
			            }
		            break;
								
								case 'show_address':
                    if( isset( $showAttributes->slug ) ) {
                        $defaultFields[ SynchronizeHelper::getApiField( $apiField, 'showApiColumnFields' ) ] = 'https://pbs.org/show/' . $showAttributes->slug;
                    }
                break;
                case 'show_last_synced':
                    $defaultFields[ SynchronizeHelper::getShowLastSyncedField() ] = new \DateTime( 'now' );
                break;
                case 'show_media_manager_id':
                    $defaultFields[ SynchronizeHelper::getShowMediaManagerIdField() ] = $showEntry->data->id;
                break;
                case 'description_long':
                    // Only if new entry add description
                    if( !$existingEntry ) {
                        $defaultFields[ SynchronizeHelper::getApiField( $apiField, 'showApiColumnFields' ) ] = $showAttributes->description_long;
                    }
                break;
                case 'description_short':
                    // Only if new entry add description
                    if( !$existingEntry ) {
                        $defaultFields[ SynchronizeHelper::getApiField( $apiField, 'showApiColumnFields' ) ] = $showAttributes->description_short;
                    }
                break;
	
	              case 'links':
		
			            if(isset($showAttributes->links) && is_array($showAttributes->links)) {
				
				            $createTable = [];
				            $count = 0;
				
				            foreach($showAttributes->links as $link) {
					            $createTable[$count]['col1'] = $link->value;
					            $createTable[$count]['col2'] = $link->profile;
					            $createTable[$count]['col3'] = new \DateTime( $link->updated_at );
					            $count++;
				            }
				
				            $defaultFields[SynchronizeHelper::getApiField($apiField, 'showApiColumnFields')] = $createTable;
				
			            }
		
		            break;
	
	            case 'platform_availability':
		
		            if(isset($showAttributes->platforms) && is_array($showAttributes->platforms)) {
			
			            $createTable = [];
			            $count = 0;
			
			            foreach($showAttributes->platforms as $platform) {
				            $createTable[$count]['col1'] = $platform->name;
				            $createTable[$count]['col2'] = $platform->slug;
				            $createTable[$count]['col3'] = new \DateTime( $platform->updated_at );
				            $count++;
			            }
			
			            $defaultFields[SynchronizeHelper::getApiField($apiField, 'showApiColumnFields')] = $createTable;
		            }
		
		            break;
								
	              case 'slug':
									
									if(isset($showAttributes['slug'])){
										$defaultFields[SynchronizeHelper::getApiField($apiField, 'showApiColumnFields')] = $showAttributes['slug'];
									}
								
                default:
                    $defaultFields[ SynchronizeHelper::getApiField( $apiField, 'showApiColumnFields' ) ] = $showAttributes->{ $apiField };
                break;
            }
        }

        // Set field values and properties
        $entry->setFieldValues( $defaultFields );
        $entry->enabled = true;

        Craft::$app->getElements()->saveElement( $entry );
        $this->setProgress( $queue, 1 );
    }

    // Protected Methods
    // =========================================================================

    protected function defaultDescription(): string
    {
        return Craft::t( 'mediamanager', 'Syncing show entry for ' . $this->title );
    }

    // Private Methods
    // =========================================================================
    
    private function log( $message )
    {
        if( $this->logProcess ) {
            $log = date( 'Y-m-d H:i:s' ) .' '. $message . "\n";
            FileHelper::writeToFile( Craft::getAlias( $this->logFile ), $log, [ 'append' => true ] );
        }
    }
    
    private function generateAPIUrl( $apiKey )
    {
        return $this->apiBaseUrl . 'shows/'. $apiKey . '/?platform-slug=bento&platform-slug=partnerplayer';
    }

    private function fetchShowEntry($url, $attribute = 'data')
    {
        $client   = Craft::createGuzzleClient();
        $response = $client->get( $url, $this->auth );
        $response = json_decode( $response->getBody() );
				
				if($attribute){
					return $response->{$attribute};
				}
				
        return $response;
    }
    
    private function findExistingShowEntry( $mediaManagerId )
    {
        // Find existing media
        $entry = Entry::find()
                    ->{ SynchronizeHelper::getShowMediaManagerIdField() }( $mediaManagerId )
                    ->sectionId( $this->sectionId )
                    ->status( null )
                    ->one();

        return ( $entry ) ? $entry : false;
    }

    private function chooseOrCreateShowEntry( $title, $entry )
    {

        if( !$entry ) {

            $apiUserID = $this->authorId;

            if( $this->authorUsername ) {
                $user = Craft::$app->users->getUserByUsernameOrEmail( $this->authorUsername );
                
                if( $user ) {
                    $apiUserID = $user->id;
                }
            }

            $entry            = new Entry();
            $entry->sectionId = $this->sectionId;
            $entry->typeId    = $this->typeId;
            $entry->authorId  = $apiUserID;
            $entry->title     = $title;
        }

        return $entry;
    }

    private function getMediaFolder()
    {
        $assets = Craft::$app->getAssets();

        return $assets->findFolder( [ 'id' => $this->mediaFolderId ] );
    }

    private function copyImageToServer( $url )
    {
        $image     = file_get_contents( $url );
        $extension = pathinfo( $url )[ 'extension' ];
        $localPath = AssetHelper::tempFilePath( $extension );

        file_put_contents( $localPath, $image );

        return $localPath;
    }

    private function createOrUpdateImage( $entryTitle, $imageInfo )
    {
        $imageUrl  = $imageInfo->image;
        $extension = pathinfo( $imageUrl )[ 'extension' ];
        $slug      = ElementHelper::normalizeSlug( $entryTitle );
        $filename  = $slug . '-' . md5( ElementHelper::normalizeSlug( $imageUrl ) ) . '.' . $extension;
        $asset     = Asset::findOne( [ 'filename' => $filename ] );

        if( $asset ) {
            return $asset;
        }

        return $this->createImageAsset( $imageUrl, $filename );
    }

    private function createImageAsset( $imageUrl, $filename )
    {
        $folder    = $this->getMediaFolder();
        $localPath = $this->copyImageToServer( $imageUrl );

        $asset               = new Asset();
        $asset->tempFilePath = $localPath;
        $asset->filename     = $filename;
        $asset->newFolderId  = $folder->id;
        $asset->volumeId     = $folder->volumeId;
        $asset->avoidFilenameConflicts = true;

        $asset->setScenario( Asset::SCENARIO_CREATE );
        Craft::$app->getElements()->saveElement( $asset );

        return $asset;
    }
}
