<?php
/**
 * Media Manager
 *
 * @package       PaperTiger:MediaManager
 * @author        Paper Tiger
 * @copyright     Copyright (c) 2020 Paper Tiger
 * @link          https://www.papertiger.com/
 */

namespace papertiger\mediamanager\validators;

use Craft;
use craft\helpers\App;
use yii\validators\Validator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class BasicAuthValidator extends Validator
{
    // Public Methods
    // =========================================================================
    
    public function validateAttribute( $model, $attribute )
    {
        $apiBaseUrl      = $model->apiBaseUrl; 
        $pbsApiUsername  = '';
        $pbsApiPassword  = '';

        if( method_exists( 'Craft', 'parseEnv' ) ) {

            $pbsApiUsername = Craft::parseEnv( '$PBS_API_BASIC_AUTH_USERNAME' );
            $pbsApiPassword = Craft::parseEnv( '$PBS_API_BASIC_AUTH_PASSWORD' );
        }

        if( method_exists( 'App', 'parseEnv' ) ) {

            $pbsApiUsername = App::parseEnv( '$PBS_API_BASIC_AUTH_USERNAME' );
            $pbsApiPassword = App::parseEnv( '$PBS_API_BASIC_AUTH_PASSWORD' );
        }

        try {

            $client   = new Client();
            $response = $client->request( 'HEAD', $apiBaseUrl, [
                'auth' => [ $pbsApiUsername, $pbsApiPassword ]
            ]);

        } catch( ClientException $e ) {
            $this->addError( $model, $attribute, 'Failed to authenticate PBS API. Make sure base url is correct and you have set a correct PBS_API_BASIC_AUTH_USERNAME and PBS_API_BASIC_AUTH_PASSWORD variables in .env file.' );
        }

        return;
    }
}
