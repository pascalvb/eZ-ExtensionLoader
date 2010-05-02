<?php
/**
 * File containing the ymcDynamicIniSetting class
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
 * @category   Persistent
 * @package    ymcExtensionLoader
 * @subpackage ymcDynamicIniSetting
 * @copyright  2010 by YMC AG. All rights reserved.
 * @license    GNU General Public License v2.0
 * @author     ymc-dabe
 * @filesource
 */


/**
 * Stores dynamic ini settings in the database
 *
 *
 * @package    ymcExtensionLoader
 * @subpackage ymcDynamicIniSetting
 * @version    ---ymcVersionAutoGen---
 * @author     ymc-pabu
 */

class ymcDynamicIniSetting extends eZPersistentObject
{
    const TYPE_UNDEFINED = 0;
    const TYPE_TEXT = 1;
    const TYPE_ONOFF = 2;
    const TYPE_BOOLEAN = 3;
    const TYPE_INTEGER = 4;
    const TYPE_FLOAT = 5;
    const TYPE_ARRAY = 6;

    /**
     * The eZPersistentObject definition.
     *
     * @return array  An eZPersistentObject definition
     * @access public
     * @static
     * @author ymc-dabe
     */
    public static function definition()
    {
        return array( 'fields' => array( 'contentclassattribute_id' => array( 'name' => 'ContentClassAttributeID',
                                                                              'datatype' => 'integer',
                                                                              'default' => 0,
                                                                              'required' => true ),
                                         'contentobjectattribute_id' => array( 'name' => 'ContentObjectAttributeID',
                                                                               'datatype' => 'integer',
                                                                               'default' => 0,
                                                                               'required' => true ),
                                         'contentobjectattribute_version' => array( 'name' => 'ContentObjectAttributeVersion',
                                                                               'datatype' => 'integer',
                                                                               'default' => 0,
                                                                               'required' => true ),
                                         'contentobject_id' => array( 'name' => 'ContentObjectID',
                                                                      'datatype' => 'integer',
                                                                      'default' => 0,
                                                                      'required' => true ),
                                         'ini_type' => array( 'name' => 'IniType',
                                                          'datatype' => 'integer',
                                                          'default' => self::TYPE_UNDEFINED,
                                                          'required' => true ),
                                         'ini_file' => array( 'name' => 'IniFile',
                                                          'datatype' => 'string',
                                                          'default' => '',
                                                          'required' => true ),
                                         'ini_section' => array( 'name' => 'IniSection',
                                                             'datatype' => 'string',
                                                             'default' => '',
                                                             'required' => true ),
                                         'ini_parameter' => array( 'name' => 'IniParameter',
                                                               'datatype' => 'string',
                                                               'default' => '',
                                                               'required' => true ),
                                         'ini_key' => array( 'name' => 'IniKey',
                                                         'datatype' => 'string',
                                                         'default' => '',
                                                         'required' => true ),
                                         'ini_value' => array( 'name' => 'IniValue',
                                                           'datatype' => 'string',
                                                           'default' => '',
                                                           'required' => true ) ),
                      'keys' => array( 'contentobjectattribute_id',
                                       'contentobjectattribute_version',
                                       'ini_file',
                                       'ini_section',
                                       'ini_parameter',
                                       'ini_key' ),
                      'function_attributes' => array( 'contentclass_attribute' => 'contentClassAttribute',
                                                      'contentobject_attribute' => 'contentObjectAttribute',
                                                      'contentobject' => 'contentObject',
                                                      'ini_value_dynamic' => 'parseIniDynamicValue' ),
                      'sort' => array( 'contentobject_id' => 'asc',
                                       'contentobjectattribute_id' => 'asc' ),
                      'class_name' => 'ymcDynamicIniSetting',
                      'name' => 'ymcdynamicinisetting' );
    }

    /**
     * Creates a new ymcDynamicIniSetting instance
     *
     * @param  integer $contentclassattribute_id       A ContentClassAttributeID
     * @param  integer $contentobjectattribute_id      A ContentObjectAttributeID
     * @param  integer $contentobjectattribute_version A ContentObjectAttributeVersion
     * @param  integer $contentobject_id               A ContentObjectID
     * @param  integer $type                           The type, as defined by one of the self:TYPE_* constants
     * @param  string  $file                           The name of the ini-file, without any suffix
     * @param  string  $section                        The name of the ini-section, without brackets
     * @param  string  $parameter                      The name of the parameter in the section
     * @param  string  $value                          The value of the parameter
     * @param  string  $key                            The key of the parameter
     * @return object  A new ymcDynamicIniSetting instance
     * @access public
     * @static
     * @author ymc-dabe
     */
    public static function create( $contentclassattribute_id, $contentobjectattribute_id, $contentobjectattribute_version, $contentobject_id, $type, $file, $section, $parameter, $key = '', $value = '' )
    {
        $timestamp = time();
        $row = array( 'contentclassattribute_id' => $contentclassattribute_id,
                      'contentobjectattribute_id' => $contentobjectattribute_id,
                      'contentobjectattribute_version' => $contentobjectattribute_version,
                      'contentobject_id' => $contentobject_id,
                      'ini_type' => $type,
                      'ini_file' => $file,
                      'ini_section' => $section,
                      'ini_parameter' => $parameter,
                      'ini_value' => $value,
                      'ini_key' => $key );
        return new self( $row );
    }

    /**
     * Fetches a ymcDynamicIniSetting instance by its primary key
     *
     * @param  integer $contentobjectattribute_id      A ContentObjectAttributeID
     * @param  integer $contentobjectattribute_version A ContentObjectAttributeVersion
     * @param  string  $file                          The name of the ini-file, without any suffix
     * @param  string  $section                       The name of the ini-section, without brackets
     * @param  string  $parameter                     The name of the parameter in the section
     * @param  string  $key                           The key of the parameter
     * @param  boolean $asObject                      Whether to return an OBJECT or ARRAY data
     * @return object  A ymcDynamicIniSetting instance
     * @access public
     * @static
     * @author ymc-dabe
     */
    public static function fetchByPRIMARY( $contentobjectattribute_id,
                                           $contentobjectattribute_version,
                                           $file,
                                           $section,
                                           $parameter,
                                           $key = '',
                                           $asObject = true )
    {
        return eZPersistentObject::fetchObject( self::definition(),
                                                null,
                                                array( 'contentobjectattribute_id' => $contentobjectattribute_id,
                                                       'contentobjectattribute_version' => $contentobjectattribute_version,
                                                       'ini_file' => $file,
                                                       'ini_section' => $section,
                                                       'ini_parameter' => $parameter,
                                                       'ini_key' => $key ),
                                                $asObject );
    }

    /**
     * Fetches a ymcDynamicIniSetting instance by its contentclassattribute_id
     *
     * @param  integer $contentclassattribute_id A ContentClassAttributeID
     * @param  boolean $asObject                 Whether to return an OBJECT or ARRAY data
     * @return object  An ARRAY of ymcDynamicIniSetting instance or an appropriate ARRAY of ARRAYs
     * @access public
     * @static
     * @author ymc-dabe
     */
    public static function fetchListByClassAttributeID( $contentclassattribute_id, $asObject = true )
    {
        return eZPersistentObject::fetchObjectList( self::definition(),
                                                    null,
                                                    array( 'contentclassattribute_id' => $contentclassattribute_id ),
                                                    null,
                                                    null,
                                                    $asObject );
    }

    /**
     * Fetches a ymcDynamicIniSetting instance by its contentobjectattribute_id
     *
     * @param  integer $contentobjectattribute_id A ContentObjectAttributeID
     * @param  boolean $asObject                  Whether to return an OBJECT or ARRAY data
     * @return object  An ARRAY of ymcDynamicIniSetting instance or an appropriate ARRAY of ARRAYs
     * @access public
     * @static
     * @author ymc-dabe
     */
    public static function fetchListByObjectAttributeID( $contentobjectattribute_id, $asObject = true )
    {
        return eZPersistentObject::fetchObjectList( self::definition(),
                                                    null,
                                                    array( 'contentobjectattribute_id' => $contentobjectattribute_id ),
                                                    null,
                                                    null,
                                                    $asObject );
    }

    /**
     * Fetches a list of entries by conditions.
     *
     * @param array      $conds    Conditions to fetch with. Defaults to an empty array.
     * @param NULL|array $sorts    <code>array( 'db_field' => 'asc' )</code>.
     * @param NULL|array $limit    <code>array( 'offset'=> 100, 'limit' => 10 )</code>.
     * @param boolean    $asObject Defaults to true, which means objects should be returned.
     *
     * @return array An array with instances of ymcDynamicIniSetting or an multi-dimensional array
     * @author ymc-dabe
     */
    public static function fetchList( array $conds = array(),
                                      array $sorts = NULL,
                                      array $limit = NULL,
                                      $asObject = true )
    {
        return ezPersistentObject::fetchObjectList( self::definition(),
                                                    null,
                                                    $conds,
                                                    $sorts, $limit,
                                                    $asObject );
    }

    /**
     * Fetches a the count of a list of entries by conditions.
     *
     * @param array $conditions Conditions to fetch with. Defaults to an empty array.
     *
     * @return integer The count of items in the list
     * @author ymc-dabe
     */
    public static function fetchListCount( array $conditions = array() )
    {
        $rows = ezPersistentObject::fetchObjectList( self::definition(),
                                                     array(),
                                                     $conditions,
                                                     null, null,
                                                     false,
                                                     false,
                                                     array( array( 'operation' => 'count( id )',
                                                                   'name' => 'count' ) ) );
        return $rows[0]['count'];
    }
    
    /**
     * Fetches all eZ ContentObject IDs for the specified siteacess that contain a dynamic
     * ini setting for SiteAccessSettings-AvailableSiteAccessList in site.ini that matches
     * the siteaccess
     *
     * @param  string $siteaccess
     * @return array  list of contentobject Ids
     * @access private
     * @static
     * @author ymc-dabe
     */

    private static function getObjectIDsBySiteaccess( $siteaccess )
    {
        $object_ids = array();
        $db = eZDB::instance();
        $sql = "SELECT DISTINCT ini.contentobject_id
                FROM ymcdynamicinisetting ini
                INNER JOIN ezcontentobject object
                        ON object.id = ini.contentobject_id
                       AND object.status = ".(int)eZContentObject::STATUS_PUBLISHED."
                       WHERE ini.ini_type = ".(int)self::TYPE_ARRAY."
                  AND ini.ini_file = 'site.ini'
                  AND ini.ini_section = 'SiteAccessSettings'
                  AND ini.ini_parameter = 'AvailableSiteAccessList'
                  AND ini.ini_value = '".$db->escapeString($siteaccess)."';";
        $rows = $db->arrayQuery( $sql );
        foreach ( $rows as $row )
        {
            $objectID = (int)$row['contentobject_id'];
            if ( $objectID > 0 )
            {
                $object_ids[] = $objectID;
            }
        }
        return array_unique($object_ids);
    }

    /**
     * Fetches a list of all dynamic ini settings for a given siteaccess
     *
     * @param  string  $siteaccess
     * @param  boolean $asObject   Whether to return an OBJECT or ARRAY data
     * @return array  list of dynamic ini settings
     * @access public
     * @static
     * @author ymc-dabe
     */
    public static function fetchListBySiteaccess( $siteaccess, $asObject = true )
    {
        $object_ids = self::getObjectIDsBySiteaccess( $siteaccess );
        if ( count($object_ids) > 0 )
        {
            return eZPersistentObject::fetchObjectList( self::definition(),
                                                        null,
                                                        array( 'contentobject_id ' => array( $object_ids ) ),
                                                        null,
                                                        null,
                                                        $asObject );
        }
        else
        {
            return array();
        }
    }

    /**
     * Fetches all eZ ContentObject Ids that have $parentNodeID as parent and contain
     * dynamic ini settings
     *
     * @param  string  $parentNodeID
     * @return array   list of eZContentObjectIds
     * @access private
     * @static
     * @author ymc-dabe
     */
    private static function getObjectIDsByParentNodeID( $parentNodeID )
    {
        $object_ids = array();
        $db = eZDB::instance();
        $sql = "SELECT tree.contentobject_id
                FROM ezcontentobject_tree tree
                INNER JOIN ezcontentobject object
                        ON object.id = tree.contentobject_id
                       AND object.status = ".(int)eZContentObject::STATUS_PUBLISHED."
                INNER JOIN ezcontentclass_attribute c_att
                        ON c_att.contentclass_id = object.contentclass_id
                AND c_att.data_type_string = 'ymcdynamicinisetting'
                WHERE tree.parent_node_id = ".(int)$parentNodeID.";";
        $rows = $db->arrayQuery( $sql );
        foreach ( $rows as $row )
        {
            $objectID = (int)$row['contentobject_id'];
            if ( $objectID > 0 )
            {
                $object_ids[] = $objectID;
            }
        }
        return array_unique($object_ids);
    }
 
    /**
     * Fetches all dynamic ini settings from objects that have $parentNodeID as parent
     *
     *
     * @param  string  $parentNodeID
     * @param  boolean $asObject     Whether to return an OBJECT or ARRAY data
     * @return array List of dynamic ini settings
     * @access public
     * @static
     * @author ymc-dabe
     */
    public static function fetchListByParentNodeID( $parentNodeID, $asObject = true )
    {
        $object_ids = self::getObjectIDsByParentNodeID( $parentNodeID );
        if ( count($object_ids) > 0 )
        {
            return eZPersistentObject::fetchObjectList( self::definition(),
                                                        null,
                                                        array( 'contentobject_id ' => array( $object_ids ) ),
                                                        null,
                                                        null,
                                                        $asObject );
        }
        else
        {
            return array();
        }
    }

    /**
     * Fetches all dynamic ini settings  for a given siteaccess depending on existing ini settings
     *
     *
     * @param  string  $siteaccess [ymcToDoc]
     * @param  boolean $asObject   Whether to return an OBJECT or ARRAY data
     * @return array List of dynamic ini settings
     * @access public
     * @static
     * @author ymc-dabe
     */
    public static function fetchListByDefinedLogic( $siteaccess, $asObject = true )
    {
        $object_ids = self::getObjectIDsBySiteaccess( $siteaccess );
        $ini = eZINI::instance();
        if ( $ini->hasVariable('eZINISettings', 'DynamicIniSettingsSearchLogic') )
        {
            $dynamicIniSettingsSearchLogic = $ini->variable('eZINISettings', 'DynamicIniSettingsSearchLogic');
        }
        if ( $dynamicIniSettingsSearchLogic === 'SiteaccessMatchWithDirectChildren' )
        {
            foreach ( $object_ids as $objectID )
            {
                $contentObject = eZContentObject::fetch( $objectID );
                if ( is_object($contentObject) )
                {
                    $object_ids = array_merge($object_ids, self::getObjectIDsByParentNodeID( $contentObject->attribute('main_node_id') ));
                }
            }
        }
        if ( count($object_ids) > 0 )
        {
            return eZPersistentObject::fetchObjectList( self::definition(),
                                                        null,
                                                        array( 'contentobject_id ' => array( $object_ids ) ),
                                                        null,
                                                        null,
                                                        $asObject );
        }
        else
        {
            return array();
        }
    }

    /**
     * Helper function to get the current objects eZContentClassAttribute
     *
     * @return eZContentClassAttribute of the current object
     * @access protected
     * @author ymc-dabe
     */
    protected function contentClassAttribute()
    {
        return eZContentClassAttribute::fetch( $this->attribute( 'contentclassattribute_id' ) );
    }

    /**
     * Helper function to get the current objects eZContentObject
     *
     * @return eZContentObject of the current object
     * @access protected
     * @author ymc-dabe
     */
    protected function contentObject()
    {
        return eZContentObject::fetch( $this->attribute( 'contentobject_id' ) );
    }
    /**
     * Helper function to get the current objects contentObjectAttribute
     *
     * @return contentObjectAttribute of the current object
     * @access protected
     * @author ymc-dabe
     */
    protected function contentObjectAttribute()
    {
        if ( is_object( $this->attribute('contentObject') ) )
        {
            return eZContentObjectAttribute::fetch( $this->attribute( 'contentobjectattribute_id' ),
                                                    $this->attribute('contentObject')->attribute('current_version') );
        }
        else
        {
            return NULL;
        }
    }


    /**
     * Parses a dynamic ini value
     * Replaces %object with the current object and thus enables the real dynamic use
     * of dynamic inis: Define ini settings corresponding to other values from the same
     * content object.
     * 
     * Example: %object.main_node_id 
     *          %object.main_node.parent.url_alias
     *
     * @return mixed parsed Ini String
     * @access protected
     * @author ymc-pabu
     */
    protected function parseIniDynamicValue()
    {
        $ini_value = $this->attribute('ini_value');
        if ( strpos( $ini_value, '%object.' ) !== 0 )
        {
            return $ini_value;
        }
        else
        {
            $base_object = $this->attribute('contentobject');
            $options_array = explode('.', $ini_value);
            $we_dont_need_the_first_option_for_now = array_shift($options_array);
            $current_element = $base_object;
            foreach ( $options_array as $option )
            {
                if ( is_object($current_element) )
                {
                    $current_element = $current_element->attribute($option);
                }
                else if ( is_array($current_element) )
                {
                    $current_element = $current_element[$option];
                }
                else
                {
                    $current_element = '';
                    break;
                }

            }
            return $current_element;
        }
    }

    /**
     * Helper function for internal cleanup
     *
     * @param  integer $contentobjectattribute_id A ContentObjectAttributeID
     * @return void
     * @access public
     * @static
     * @author ymc-dabe
     */
    public static function dropByContentObjectAttributeID( $contentobjectattribute_id )
    {
        $db = eZDB::instance();
        $db->query( "DELETE FROM ymcdynamicinisetting
                     WHERE contentobjectattribute_id = ".(int)$contentobjectattribute_id );
    }

    /**
     * Helper function for internal cleanup
     *
     * @param  integer $contentclassattribute_id A ContentClassAttributeID
     * @return void
     * @access public
     * @static
     * @author ymc-dabe
     */
    public static function dropUnneededByContentClassAttributeID( $contentclassattribute_id )
    {
        $db = eZDB::instance();
        $db->query( "DELETE FROM ymcdynamicinisetting
                     WHERE contentclassattribute_id = ".(int)$contentclassattribute_id );
    }

    /**
     * Helper function for internal cleanup
     *
     * @param  integer $contentobject_id A ContentObjectID
     * @return void
     * @access public
     * @static
     * @author ymc-dabe
     */
    public static function dropUnneededByContentObjectID( $contentobject_id )
    {
        $db = eZDB::instance();
        $object = eZContentObject::fetch( $contentobject_id );
        if ( !is_object( $object ) )
        {
            $db->query( "DELETE FROM ymcdynamicinisetting
                         WHERE contentobject_id = ".(int)$object->attribute('id') );
        }
        else
        {
            $db->query( "DELETE FROM ymcdynamicinisetting
                         WHERE contentobject_id = ".(int)$object->attribute('id') )."
                           AND contentobjectattribute_version != ".(int)$object->attribute('current_version');
        }
    }
    
    /**
     * Helper function for internal cleanup
     *
     * @param  integer $contentobjectattribute_id A ContentObjectAttributeID
     * @param integer $contentobjectattribute_version a Version number
     * @return void
     * @access public
     * @static
     * @author ymc-dabe
     */
    public static function dropUnneededByContentObjectAttributeIdAndVersion( $contentobjectattribute_id, $contentobjectattribute_version )
    {
        $db = eZDB::instance();
        $db->query( "DELETE FROM ymcdynamicinisetting
                     WHERE contentobjectattribute_id = ".(int)$contentobjectattribute_id."
                       AND contentobjectattribute_version != ".(int)$contentobjectattribute_version );
    }
}
?>
