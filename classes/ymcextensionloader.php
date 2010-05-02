<?php
/**
 * File containing the ymcExtensionLoader class
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
 * @category   Classes
 * @package    ymcextensionloader
 * @copyright  2009 by YMC AG. All rights reserved.
 * @license    GNU General Public License v2.0
 * @author     ymc-dabe
 * @filesource
 */

/**
 * Class containing ymc's enhanced extension loader
 *
 * The setting "ymcEnhancedExtensionLoader" in section [SiteSettings] of site.ini
 * can be used to control which loading system to use. Please keep in mind, that changing
 * this inside extensions or siteaccesses doesn't make sense and isn't supported! In fact
 * this setting should be changed only in '[ezroot]/settings/site.ini' or
 * '[ezroot]/settings/override/site.ini[.append[.php]]' !
 *
 * YMCExtensionLoader introduces a new order how to load ini-settings in eZ
 * publish. This has been a long time demand as you might see here:
 * http://issues.ez.no/IssueView.php?Id=2709
 *
 * Besides a more consistent load-hierarchy YMCExtensionLoader adds support to
 * global override settings for a specific siteaccess which is useful
 * for local development.
 * Note that YMCExtensionLoader does not care much whether an extension is loaded
 * as "ActiveExtension" or "ActiveAccessExtension". The only thing is,
 * that an "ActiveExtension" is _tried_ to be loaded before an
 * "ActiveAccessExtension". It is recommended that you only use the
 * setting "ActiveExtension" to load an extension as long as you don't
 * experience problems with that.
 *
 * Additionally YMCExtensionLoader allows loading of extensions inside any other
 * extension (so there is in fact no more need to use the
 * "ActiveAccessExtension"-setting which most likely has been
 * introduced in eZ publish to workaround this missing feature).
 *
 * An other feature is, that PHP-autoloads using eZ components autoload
 * system are automatically supported, by simply putting a file 'autoload.php'
 * in an extensions subfolder 'autoload'. For this extension this is e.g.:
 * 'extension/ymcextensionloader/autoload/autoload.php'
 * With this feature it is possible to define autoloads by extension, which is
 * much easier to handle than having a single autoload-file for all extensions.
 *
 *
 * ----------------------------------------
 * Here is an example how the loader works:
 * ----------------------------------------
 * Assume there are four extensions called "FIRST", "SECOND", "THIRD", and
 * "FOURTH". The extension "FIRST" is activated in "settings/site.ini" (or
 * in "settings/override/site.ini" if you e.g. just want to try out a new
 * extension), whether as "ActiveExtension" or "ActiveAccessExtension"
 * (which doesn't matter much, see above).  Inside the extension "FIRST"
 * (which now is active), the extension "SECOND" is activated in
 * "extension/FIRST/settings/site.ini" (again: it doesn't matter whether
 * as "ActiveExtension" or as "ActiveAccessExtension" - I won't repeat
 * this fact anymore!). This will make YMCExtensionLoader load the extension
 * "SECOND" now.
 * Now we assume that the extension "SECOND" has the extension "THIRD"
 * referenced in its "extension/SECOND/settings/site.ini" which makes
 * eZ Publish to load "THIRD" now. I assume you got it now and can imagine
 * what will happen if "FOURTH" is referenced in "THIRD"...
 * ----------------------------------------
 *
 * This is the hierarchy used to load settings in YMCExtensionLoader - note that
 * higher numbers are searched for settings first (first look/load in 8.
 * then in 7. then in ... 1.):
 * 1. Default Settings (/settings)
 * 2. In ActiveExtensions (extension/EXTENSION/settings)
 * 3. In ActiveAccessExtensions (/extension/EXTENSION/settings)
 * 4. Default Siteaccess-Settings (/settings/siteaccess)
 * 5. Siteaccess in ActiveExtensions
 *                  (/extension/EXTENSION/settings/siteaccess/SITEACCESS)
 * 6. Siteaccess in ActiveAccessExtensions
 *                  (/extension/EXTENSION/settings/siteaccess/SITEACCESS)
 * 7. Global overrides (/settings/override)
 * 8. Global siteaccess overrides (/settings/override/sitaccess/SITEACCESS)
 *
 * Note that it is even possible to load an additional extension in the
 * siteaccess-settings of an already activated extension.
 * ----------- Here is an example: ------------------
 * It's possible to activate the extension "FIFTH" for the siteaccess
 * "SITEACCESS" which has a site.ini in the already active extension
 * "FOURTH" (so we talking about this site.ini:
 * "extension/FOURTH/settings/siteacces/SITEACCESS/site.ini"). If you now
 * access "SITEACCESS", the extension "FOURTH" will be active. And if
 * "FOURTH" activates the extension "SIX", this one will be active, too.
 * -------------------------------------------------------------
 *
 * @package    ymcextensionloader
 * @version    ---ymcVersionAutoGen---
 * @author     ymc-dabe
 */
class ymcExtensionLoader
{
    /**
     * Singleton instance
     *
     * @var    null|ymcExtensionLoader Contains null or the singleton instance of the this class
     * @access private
     * @static
     * @author ymc-dabe
     */
    private static $instance = null;

    /**
     * Contains information whether the ymcextensionloader is enabled or not
     *
     * @var    null|boolean
     * @access private
     * @static
     * @author ymc-dabe
     */
    private static $isEnabled = null;

    /**
     * Contains an array of extensions, that should not automatically added to the
     * eZ compontents autoload system
     *
     * @var    array
     * @access private
     * @static
     * @author ymc-dabe
     */
    private static $noAutoloadExtensions = array();

    /**
     * Contains an array of extensions, that should always be enabled despite any other ini-setting
     *
     * @var    array
     * @access private
     * @static
     * @author ymc-dabe
     */
    private static $alwaysEnabledExtensions = array();

    /**
     * Switch whether the early loading is over or not.
     *
     * Early loading is over as soon as all non-siteaccess extensions have been loaded
     *
     * @var boolean false as long as the early loading has not been completed
     * @access private
     * @static
     * @author ymc-dabe
     */
    private static $earlyLoadingCompleted = false;

    /**
     * Switch whether the standard loading is over or not.
     *
     * Loading is over as soon as the last (non-virtual-siteaccess) extension has been loaded
     *
     * @var boolean false as long as the standard loading has not been completed
     * @access private
     * @author ymc-dabe
     */
    private $standardLoadingCompleted = false;

    /**
     * Switch whether the virtual loading is over or not.
     *
     * @var boolean false as long as the virtual loading has not been completed
     * @access private
     * @author ymc-dabe
     */
    private $virtualLoadingCompleted = false;

    /**
     * Switch whether the loading is over or not.
     *
     * Loading is over as soon the last extension has been loaded
     *
     * @var boolean false as long as the loading has not been fully completed
     * @access private
     * @author ymc-dabe
     */
    private $loadingCompleted = false;

    /**
     * Contains all already registered extensions.
     *
     * @var array
     * @see ymcExtensionLoader::registerExtensions()
     * @access private
     * @author ymc-dabe
     */
    private $registeredExtensions = array();

    /**
     * Contains the global cache directory
     *
     * This defaults to 'var/cache' and will be set to whatever is used
     * during the basic load.
     *
     * @var string The global cache directory
     * @access private
     * @static
     * @author ymc-dabe
     */
    private static $globalCacheDirectory = 'var/cache';

    /**
     * Contains the global storage directory
     *
     * This defaults to 'var/storage' and will be set to whatever is used
     * during the basic load.
     *
     * @var string The global storage directory
     * @access private
     * @static
     * @author ymc-dabe
     */
    private static $globalStorageDirectory = 'var/storage';

    /**
     * Contains the current non-virtual siteaccess name
     *
     * This is set together with '$this->$standardLoadingCompleted=true;'
     *
     * @var    string|boolean false as long this information is not available
     * @access private
     * @author ymc-dabe
     */
    private $non_virtual_siteaccess_name = false;

    /**
     * Contains the original non-virtual siteaccess name
     *
     * This is set together with '$this->$loadingCompleted=true;'
     * Note that this always contains the original non-virtual siteaccess - even after a siteaccess-switch
     *
     * @var    string|boolean false as long this information is not available
     * @access private
     * @author ymc-dabe
     */
    private $originalNonVirtualSiteaccessName = false;

    /**
     * Contains the original siteaccess name
     *
     * This is set together with '$this->$loadingCompleted=true;'
     * Note that this always contains the original siteaccess - even after a siteaccess-switch
     *
     * @var    string|boolean false as long this information is not available
     * @access private
     * @author ymc-dabe
     */
    private $originalVirtualSiteaccessName = false;

    /**
     * Private constructor for the ymcExtensionLoader class
     *
     * Please use ymcExtensionLoader::getInstance() to get a singleton.
     *
     * @return void
     * @access private
     * @author ymc-dabe
     * @see ymcExtensionLoader::getInstance()
     */
    private function __construct()
    {
        //add ymcExtensionLoader's autoload-dir to the eZ compontents autoload system
        ezcBase::addClassRepository( 'extension/ymcextensionloader', 'extension/ymcextensionloader/autoload' );

        if ( self::$isEnabled === null )
        {
            //Check if ymcExtensionLoader is enabled
            $ini = eZINI::instance();
            if ( $ini->hasVariable( 'SiteSettings', 'ymcEnhancedExtensionLoader' ) and
                 $ini->variable( 'SiteSettings', 'ymcEnhancedExtensionLoader' ) === 'enabled' and
                 self::getCurrentSiteaccess() === null )
            {
                self::$isEnabled = true;
            }
            else
            {
                self::$isEnabled = false;
            }
        }
    }

    /**
     * Use this to get the single unique instance of ymcExtensionLoader.
     *
     * @return ymcExtensionLoader The unique instance of ymcExtensionLoader
     * @access public
     * @static
     * @author ymc-dabe
     */
    public static function getInstance()
    {
        if ( null === self::$instance )
        {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Returns the attributes for this object.
     *
     * @return array The attributes for this object.
     * @access public
     * @author ymc-dabe
     */
    public function attributes()
    {
        return array( 'is_enabled',
                      'early_loading_complete',
                      'standard_loading_complete',
                      'virtual_loading_complete',
                      'loading_complete',
                      'global_cache_directory',
                      'global_storage_directory',
                      'non_virtual_siteaccess',
                      'original_non_virtual_siteaccess',
                      'original_virtual_siteaccess' );
    }

    /**
     * Checks if an attribute exists.
     *
     * @param  string  $attr The attribute to check.
     * @return boolean If the attribute $attr exist the return value is true.
     * @access public
     * @author ymc-dabe
     */
    public function hasAttribute( $attr )
    {
        return in_array( $attr, $this->attributes() );
    }

    /**
     * Gets the value for an attribute.
     *
     * @param string $attr The attribute to return.
     * @return mixed The value for the attribute $attr or null if the attribute does not exist.
     * @access public
     * @author ymc-dabe
     */
    public function attribute( $attr )
    {
        switch ( $attr )
        {
            case 'is_enabled':
            {
                return self::$isEnabled;
            }
            break;

            case 'early_loading_complete':
            {
                return self::$earlyLoadingCompleted;
            }
            break;

            case 'standard_loading_complete':
            {
                return $this->standardLoadingCompleted;
            }
            break;

            case 'virtual_loading_complete':
            {
                return $this->virtualLoadingCompleted;
            }
            break;

            case 'loading_complete':
            {
                return $this->loadingCompleted;
            }
            break;

            case 'global_cache_directory':
            {
                return self::$globalCacheDirectory;
            }

            case 'global_storage_directory':
            {
                return self::$globalStorageDirectory;
            }

            case 'non_virtual_siteaccess':
            {
                return $this->non_virtual_siteaccess_name;
            }

            case 'original_non_virtual_siteaccess':
            {
                return $this->originalNonVirtualSiteaccessName;
            }

            case 'original_virtual_siteaccess':
            {
                return $this->originalVirtualSiteaccessName;
            }


            default:
            {
                return null;
            }
            break;
        }
    }

    /**
     * Helper method to find out the current siteaccess.
     *
     * Determine the current eZ publish internal siteaccess
     *
     * @return null|string The current siteaccess or null if no siteaccess is set, yet
     * @access public
     * @static
     * @author ymc-dabe
     */
    public static function getCurrentSiteaccess()
    {
        $siteaccess = null;
        if ( isset($GLOBALS['eZCurrentAccess']) and
             is_array($GLOBALS['eZCurrentAccess']) and
             isset($GLOBALS['eZCurrentAccess']['name']) )
        {
            $siteaccess = $GLOBALS['eZCurrentAccess']['name'];
        }
        return $siteaccess;
    }

    /**
     * Helper method to find out the current non-virtual siteaccess.
     *
     * @return boolean|string The current non-virtual siteaccess or false if not set
     * @access public
     * @static
     * @author ymc-dabe
     */
    public static function getCurrentNonVirtualSiteaccess()
    {
        if ( !self::virtualSiteaccessActive() )
        {
            return self::getCurrentSiteaccess();
        }

        $extensionLoader = self::getInstance();
        return $extensionLoader->attribute('non_virtual_siteaccess');
    }

    /**
     * Helper method to find out the current virtual siteaccess.
     *
     * @return boolean|string The current virtual siteaccess or false if not se
     * @access public
     * @static
     * @author ymc-dabe
     */
    public static function getCurrentVirtualSiteaccess()
    {
        if ( self::virtualSiteaccessActive() )
        {
            return self::getCurrentSiteaccess();
        }
        return false;
    }

    /**
     * Helper method to find out the current cache identifier.
     *
     * @return string unique prefix for cache files by siteaccess
     * @access public
     * @static
     * @author ymc-dabe
     */
    public static function getCurrentSiteaccessCacheString()
    {
        $siteaccess_string = self::getCurrentNonVirtualSiteaccess();
        if ( self::getCurrentVirtualSiteaccess() != '' )
        {
            $siteaccess_string .= '/'.self::getCurrentVirtualSiteaccess();
        }
        return $siteaccess_string;
    }

    /**
     * Helper method to find out the current cache identifier for usage in file names
     *
     * @return string unique prefix for cache file names by siteaccess
     * @access public
     * @static
     * @author ymc-dabe
     */
    public static function getCurrentSiteaccessCacheStringForFilename()
    {
        return str_replace('/', '.', self::getCurrentSiteaccessCacheString());
    }

    /**
     * Helper method to find out the current cache identifier for usage in directory names
     *
     * @return string unique prefix for cache directory names by siteaccess
     * @access public
     * @static
     * @author ymc-dabe
     */
    public static function getCurrentSiteaccessCacheStringForDirname()
    {
        return str_replace('/', '-', self::getCurrentSiteaccessCacheString());
    }

    /**
     * Helper method to find out the current cache hash (md5)
     *
     * @return string md5 hash by siteaccess
     * @access public
     * @static
     * @author ymc-dabe
     */
    public static function getCurrentSiteaccessHash()
    {
        return md5(self::getCurrentSiteaccessCacheString());
    }
    
    /**
     * Helper method to find out whether virtual siteaccesses are enabled
     *
     * @return boolean virtual siteaccess ist active
     * @access public
     * @static
     * @author ymc-dabe
     */
    public static function virtualSiteaccessActive()
    {
        $extensionLoader = self::getInstance();
        if ( $extensionLoader->attribute('non_virtual_siteaccess') != self::getCurrentSiteaccess() )
        {
            return true;
        }
        return false;
    }

    /**
     * Helper method to set the eZ publish internal siteaccess.
     *
     * @param  string  $siteaccess_name The siteaccess to set in eZ publish
     * @return boolean True if setting the siteaccess has been successfully done
     * @access private
     * @static
     * @author ymc-dabe
     */
    private static function setInternalSiteaccess( $siteaccess_name )
    {
        if ( isset($GLOBALS['eZCurrentAccess']) and
             is_array($GLOBALS['eZCurrentAccess']) and
             array_key_exists('name', $GLOBALS['eZCurrentAccess']) )
        {
            $GLOBALS['eZCurrentAccess']['name'] = $siteaccess_name;
            return true;
        }
        return false;
    }

    /**
     * Force activate the ymcExtensionLoader
     *
     * This is useful if despite of whatever is set in the ini-setting "ymcEnhancedExtensionLoader"
     * in section [SiteSettings] of site.ini the ymcExtensionLoader should be enabled.
     *
     * Note: This feature can only be used as long as the early loading hasn't been done, yet.
     *
     * @return boolean true if the force-activation has been successful, otherwise false
     * @access public
     * @static
     * @author ymc-dabe
     */
    public static function forceEnable()
    {
        if ( !self::$earlyLoadingCompleted )
        {
            self::$isEnabled = true;
            return true;
        }
        return false;
    }

    /**
     * Tell the ymcExtensionLoader about extensions that always need to be active
     *
     * This is useful for extension like 'basic_settings' which defines the basic settings
     * of an eZ publish installation without the need to change the configuration of the standard
     * settings delivered with eZ publish.
     * The optional parameter '$no_automatic_autoload' is useful for extensions like 'ymcbase',
     * which partly are used even before any extension is loaded and therefore cares their self
     * about correct autoloads...
     *
     * Note: This feature can only be used as long as the early loading hasn't been done, yet.
     *
     * @param  string $extension The name of an extension not to be added to eZc's autoload system
     * @param  boolean $no_automatic_autoload true if NO automatic addition of the extension
     *                                        to the eZ compontent's autoload system should be done
     * @return boolean true if the addition has been successful, otherwise false
     * @access public
     * @static
     * @author ymc-dabe
     */
    public static function addAlwaysEnabledExtension( $extension, $no_automatic_autoload = false )
    {
        if ( !self::$earlyLoadingCompleted )
        {
            self::$alwaysEnabledExtensions[] = $extension;
            if ( $no_automatic_autoload === true )
            {
                self::$noAutoloadExtensions[] = $extension;
            }
            return true;
        }
        return false;
    }

    /**
     * Register extensions "the YMC way".
     * 
     *
     * @return void
     * @access public
     * @author ymc-dabe
     * @see    eZExtension::activateExtensions()
     * @link   http://issues.ez.no/IssueView.php?Id=2709
     */
    public function registerExtensions( $virtual_siteaccess = false )
    {
        if ( !$this->attribute('is_enabled') )
        {
            eZDebug::writeError( "The ymcExtensionLoader is disabled, but ".__METHOD__." has just been called (which really shouldn't be done)!", __METHOD__ );
        }
        $siteaccess = self::getCurrentSiteaccess();
        $isBasicLoad = true;
        $is_virtual_load = false;
        $cache_hit = false;
        $defaultActiveExtensions = self::$alwaysEnabledExtensions;
        if ( !in_array('ymcextensionloader', $defaultActiveExtensions) )
        {
            $defaultActiveExtensions[] = 'ymcextensionloader';
        }

        if ( $this->standardLoadingCompleted === true and
             $virtual_siteaccess !== false and
             $siteaccess !== $virtual_siteaccess )
        {
            if ( $this->virtualLoadingCompleted === true )
            {
                eZDebug::writeError( "Unnecessary call to 'ymcExtensionLoader::registerExtensions('.$virtual_siteaccess.')'!", __METHOD__ );
                return;
            }
            $siteaccess = $virtual_siteaccess;
            self::setInternalSiteaccess($siteaccess);
            eZDebug::accumulatorStart( 'OpenVolanoExtensionLoader_VirtualSiteaccess',
                                       'OpenVolano: Enhanced Extension Loader',
                                       "After virtual siteaccess '$siteaccess' initialised " );
            $isBasicLoad = false;
            $is_virtual_load = true;
            $this->rebuildIniOverrideArray( $siteaccess, $isBasicLoad );
        }
        else if ( null !== $siteaccess )
        {
            if ( $this->standardLoadingCompleted === true )
            {
                eZDebug::writeError( "Unnecessary call to ".__METHOD__."!", __METHOD__ );
                return;
            }
            $isBasicLoad = false;
            eZDebug::accumulatorStart( 'OpenVolanoExtensionLoader_Siteaccess',
                                       'OpenVolano: Enhanced Extension Loader',
                                       "After siteaccess '$siteaccess' initialised " );
        }
        else
        {
            if ( self::$earlyLoadingCompleted === true )
            {
                eZDebug::writeWarning( "Force registering additional extensions - please keep in mind, that it is not possible to unload extensions", __METHOD__ );
            }
            eZDebug::accumulatorStart( 'OpenVolanoExtensionLoader_Basic',
                                       'OpenVolano: Enhanced Extension Loader',
                                       'Pre siteaccess initialised' );
        }

        $ini = eZINI::instance();
        $allExtensionsRegistered = false;
        if ( $isBasicLoad )
        {
            self::$globalCacheDirectory = eZSys::cacheDirectory();
            $cacheFileName = 'basic';
            $cache_var_name = 'ymcExtensionLoaderRegisterExtensionBasicLoadInformation';
        }
        else if ( $is_virtual_load )
        {
            $cacheFileName = 'siteaccess-'.$this->attribute('non_virtual_siteaccess').'-virtualsiteaccess-'.$siteaccess;
            $cache_var_name = 'ymcExtensionLoaderRegisterExtensionVirtualSiteaccessLoadInformation';
        }
        else
        {
            $cacheFileName = 'siteaccess-'.$siteaccess;
            $cache_var_name = 'ymcExtensionLoaderRegisterExtensionSiteaccessLoadInformation';
        }
        $write_cache = false;
        $can_write_cache = true;
        $cacheDir = eZSys::cacheDirectory().'/openvolano/extensions';
        if ( !is_writable( $cacheDir ) )
        {
            if ( !eZDir::mkdir( $cacheDir, 0777, true ) )
            {
                $can_write_cache = false;
                eZDebug::writeError( "Couldn't create cache directory '$cacheDir', perhaps wrong permissions", __METHOD__ );
            }
        }

        $cacheFilePath = $cacheDir.'/'.$cacheFileName;
        if ( !file_exists( $cacheFilePath ) )
        {
            $write_cache = $can_write_cache;
            if ( $isBasicLoad )
            {
                eZDebug::writeNotice( "No cache found for loading basic set of extensions", __METHOD__ );
            }
            else
            {
                eZDebug::writeNotice( "No cache found for loading per siteaccess set of extensions for siteaccess '$siteaccess'", __METHOD__ );
            }
        }
        else
        {
            include( $cacheFilePath );
            if ( !isset($$cache_var_name) )
            {
                eZDebug::writeWarning( "Cache '$cache_var_name' in file '$cacheFilePath' not found. Trying to force rewrite of this cache file...", __METHOD__ );
                $write_cache = $can_write_cache;
            }
            else
            {
                eZDebug::writeNotice( "Cache hit: $cacheFilePath", __METHOD__ );
                $defaultActiveExtensions = $$cache_var_name;
                $cache_hit = true;
                unset( $cache_var_name );
            }
        }

        $additional_lookups = 0;
        //Loop registering of extensions until all are loaded
        while ( !$allExtensionsRegistered )
        {
            //First we asume we do not need to check for new extensions
            $allExtensionsRegistered = true;

            //these extensions are always active
            $activeExtensions = $defaultActiveExtensions;

            //Get all active extension
            $activeExtensions = array_unique( array_merge($activeExtensions, eZExtension::activeExtensions()) );
            foreach ( $activeExtensions as $activeExtension )
            {
                //only activate an extension if it has not been registered, yet
                if ( !in_array($activeExtension, $this->registeredExtensions) )
                {
                    $this->registeredExtensions[] = $activeExtension;
                    $fullExtensionPath = eZExtension::baseDirectory() . '/' . $activeExtension;
                    if ( !file_exists( $fullExtensionPath ) )
                    {
                        eZDebug::writeWarning( "Extension '$activeExtension' does not exist, looked for directory '$fullExtensionPath'", __METHOD__ );
                    }
                    else
                    {
                        $fullExtensionAutoloadPath = $fullExtensionPath.'/autoload';
                        if ( $activeExtension !== 'ymcextensionloader' and
                             !in_array( $activeExtension, self::$noAutoloadExtensions) and
                             file_exists( $fullExtensionAutoloadPath ) )
                        {
                            //add the new extension's autoload-dir to the eZ compontents autoload system (if needed)
                            ezcBase::addClassRepository( $fullExtensionPath,
                                                         $fullExtensionAutoloadPath );
                        }
                        //We are about to activate a new extension which might need to load one ore more other extension (if we do not have a cached info about this)
                        $allExtensionsRegistered = $cache_hit;
                    }
                }
            }

            $this->rebuildIniOverrideArray( $siteaccess, $isBasicLoad );
            if ( !$allExtensionsRegistered )
            {
                $additional_lookups++;
            }
        }

        if ( !$cache_hit )
        {
            if ( $isBasicLoad )
            {
                eZDebug::writeNotice( "Loaded all basic extensions in $additional_lookups additional lookups...", __METHOD__ );
            }
            else if ( $is_virtual_load )
            {
                eZDebug::writeNotice( "Loaded all virtual siteaccess extensions in $additional_lookups additional lookups...", __METHOD__ );
            }
            else
            {
                eZDebug::writeNotice( "Loaded all siteaccess extensions in $additional_lookups additional lookups...", __METHOD__ );
            }
        }

        if ( $write_cache )
        {
            if ( $isBasicLoad )
            {
                eZDebug::writeNotice( "Storing basic extension load information into cache file '$cacheFilePath'...", __METHOD__ );
            }
            else if ( $is_virtual_load )
            {
                eZDebug::writeNotice( "Storing virtual siteaccess extension load information into cache file '$cacheFilePath'...", __METHOD__ );
            }
            else
            {
                eZDebug::writeNotice( "Storing siteaccess extension load information into cache file '$cacheFilePath'...", __METHOD__ );
            }
            $php = new eZPHPCreator( $cacheDir, $cacheFileName );
            $php->addRawVariable( $cache_var_name, $this->registeredExtensions );
            $php->store();
        }

        if ( $is_virtual_load )
        {
            $this->virtualLoadingCompleted = true;
            eZDebug::accumulatorStop( 'OpenVolanoExtensionLoader_VirtualSiteaccess' );
        }
        else if ( !$isBasicLoad )
        {
            $this->standardLoadingCompleted = true;
            $this->non_virtual_siteaccess_name = $siteaccess;
            eZDebug::accumulatorStop( 'OpenVolanoExtensionLoader_Siteaccess' );
        }
        else
        {
            self::$earlyLoadingCompleted = true;
            eZDebug::accumulatorStop( 'OpenVolanoExtensionLoader_Basic' );
        }

        //Use the following line to take a look into the ini-hierarchy...
        //ymc_pr($GLOBALS["eZINIOverrideDirList"], $siteaccess.'|'.self::getCurrentSiteaccess());

        if ( !$is_virtual_load and
             !$isBasicLoad and
             $ini->hasVariable( 'SiteAccessSettings', 'VirtualSiteaccessSystem' ) and
             $ini->variable( 'SiteAccessSettings', 'VirtualSiteaccessSystem' ) !== 'disabled' )
        {
            $allowLoadingOfPreviouslyKnownSiteaccesses = false;
            if ( $ini->hasVariable( 'SiteAccessSettings', 'VirtualSiteaccessSystem' ) and
                 $ini->variable( 'VirtualSiteaccessSettings', 'AllowLoadingOfPerviouslyKnowSiteaccesses' ) === 'enabled' )
            {
                $allowLoadingOfPreviouslyKnownSiteaccesses = true;
            }

            if ( isset($GLOBALS['eZURIRequestInstance']) and
                 is_object($GLOBALS['eZURIRequestInstance']) )
            {
                $uri = eZURI::instance();
                $elements = $uri->elements( false );
                if ( count($elements) > 0 and
                     $elements[0] != '' )
                {
                    $goInVirtualSiteaccessMode = true;
                    if ( $ini->hasVariable( 'VirtualSiteaccessSettings', 'SkipLoadingForUri' ) and
                         is_array($ini->variable( 'VirtualSiteaccessSettings', 'SkipLoadingForUri' )) and
                         count( $ini->variable( 'VirtualSiteaccessSettings', 'SkipLoadingForUri' )) > 0 )
                    {
                        $uri_string = $uri->elements( true );
                        foreach ( $ini->variable( 'VirtualSiteaccessSettings', 'SkipLoadingForUri' ) as $ignoreUriForVirtualSiteaccess )
                        {
                            if ( strpos( $uri_string, $ignoreUriForVirtualSiteaccess ) === 0 )
                            {
                                $goInVirtualSiteaccessMode = false;
                                break;
                            }
                        }
                        unset( $uri_string );
                    }
                }
                else
                {
                    $goInVirtualSiteaccessMode = false;
                }

                if ( $goInVirtualSiteaccessMode )
                {
                    $matchIndex = 1;

                    $name = $elements[0];
                    //by ymc-dabe //Code taken from /access.php line 241-249 of eZ publish v4.1.3 //KEEP IT IN SYNC! //start
                    $name = preg_replace( array( '/[^a-zA-Z0-9]+/',
                                                 '/_+/',
                                                 '/^_/',
                                                 '/_$/' ),
                                          array( '_',
                                                 '_',
                                                 '',
                                                 '' ),
                                          $name );
                    //by ymc-dabe //Code taken from /access.php line 241-249 of eZ publush v4.1.3 //KEEP IT IN SYNC! //end
                    
                    if ( $allowLoadingOfPreviouslyKnownSiteaccesses or
                         !in_array($name, $ini->variable( 'SiteAccessSettings', 'AvailableSiteAccessList' )) )
                    {
                        eZSys::addAccessPath( $name );
                        $uri->increase( $matchIndex );
                        $uri->dropBase();
                        $this->registerExtensions( $name );

                        //die if virtual siteaccess is not found
                        if ( !in_array($name, $ini->variable( 'SiteAccessSettings', 'AvailableSiteAccessList' )) )
                        {
                            header( $_SERVER['SERVER_PROTOCOL'] .  " 400 Bad Request" );
                            header( "Status: 400 Bad Request" );
                            eZExecution::cleanExit();
                        }
                    }
                    unset($name);
                }
            }
            else if ( isset($GLOBALS['ymcEnhancedExtensionLoaderVirtualSiteaccess']) and
                      $GLOBALS['ymcEnhancedExtensionLoaderVirtualSiteaccess'] != '' )
            {
                $virtualSiteaccessName = $GLOBALS['ymcEnhancedExtensionLoaderVirtualSiteaccess'];

                if ( $allowLoadingOfPreviouslyKnownSiteaccesses or
                     !in_array($virtualSiteaccessName, $ini->variable( 'SiteAccessSettings', 'AvailableSiteAccessList' )) )
                {
                    eZSys::addAccessPath( $virtualSiteaccessName );
                    $this->registerExtensions( $virtualSiteaccessName );
                    if ( !in_array($virtualSiteaccessName, $ini->variable( 'SiteAccessSettings', 'AvailableSiteAccessList' )) )
                    {
                        fputs( STDERR, "\n----------\nError: Invalid siteaccess '$virtualSiteaccessName'!\n----------\n\n" );
                        eZExecution::cleanExit();
                    }
                }
                unset($virtualSiteaccessName);
            }
        }
        else if ( $this->standardLoadingCompleted === true )
        {
            $this->virtualLoadingCompleted = true;
        }

        if ( $this->standardLoadingCompleted === true )
        {
            $this->loadingCompleted = true;
            if ( $this->originalNonVirtualSiteaccessName === false )
            {
                $this->originalNonVirtualSiteaccessName = $this->attribute('non_virtual_siteaccess');
                if ( $this->originalVirtualSiteaccessName === false and
                     $siteaccess != $this->attribute('non_virtual_siteaccess') )
                {
                    $this->originalVirtualSiteaccessName = $siteaccess;
                }
            }
        }
    }
    /**
     * Generate INI Override List the YMC Way
     *
     * This is the hierarchy used to load settings in YMCExtensionLoader - note that
     * higher numbers are searched for settings first (first look/load in 8.
     * then in 7. then in ... 1.):
     * 1. Default Settings (/settings)
     * 2. In ActiveExtensions (extension/EXTENSION/settings)
     * 3. In ActiveAccessExtensions (/extension/EXTENSION/settings)
     * 4. Default Siteaccess-Settings (/settings/siteaccess)
     * 5. Siteaccess in ActiveExtensions
     *                  (/extension/EXTENSION/settings/siteaccess/SITEACCESS)
     * 6. Siteaccess in ActiveAccessExtensions
     *                  (/extension/EXTENSION/settings/siteaccess/SITEACCESS)
     * 7. Global overrides (/settings/override)
     * 8. Global siteaccess overrides (/settings/override/sitaccess/SITEACCESS)
     *
     * This function gets called up to three times:
     * 1. Basic Laod
     * 2. Siteaccess Load
     * 3. Virtual siteaccess Load
     *
     * @param  string  $siteaccess the current siteaccess to generate ini overrides for
     * @todo   ask dabe what this is:
     * @param  boolean $isBasicLoad true if called through eZ Publish basic load
     * @param  boolean $useDynamicIni true to respect dynamic ini cache dir locations
     * @param  boolean $keepNonVirtualSiteaccessOnVirtualLoad true to keep settings for
     *                 non-virtual siteaccess when loading a virtual one
     * @return void
     * @access private
     * @author ymc-dabe
     */
    private function rebuildIniOverrideArray( $siteaccess, $isBasicLoad, $useDynamicIni = false, $keepNonVirtualSiteaccessOnVirtualLoad = true )
    {
        if ( $isBasicLoad )
        {
            eZDebug::writeDebug( "Rebuilding INI-overrides for basic-load...", __METHOD__ );
        }
        else if ( $useDynamicIni )
        {
            eZDebug::writeDebug( "Rebuilding INI-overrides for siteaccess '$siteaccess' in respect of DynamicINI-settings...", __METHOD__ );
        }
        else
        {
            eZDebug::writeDebug( "Rebuilding INI-overrides for siteaccess '$siteaccess'...", __METHOD__ );
        }

        if ( $keepNonVirtualSiteaccessOnVirtualLoad )
        {
            eZDebug::writeDebug( "Keeping possible non-virtual-siteaccess settings...", __METHOD__ );
        }
        else
        {
            eZDebug::writeDebug( "Dropping possible non-virtual-siteaccess settings...", __METHOD__ );
        }


        $ini = eZINI::instance();
        //now re-build the new ini-override-array...
        $extensionSettingsPaths = array();
        $extensionSiteaccessSettingsPaths = array();
        foreach ( array_reverse($this->registeredExtensions) as $registeredExtension )
        {
            $extensionSettingsPath = eZExtension::baseDirectory().'/'.$registeredExtension.'/settings';
            if ( file_exists( $extensionSettingsPath ) )
            {
                $extensionSettingsPaths[] = $extensionSettingsPath;
                if ( !$isBasicLoad )
                {
                    $extensionSiteaccessSettingsPath = $extensionSettingsPath.'/siteaccess/'.$siteaccess;
                    if ( file_exists( $extensionSiteaccessSettingsPath ) )
                    {
                        $extensionSiteaccessSettingsPaths[] = $extensionSiteaccessSettingsPath;
                    }

                    //add non-virtual-siteaccess (if needed)
                    if ( $keepNonVirtualSiteaccessOnVirtualLoad and
                         $this->attribute('non_virtual_siteaccess') !== false and
                         $siteaccess !== $this->attribute('non_virtual_siteaccess') )
                    {
                        $extensionSiteaccessSettingsPath = $extensionSettingsPath.'/siteaccess/'.$this->attribute('non_virtual_siteaccess');
                        if ( file_exists( $extensionSiteaccessSettingsPath ) )
                        {
                            $extensionSiteaccessSettingsPaths[] = $extensionSiteaccessSettingsPath;
                        }
                    }
                }
            }
        }

        //Flush any ini-override information in order to rebuild it...
        unset($GLOBALS["eZINIOverrideDirList"]);

        if ( $useDynamicIni )
        {
            $this->prependDynamicINI( $siteaccess );
        }

        //prepend the siteaccesses in extensions
        foreach ( $extensionSiteaccessSettingsPaths as $extensionSiteaccessSettingsPaths )
        {
            $ini->prependOverrideDir( $extensionSiteaccessSettingsPaths, true );
        }

        //prepend non-extension siteaccess
        if ( !$isBasicLoad and file_exists( "settings/siteaccess/$siteaccess" ) )
        {
            $ini->prependOverrideDir( "siteaccess/$siteaccess", false, 'siteaccess' );
        }

        //prepend non-extension non-virtual-siteaccess (if needed)
        if ( !$isBasicLoad and
             $keepNonVirtualSiteaccessOnVirtualLoad and
             $siteaccess !== $this->attribute('non_virtual_siteaccess') and
             $this->attribute('non_virtual_siteaccess') !== false )
        {
            $non_virtual_siteaccess = $this->attribute('non_virtual_siteaccess');
            if ( file_exists( "settings/siteaccess/$non_virtual_siteaccess" ) )
            {
                $ini->prependOverrideDir( "siteaccess/$non_virtual_siteaccess", false, 'siteaccess' );
            }
        }

        //prepend extensions
        foreach ( $extensionSettingsPaths as $extensionSettingsPath )
        {
            $ini->prependOverrideDir( $extensionSettingsPath, true );
        }

        //almost finally add support for non-virtual-siteaccess in the global override-dir
        if ( !$isBasicLoad and
             $keepNonVirtualSiteaccessOnVirtualLoad and
             $siteaccess !== $this->attribute('non_virtual_siteaccess') and
             $this->attribute('non_virtual_siteaccess') !== false )
        {
            $non_virtual_siteaccess = $this->attribute('non_virtual_siteaccess');
            if ( file_exists( "settings/override/siteaccess/$non_virtual_siteaccess" ) )
            {
                $ini->appendOverrideDir( "override/siteaccess/$non_virtual_siteaccess" );
            }
        }

        //finally add support for siteaccess in the global override-dir
        if ( !$isBasicLoad and file_exists( "settings/override/siteaccess/$siteaccess" ) )
        {
            $ini->appendOverrideDir( "override/siteaccess/$siteaccess" );
        }

        //$ini->loadCache();
        self::resetAllLoadedIniInstances();

        //check if we need a rerun with dynamic-ini-settings enabled or without keeping non-virtual-siteaccess.settings...
        if ( !$isBasicLoad and !$useDynamicIni and $keepNonVirtualSiteaccessOnVirtualLoad )
        {
            $needRerun = false;
            if ( $ini->hasVariable( 'eZINISettings', 'DynamicSettings' ) and
                 $ini->variable( 'eZINISettings', 'DynamicSettings' ) === 'enabled' )
            {
                $needRerun = true;
                $useDynamicIni = true;
                eZDebug::writeDebug( "Rerun requested, as DynamicINI-settings are now enabled...", __METHOD__ );
            }

            if ( $ini->hasVariable( 'VirtualSiteaccessSettings', 'KeepNonVirtualSiteaccessSettings' ) and
                 $ini->variable( 'VirtualSiteaccessSettings', 'KeepNonVirtualSiteaccessSettings' ) === 'disabled' )
            {
                $needRerun = true;
                $keepNonVirtualSiteaccessOnVirtualLoad = false;
                eZDebug::writeDebug( "Rerun requested, as KeepNonVirtualSiteaccessSettings is now disabled...", __METHOD__ );
            }

            if ( $needRerun )
            {
                eZDebug::writeDebug( "Entering rerun as requested earlier...", __METHOD__ );
                $this->rebuildIniOverrideArray( $siteaccess, $isBasicLoad, $useDynamicIni, $keepNonVirtualSiteaccessOnVirtualLoad );
                eZDebug::writeDebug( "Finished rerun...", __METHOD__ );
            }
        }

        if ( !$this->attribute('standard_loading_complete') )
        {
            //We do not need to try keeping non-virtual-siteacess-settings, as long standard-loading hasn't finished
            $keepNonVirtualSiteaccessOnVirtualLoad = false;
            eZDebug::writeDebug( "Not trying to keep non-virtual-siteacess-settings, as standard-loading has not been finished", __METHOD__ );
        }


    }
    /**
     * Support for dynamic ini settings: Prepend dynamic ini cache files on activation
     *
     *
     * @param  string  $siteaccess the current siteaccess to prepend dynamic ini overrides
     * @return void
     * @access private
     * @uses ymcDynamicIniSetting
     * @author ymc-dabe
     */
    private function prependDynamicINI( $siteaccess )
    {
        $ini = eZINI::instance();
        eZDebug::writeNotice( "Dynamic INI-settings are enabled for siteaccess '$siteaccess'", __METHOD__ );

        $dynamicIniSettings = ymcDynamicIniSetting::fetchListByDefinedLogic( $siteaccess );
        $iniFilesToWrite = array();
        $finalCacheDirs = array();
        
        foreach ( $dynamicIniSettings as $dynamicIniSetting )
        {
            $iniFile =  $dynamicIniSetting->attribute('ini_file');
            $array_key = $dynamicIniSetting->attribute('contentobject_id').'-'.$iniFile;
            $finalCacheDir = $this->attribute('global_cache_directory').'/openvolano/dynamic_ini/'.$dynamicIniSetting->attribute('contentobject_id').'/'.$siteaccess;
            $finalCacheDirs[] = $finalCacheDir;
            if ( file_exists($finalCacheDir.'/'.$iniFile) )
            {
                eZDebug::writeNotice( "Cache hit: $finalCacheDir/$iniFile", __METHOD__ );
                continue;
            }

            if ( !isset($iniFilesToWrite[$array_key]) )
            {
                $iniFilesToWrite[$array_key] = array( 'iniInstance' => eZINI::instance( $iniFile, $finalCacheDir, null, false, null, true, true ),
                                                      'ini_dir' => $finalCacheDir,
                                                      'ini_file' => $iniFile );
            }
            $iniInstance = $iniFilesToWrite[$array_key]['iniInstance'];

            if ( (int)$dynamicIniSetting->attribute('ini_type') !== ymcDynamicIniSetting::TYPE_ARRAY )
            {
                $iniInstance->setVariable( $dynamicIniSetting->attribute('ini_section'),
                                            $dynamicIniSetting->attribute('ini_parameter'),
                                            $dynamicIniSetting->attribute('ini_value_dynamic') );
            }
            else
            {
                if ( $iniInstance->hasVariable( $dynamicIniSetting->attribute('ini_section'),
                                                 $dynamicIniSetting->attribute('ini_parameter') ) )
                {
                    $existingValues = $iniInstance->variable( $dynamicIniSetting->attribute('ini_section'),
                                                                $dynamicIniSetting->attribute('ini_parameter') );
                }
                else
                {
                    $existingValues = array();
                }
                $newKey = $dynamicIniSetting->attribute('ini_key');
                $newValue = $dynamicIniSetting->attribute('ini_value_dynamic');
                if ( $newKey == '' and $newValue == '' )
                {
                    array_unshift( $existingValues, null );
                }
                else if ( is_numeric($newKey) )
                {
                    $existingValues[] = $newValue;
                }
                else
                {
                    $existingValues[$newKey] = $newValue;
                }

                $iniInstance->setVariable( $dynamicIniSetting->attribute('ini_section'),
                                            $dynamicIniSetting->attribute('ini_parameter'),
                                            $existingValues );
            }
        }

        if ( count($iniFilesToWrite) > 0 )
        {
            eZDebug::writeDebug( "Generating dynamic-ini-files for '$siteaccess'", __METHOD__ );
        }

        
        foreach ( $iniFilesToWrite as $ini_infos )
        {
            $iniInstance = $ini_infos['iniInstance'];
            $finalCacheDir = $ini_infos['ini_dir'];
            $iniFile = $ini_infos['ini_file'];

            if ( !is_writable( $finalCacheDir ) )
            {
                if ( !eZDir::mkdir( $finalCacheDir, 0777, true ) )
                {
                    eZDebug::writeError( "Couldn't create cache directory '$finalCacheDir', perhaps wrong permissions", __METHOD__ );
                    continue;
                }
            }
            $iniInstance->save();
            eZDebug::writeDebug( "Created INI '$iniFile' at:\n$finalCacheDir/$iniFile", __METHOD__ );
        }
        $finalCacheDirs = array_unique($finalCacheDirs);
        foreach ( $finalCacheDirs as $finalCacheDir )
        {
            eZDebug::writeDebug( "Added dynamic INI-settings for siteaccess '$siteaccess' using cache-dir:\n$finalCacheDir", __METHOD__ );
            $ini->prependOverrideDir( $finalCacheDir, true );
        }
    }

    /**
     * Flushes all ini instances from PHP globals
     *
     * @return void
     * @access private
     * @static
     * @author ymc-dabe
     */
    private static function resetAllLoadedIniInstances()
    {
        //sadly, there is no other way getting all eZINIGlobalInstances
        //so we loop through all globals an search for the ini-prefix
        foreach ( array_keys( $GLOBALS ) as $key )
        {
            if ( strlen( $key ) > 19 and
                 substr_compare( $key, 'eZINIGlobalInstance-', 0, 20 ) === 0  )
            {
                if ( $GLOBALS[$key] instanceof eZINI )
                {
                    $GLOBALS[$key]->loadCache();;
                }
            }
        }

        //Flush some other globals depending on above settings
        unset($GLOBALS['eZAuditNameSettings']);
    }

    /**
     * Switch the siteaccess during runtime.
     *
     * WARNING: eZ publish does not support unloading extensions very well. Using this method might
     *          leave the system with partly registered extensions!
     *
     * NOTE: This is the public and static wrapper of the internally used private non-static
     *       method which actually does the switching...
     *
     * NOTE: Of course this depends on the ymcExtensionLoader enabled!
     *
     * @param  string $requested_siteaccess The name of an existing real siteaccess.
     * @param  string $virtual_siteaccess   The name of an optional virtual siteaccess.
     * @return mixed  true on success - otherwise false
     * @access public
     * @static
     * @author ymc-dabe
     * @see ymcExtensionLoader::switchSiteaccessInternally
     */
    public static function switchSiteaccess( $requested_siteaccess = false, $virtual_siteaccess = false )
    {
        $extensionLoader = self::getInstance();
        return $extensionLoader->switchSiteaccessInternally($requested_siteaccess, $virtual_siteaccess );
    }

    /**
     * Switch the siteaccess during runtime.
     *
     * WARNING: eZ publish does not support unloading extensions very well. Using this method might
     *          leave the system with partly registered extensions!
     *
     * @param  string  $requested_siteaccess The name of an existing real siteaccess.
     * @param  string  $virtual_siteaccess   The name of an optional virtual siteaccess.
     * @return boolean true on success - otherwise false
     * @access private
     * @author ymc-dabe
     */
    private function switchSiteaccessInternally( $requested_siteaccess = false, $virtual_siteaccess = false )
    {
        if ( self::getCurrentSiteaccess() === null )
        {
            return false;
        }

        //We might simply want to switch back to the original siteaccess...
        if ( $requested_siteaccess === false and
             $virtual_siteaccess === false )
        {
            $requested_siteaccess = $this->attribute('original_non_virtual_siteaccess');
            $virtual_siteaccess = $this->attribute('original_virtual_siteaccess');
        }

        if ( !$this->attribute('is_enabled') )
        {
            eZDebug::writeError( "The ymcExtensionLoader is disabled, but ".__METHOD__." has just been called (which really shouldn't be done)!", __METHOD__ );
            return false;
        }

        //no switching needed. bye.
        if ( $requested_siteaccess == self::getCurrentNonVirtualSiteaccess() and
             $virtual_siteaccess == self::getCurrentVirtualSiteaccess() )
        {
            return true;
        }

        //Reset the loaded extensions...
        self::$earlyLoadingCompleted = false;
        $this->standardLoadingCompleted = false;
        $this->virtualLoadingCompleted = false;
        $this->loadingCompleted = false;
        $this->registeredExtensions = array();
        $this->non_virtual_siteaccess_name = false;

        //Reset all loaded INIs
        unset($GLOBALS["eZINIOverrideDirList"]);

        //Set no siteaccess
        self::setInternalSiteaccess( null );

        //Initialise INI (using site.ini)
        $ini = eZINI::instance();

        //FLush the INI-Cache (of site.ini)
        $ini->loadCache();

        //Activate basic extensions
        $this->registerExtensions();

        //Set the requested siteaccess and load it
        self::setInternalSiteaccess( $requested_siteaccess );

        //Advice new INI-files to use (as it is done by eZ publish on startup
        if ( file_exists( "settings/siteaccess/$requested_siteaccess" ) )
        {
            $ini->prependOverrideDir( "siteaccess/$requested_siteaccess", false, 'siteaccess' );
        }
        eZExtension::prependExtensionSiteAccesses( $requested_siteaccess );

        //FLush the INI-Cache (of site.ini)
        $ini->loadCache();

        //Register access-extensions
        $this->registerExtensions();

        //Activate the virtual_siteaccess if needed
        if ( $virtual_siteaccess !== false )
        {
            $this->registerExtensions( $virtual_siteaccess );
        }

        return true;
    }
}
?>
