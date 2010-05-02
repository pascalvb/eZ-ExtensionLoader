#!/usr/bin/env php
<?php

/**
 * This script dumps the calculated final ini files using the
 * ymcExtensionLoader ini loading system for debug purposes
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
 * @category   helper script
 * @package    ymcExtensionLoader
 * @copyright  2010 by YMC AG. All rights reserved.
 * @license    GNU General Public License v2.0
 * @author     ymc-pabu
 * @filesource
 */



require_once( 'autoload.php' );

$cli = eZCLI::instance();
$endl = $cli->endlineString();
$script = eZScript::instance( array( 'description' => ( "Outputs combined settings for a specific INI-file, using the specified siteaccess" ),
                                     'use-session' => false,
                                     'use-modules' => false,
                                     'use-extensions' => true ) );

$script->startup();
$options = $script->getOptions( "[ini:][with_non_extension_settings]",
                                "",
                                array( 'ini' => "The INI-file to use without '.append' or '.append.php' (e.g. site.ini or content.ini)",
                                       'with_non_extension_settings' => "Process files in '[ezroot]/settings/...', by default those files are ignored.") );
$script->initialize();

$siteaccess = $options['siteaccess'] ? $options['siteaccess'] : false;
$ini_file = $options['ini'];
if ( isset($options[ 'with_non_extension_settings' ]) and
     (boolean)$options[ 'with_non_extension_settings' ] === true )
{
    $with_non_extension_settings = true;
}
else
{
    $with_non_extension_settings = false;
}


if ( $siteaccess == '' )
{
    $script->showHelp();
    $cli->error( "No siteaccess given!" );
    $script->shutdown();
    exit;
}

if ( $ini_file == '' )
{
    $script->showHelp();
    $cli->error( "Missing required option 'ini'!" );
    $script->shutdown();
    exit;
}

if ( $with_non_extension_settings )
{
    $ini_root_dir = 'settings';
}
else
{
    $ini_root_dir = false;
}


$loaded_ini_instance = eZINI::instance( $ini_file, $ini_root_dir );

//A copy of this array is OKAY (we need no reference here) //next line
$iniBlockValues = $loaded_ini_instance->groups();
ksort($iniBlockValues);
foreach ( $iniBlockValues as $section_name => &$iniBlockValue )
{
    ksort($iniBlockValue);
    $cli->output( "[$section_name]" );
    foreach ( $iniBlockValue as $var_name => &$var_value )
    {
        if ( is_array($var_value) )
        {
            foreach ( $var_value as $array_var_key => $array_var_value )
            {
                if ( is_integer($array_var_key) )
                {
                    $array_var_key = '';
                }
                $cli->output( $var_name.'['.$array_var_key.']='.$array_var_value );
            }
        }
        else
        {
            $cli->output( "$var_name=$var_value" );
        }
    }
    $cli->output( "" );
}


//print_r($loaded_ini_instance->groups());


$script->shutdown();
?>
