<?php
/**
 * File containing the ymcDynamicIniSettingType class
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of version 2.0  of the GNU General
 * Public License as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of version 2.0 of the GNU General
 * Public License along with this program; if not, write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 * @category   Datatypes
 * @package    ymcExtensionLoader
 * @subpackage ymcDynamicIniSetting
 * @copyright  2010 by YMC AG. All rights reserved.
 * @license    GNU General Public License v2.0
 * @author     ymc-dabe
 * @filesource
 */


/**
 * ymcDynamicIniSettingType allows to override ini settings in content objects
 * Settings are stored in a persistent object in database and synced to the current
 * cache directory. ymcExtensionLoader has a custom ini loading order to load these
 * cache files in the right order.
 *
 *
 * @package    ymcExtensionLoader
 * @subpackage ymcDynamicIniSetting
 * @version    ---ymcVersionAutoGen---
 * @author     ymc-dabe
 */

class ymcDynamicIniSettingType extends eZDataType
{
    const DATA_TYPE_STRING = 'ymcdynamicinisetting';

    const CLASS_TYPE = '_ymcdynamicinisetting_type_';
    const CLASS_FILE = '_ymcdynamicinisetting_file_';
    const CLASS_SECTION = '_ymcdynamicinisetting_section_';
    const CLASS_PARAMETER = '_ymcdynamicinisetting_parameter_';

    const CLASS_FILE_FIELD = 'data_text1';
    const CLASS_SECTION_FIELD = 'data_text2';
    const CLASS_PARAMETER_FIELD = 'data_text3';
    const CLASS_TYPE_FIELD = 'data_int1';

    public function __construct()
    {
        $this->eZDataType( self::DATA_TYPE_STRING, ezi18n( 'kernel/classes/datatypes', 'Dynamic INI Setting', 'Datatype name' ).' [ymc]',
                                                         array( 'translation_allowed' => false,
                                                                'serialize_supported' => false ) );
    }

    function validateClassAttributeHTTPInput( $http, $base, $classAttribute )
    {
        $typeParam = $base . self::CLASS_TYPE . $classAttribute->attribute( 'id' );
        $fileParam = $base . self::CLASS_FILE . $classAttribute->attribute( 'id' );
        $sectionParam = $base . self::CLASS_SECTION . $classAttribute->attribute( 'id' );
        $parameterParam = $base . self::CLASS_PARAMETER . $classAttribute->attribute( 'id' );

        if ( $http->hasPostVariable( $fileParam ) and
             $http->hasPostVariable( $sectionParam ) and
             $http->hasPostVariable( $parameterParam ) and
             $http->hasPostVariable( $typeParam ) and
             (int)$http->postVariable( $typeParam ) > 0 and
             (int)$http->postVariable( $typeParam ) <= 6 )
        {
            $liveClassAttribute = eZContentClassAttribute::fetch( $classAttribute->attribute( 'id' ) );
            if ( is_object($liveClassAttribute) )
            {
                $new_type = (int)$http->postVariable( $typeParam );
                $old_type = (int)$liveClassAttribute->attribute( self::CLASS_TYPE_FIELD );
                if ( $new_type !== $old_type and
                     ( $new_type === ymcDynamicIniSetting::TYPE_ARRAY or
                       $old_type === ymcDynamicIniSetting::TYPE_ARRAY ) )
                {
                    eZDebug::writeNotice( 'It is not possible to change an existing type from/to the array-type', 'ymcDynamicIniSettingType::validateClassAttributeHTTPInput' );
                    return eZInputValidator::STATE_INVALID;
                }
            }
            return eZInputValidator::STATE_ACCEPTED;
        }

        eZDebug::writeNotice( 'Could not validate parameters: ' . "\n" .
                              $fileParam.':'.$http->postVariable( $fileParam )."\n".
                              $sectionParam.':'.$http->postVariable( $sectionParam )."\n".
                              $parameterParam.':'.$http->postVariable( $parameterParam )."\n".
                              $typeParam.':'. $http->postVariable( $typeParam ),
                              'ymcDynamicIniSettingType::validateClassAttributeHTTPInput' );
        return eZInputValidator::STATE_INVALID;
    }
    /**
     * Fetches the HTTP input for the contentClassAttribute
     *
     * @param  object  $http           An eZHTTPTool object
     * @param  string  $base           Base name of the HTTP variable
     * @param  object  $classAttribute An eZContentClassAttribute object
     * @return boolean TRUE if successfully fetched input, otherwise FALSE
     * @see    eZDataType
     * @access public
     * @author ymc-dabe
     */
    function fetchClassAttributeHTTPInput( $http, $base, $classAttribute )
    {
        $fileParam = $base . self::CLASS_FILE . $classAttribute->attribute( 'id' );
        $sectionParam = $base . self::CLASS_SECTION . $classAttribute->attribute( 'id' );
        $paramParam = $base . self::CLASS_PARAMETER . $classAttribute->attribute( 'id' );
        $typeParam = $base . self::CLASS_TYPE . $classAttribute->attribute( 'id' );

        if ( $http->hasPostVariable( $fileParam ) and
             $http->hasPostVariable( $sectionParam ) and
             $http->hasPostVariable( $paramParam ) and
             $http->hasPostVariable( $typeParam ) )
        {
            $file = $http->postVariable( $fileParam );
            $section = $http->postVariable( $sectionParam );
            $parameter = $http->postVariable( $paramParam );
            $type = (int)$http->postVariable( $typeParam );

            $classAttribute->setAttribute( self::CLASS_FILE_FIELD, $file );
            $classAttribute->setAttribute( self::CLASS_SECTION_FIELD, $section );
            $classAttribute->setAttribute( self::CLASS_PARAMETER_FIELD, $parameter );
            $classAttribute->setAttribute( self::CLASS_TYPE_FIELD, $type );

            return true;
        }
        return false;
    }
    /**
     * @param  object  $classAttribute An eZContentClassAttribute object
     * @param  mixed $version
     * @return boolean
     * @see    eZDataType
     * @access public
     * @author ymc-dabe
     */

    function preStoreClassAttribute( $classAttribute, $version )
    {
        if ( $version == eZContentClass::VERSION_STATUS_DEFINED )
        {
            //Modify entries in the ymcdynamicinisetting-(meta-)table
            eZPersistentObject::updateObjectList( array( 'definition' => ymcDynamicIniSetting::definition(),
                                                         'update_fields' => array( 'ini_type' => $classAttribute->attribute( self::CLASS_TYPE_FIELD ),
                                                                                   'ini_file' => $classAttribute->attribute( self::CLASS_FILE_FIELD ),
                                                                                   'ini_section' => $classAttribute->attribute( self::CLASS_SECTION_FIELD ),
                                                                                   'ini_parameter' => $classAttribute->attribute( self::CLASS_PARAMETER_FIELD ) ),
                                                         'conditions' => array( 'contentclassattribute_id' => $classAttribute->attribute('id') ) ) );
        }
        return true;
    }

    /**
     * Validates the input for the contentObjectAttribute
     *
     * @param  object  $http                   An eZHTTPTool object
     * @param  string  $base                   Base name of the HTTP variable
     * @param  object  $contentObjectAttribute An eZContentObjectAttribute object
     * @return integer A validation state as defined in eZInputValidator
     * @see    eZDataType     
     * @access public
     * @author ymc-dabe
     */
    function validateObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        if ( $http->hasPostVariable( $base . '_ymc_dynamic_ini_setting_' . $contentObjectAttribute->attribute( 'id' ) ) )
        {
            $data = $http->postVariable( $base . '_ymc_dynamic_ini_setting_' . $contentObjectAttribute->attribute( 'id' ) );
            if ( !is_array($data) or count($data) <= 0 )
            {
                $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
                                                                     'Input invalid.' ) );
                return eZInputValidator::STATE_INVALID;
            }

            $all_data_empty = true;
            foreach ( $data as $item )
            {
                if ( trim($item) != '' )
                {
                    $all_data_empty = false;
                }
            }
            if ( $all_data_empty and $contentObjectAttribute->validateIsRequired() )
            {
                $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
                                                                     'Input required.' ) );
                return eZInputValidator::STATE_INVALID;
            }
        }
        return eZInputValidator::STATE_ACCEPTED;
    }

    /**
     * Fetches the HTTP input for the contentObjectAttribute
     *
     * @param  object  $http                   An eZHTTPTool object
     * @param  string  $base                   Base name of the HTTP variable
     * @param  object  $contentObjectAttribute An eZContentObjectAttribute object
     * @return boolean TRUE if successfully fetched input, otherwise FALSE
     * @see    eZDataType
     * @access public
     * @author ymc-pabu
     */

    function fetchObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        if ( $http->hasPostVariable( $base . '_ymc_dynamic_ini_setting_' . $contentObjectAttribute->attribute( 'id' ) ) )
        {
            $contentClassAttribute = $contentObjectAttribute->attribute( 'contentclass_attribute' );
            $type = (int)$contentClassAttribute->attribute( self::CLASS_TYPE_FIELD );
            $trimmed_data_array = array();
            $data = $http->postVariable( $base . '_ymc_dynamic_ini_setting_' . $contentObjectAttribute->attribute( 'id' ) );

            if ( $type === ymcDynamicIniSetting::TYPE_ARRAY )
            {
                if ( $http->hasPostVariable( $base . '_ymc_dynamic_ini_setting_key_' . $contentObjectAttribute->attribute( 'id' ) ) )
                {
                    $key_data = $http->postVariable( $base . '_ymc_dynamic_ini_setting_key_' . $contentObjectAttribute->attribute( 'id' ) );
                }
                else
                {
                    $key_data = array();
                }
                foreach ( $data as $array_key => $value )
                {
                    if ( isset($key_data[$array_key]) )
                    {
                        $key = $key_data[$array_key];
                    }
                    else
                    {
                        $key = $array_key;
                    }
                    if ( $key != '' and
                         !isset($trimmed_data_array[$key]) )
                    {
                        $trimmed_data_array[$key] = trim( $value );
                    }
                }
            }
            else
            {
                $trimmed_data_array[''] = trim( $data[0] );
            }
            $contentObjectAttribute->setAttribute( 'data_text', json_encode( $trimmed_data_array ) );
            if ( $http->hasPostVariable( $base . '_ymc_dynamic_ini_setting_make_empty_array_' . $contentObjectAttribute->attribute( 'id' ) ) )
            {
                $isChecked = $http->postVariable( $base . '_ymc_dynamic_ini_setting_make_empty_array_' . $contentObjectAttribute->attribute( 'id' ) );
                if ( isset( $isChecked ) )
                {
                    $isChecked = 1;
                }
                $contentObjectAttribute->setAttribute( 'data_int', $isChecked );
            }
            else
            {
                $contentObjectAttribute->setAttribute( 'data_int', 0 );
            }
            return true;
        }
        return false;
    }

    /**
     * @param  object  $contentObjectAttribute An eZContentObjectAttribute object
     * @param  eZContentObject $contentObject
     * @param  array $publishedNodes
     * @return void
     * @see    eZDataType
     * @access public
     * @author ymc-dabe
     */
    function onPublish( $contentObjectAttribute, $contentObject, $publishedNodes )
    {
        $contentClassAttribute = $contentObjectAttribute->attribute( 'contentclass_attribute' );
        $type = (int)$contentClassAttribute->attribute( self::CLASS_TYPE_FIELD );
        $filename = $contentClassAttribute->attribute( self::CLASS_FILE_FIELD );
        $section = $contentClassAttribute->attribute( self::CLASS_SECTION_FIELD );
        $parameter = $contentClassAttribute->attribute( self::CLASS_PARAMETER_FIELD );
        $makeEmptyArray = $contentObjectAttribute->attribute( 'data_int' );

        $json_data = $contentObjectAttribute->attribute('data_text');
        $data = json_decode($json_data, true);
        if ( is_array($data) )
        {
            if ( $contentObjectAttribute->attribute( 'data_int' ) == 1 and
                 $type === ymcDynamicIniSetting::TYPE_ARRAY )
            {
                $dynamicIniSetting = ymcDynamicIniSetting::create( $contentClassAttribute->attribute('id'),
                                                                   $contentObjectAttribute->attribute('id'),
                                                                   $contentObjectAttribute->attribute('version'),
                                                                   $contentObjectAttribute->attribute('contentobject_id'),
                                                                   $type,
                                                                   $filename,
                                                                   $section,
                                                                   $parameter,
                                                                   '',
                                                                   '' );
                $dynamicIniSetting->store();
            }
            foreach ( $data as $key => $value )
            {
                $dynamicIniSetting = ymcDynamicIniSetting::create( $contentClassAttribute->attribute('id'),
                                                                   $contentObjectAttribute->attribute('id'),
                                                                   $contentObjectAttribute->attribute('version'),
                                                                   $contentObjectAttribute->attribute('contentobject_id'),
                                                                   $type,
                                                                   $filename,
                                                                   $section,
                                                                   $parameter,
                                                                   $key,
                                                                   $value );
                $dynamicIniSetting->store();
            }
        }
        ymcDynamicIniSetting::dropUnneededByContentObjectAttributeIdAndVersion( $contentObjectAttribute->attribute('id'), $contentObjectAttribute->attribute('version') );

        $dynamicIniSettings = ymcDynamicIniSetting::fetchListByObjectAttributeID( $contentObjectAttribute->attribute('id') );
        foreach ( $dynamicIniSettings as $dynamicIniSetting )
        {
            $ymcExtensionLoader = ymcExtensionLoader::getInstance();
            $dynamicIniCacheDirToDrop = $ymcExtensionLoader->attribute('global_cache_directory').'/openvolano/dynamic_ini/'.$dynamicIniSetting->attribute('contentobject_id');
            eZDebug::writeNotice( "Dropping cached dynamic-INI-dir: $dynamicIniCacheDirToDrop", 'ymcExtensionLoader::prependDynamicINI()' );
            $fileHandler = eZClusterFileHandler::instance();
            $fileHandler->fileDelete( $dynamicIniCacheDirToDrop );
        }
    }

    /**
     * @param  object $objectAttribute An eZContentObjectAttribute object
     * @param  mixed  $version     
     * @return boolean
     * @return void
     * @see    eZDataType     
     * @access public
     * @author ymc-dabe
     */
    function deleteStoredObjectAttribute( $objectAttribute, $version = null )
    {
        if ( $version === null )
        {
            ymcDynamicIniSetting::dropByContentObjectAttributeID( $objectAttribute->attribute('id') );
        }
    }

    /**
     * Returns the content data for the given contentObjectAttribute
     *
     * @param  object $contentObjectAttribute An eZContentObjectAttribute object
     * @return array  
     * @see    eZDataType
     * @access public
     * @author ymc-pabu
     */
    function objectAttributeContent( $contentObjectAttribute )
    {
        $contentClassAttribute = $contentObjectAttribute->attribute( 'contentclass_attribute' );

        $versioned_values = json_decode( $contentObjectAttribute->attribute( 'data_text' ), true );
        if ( !is_array( $versioned_values ) )
        {
            $versioned_values = array();
        }

        $data = array( 'live_data' => ymcDynamicIniSetting::fetchListByObjectAttributeID( $contentObjectAttribute->attribute('id') ),
                       'versioned_values' => $versioned_values,
                       'info' => array( 'type' => $contentClassAttribute->attribute( self::CLASS_TYPE_FIELD ),
                                        'filename' => $contentClassAttribute->attribute( self::CLASS_FILE_FIELD ),
                                        'section' => $contentClassAttribute->attribute( self::CLASS_SECTION_FIELD ),
                                        'parameter' => $contentClassAttribute->attribute( self::CLASS_PARAMETER_FIELD ) ) );
        //ymc_pr($data);
        return $data;
    }

    /**
     * @param  object  $http                   An eZHTTPTool object
     * @param  string  $action
     * @param  object  $contentObjectAttribute An eZContentObjectAttribute object
     * @param  mixed $parameters
     * @return void
     * @access public
     * @author ymc-dabe
     */
    function customObjectAttributeHTTPAction( $http, $action, $contentObjectAttribute, $parameters )
    {
        switch ( $action )
        {
            case "new_ymc_dynamic_ini_setting":
            {
                $json_data = $contentObjectAttribute->attribute('data_text');
                $data = json_decode($json_data, true);
                if ( !is_array($data) )
                {
                    $data = array( 0 => '' );
                }
                else
                {
                    $data[] = '';
                }
                $contentObjectAttribute->setAttribute('data_text', json_encode($data));
                $contentObjectAttribute->store();
            }
            break;

            case "ymc_dynamic_ini_setting_remove_selected":
            {
                $json_data = $contentObjectAttribute->attribute('data_text');
                $data = json_decode($json_data, true);
                if ( is_array($data) )
                {
                    $postvarname = "ContentObjectAttribute" . "_ymc_dynamic_ini_setting_remove_" . $contentObjectAttribute->attribute( "id" );
                    $array_remove = $http->postVariable( $postvarname );
                    $i = 0;
                    foreach ( array_keys($data) as $remove_key )
                    {
                        if ( in_array( $i, $array_remove ) )
                        {
                            unset($data[$remove_key]);
                        }
                        $i++;
                    }
                    $contentObjectAttribute->setAttribute('data_text', json_encode($data));
                    $contentObjectAttribute->store();
                }
            }
            break;

            default:
            {
                eZDebug::writeError( "Unknown custom HTTP action: " . $action, "ymcDynamicIniSettingType" );
            }
            break;
        }
    }
    
    /**
     * Returns a string to be used as the title for the contentObject
     *
     * @param  object $contentObjectAttribute An eZContentObjectAttribute object
     * @param  null   $name Currently not in used - just ignore it!
     * @return string A title that can be used for the contentObject
     * @see    eZDataType
     * @access public
     * @author ymc-dabe
     */

    function title( $contentObjectAttribute, $name = null )
    {
        return 'YMC Dynamic INI Setting';
    }

    /**
     * Returns whether the contentObjectAttribute contains data or not
     *
     * @param  object  $contentObjectAttribute An eZContentObjectAttribute object
     * @return boolean TRUE if the attribute has content, otherwise FALSE
     * @see    eZDataType
     * @access public
     * @author ymc-dabe
     */
    function hasObjectAttributeContent( $contentObjectAttribute )
    {
        return (boolean)($contentObjectAttribute->attribute( 'data_text' ) != '' );
    }
}

eZDataType::register( ymcDynamicIniSettingType::DATA_TYPE_STRING, 'ymcDynamicIniSettingType' );

?>
