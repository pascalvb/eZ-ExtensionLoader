#!/usr/bin/env php5
<?php
/**
 * File containing the ymcgen-autoload script
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
 * @author     ymc-toko
 * @filesource
 */


require_once 'ezc/Base/ezc_bootstrap.php';

$input = new ezcConsoleInput();

$option = $input->registerOption(
    new ezcConsoleOption(
        'h',
        'help',
        ezcConsoleInput::TYPE_NONE,
        null,
        FALSE
    )
);
$option->isHelpOption = TRUE;
$option->arguments    = FALSE;

$option = $input->registerOption(
    new ezcConsoleOption(
        'd',
        'dir',
        ezcConsoleInput::TYPE_STRING,
        '.',
        FALSE,
        'Search Directory.',
        'Directory to search for php class files, defaults to .'
  )
);
$option->arguments    = FALSE;

$option = $input->registerOption(
    new ezcConsoleOption(
        't',
        'target',
        ezcConsoleInput::TYPE_STRING,
        './autoload',
        FALSE,
        'autoload dir',
        'The directory where to save the autoload file, defaults to ./autoload'
  )
);
$option->arguments    = FALSE;

$option = $input->registerOption(
    new ezcConsoleOption(
        'p',
        'prefix',
        ezcConsoleInput::TYPE_STRING,
        null,
        FALSE,
        'classes prefix',
        'The prefix of the classes for which the autoload file should be created'
  )
);
$option->arguments    = FALSE;

$input->registerOption(
    new ezcConsoleOption(
        'b',
        'basedir',
        ezcConsoleInput::TYPE_STRING,
        '.',
        FALSE,
        'basedir for autoload paths',
        'Paths in the autoload array are written relatively to this dir.  Defaults to .'
  )
);

try
{
    $input->process();
}
catch ( ezcConsoleOptionException $e )
{
    die( $e->getMessage()."\n" );
}

if ( $input->getOption( 'h' )->value===true )
{
    echo $input->getHelpText('eZ components autoload generator' );
    die(  );
}

foreach( array( 'dir', 'target', 'basedir' ) as $option )
{
    $$option = realpath( $input->getOption( $option )->value );
    if( FALSE === $$option )
    {
        echo 'Given directory '
             .$input->getOption( $option )->value
             .' for option '.$option
             ." does not exist.\n";
        die(  );
    }
}
$prefix = $input->getOption( 'p' )->value;

chdir( $basedir );
$relPath = relPath( $basedir, $dir );

$iterator = new DirectoryIteratorFilter( $relPath );
foreach( $iterator as $file )
{
//    echo $file,"\n";
    $classNames = getClassNamesFromSource( file_get_contents( $file ) );
    foreach( $classNames as $className )
    {
        $autoloadArray[$className] = (string)$file;
    }
}

if( $prefix )
{
    $autoloadArray = filterByPrefix( $autoloadArray, $prefix );
    //The autoload framework searches also for two word prefixes. So we need to
    //convert the prefix DbSchema to the filename db_schema_autoload.php
    preg_match_all( '/[A-Z][a-z]+/', $prefix, $matches );
    $filename = strtolower( implode( '_', $matches[0] ) ).'_autoload.php';
}
else
{
    $filename = 'autoload.php';
}

ksort($autoloadArray);

$filetext = "<?php \nreturn ";
$filetext .= var_export( $autoloadArray, true );
$filetext .= ";\n?>";


file_put_contents( $target.'/'.$filename , $filetext );

/**
 * Returns a relative path from one dir to another.
 *
 * The given directories needs to be realpaths. Use realpath() if you're not
 * sure.
 *
 * @param string $from
 * @param string $to
 * @return string
 */
function relPath( $from, $to )
{
    $fromArray = explode( DIRECTORY_SEPARATOR, $from );
    $toArray   = explode( DIRECTORY_SEPARATOR, $to );
    $i = 0;
    $count = min(
            count( $fromArray ),
            count( $toArray )
            );
    while( $i < $count && $fromArray[$i] === $toArray[$i] )
    {
        unset( $fromArray[$i] );
        unset( $toArray[$i++] );
    }
    $result =
        '.'.DIRECTORY_SEPARATOR
        .str_repeat( '..'.DIRECTORY_SEPARATOR, count( $fromArray ) )
        .implode( DIRECTORY_SEPARATOR, $toArray );
    return rtrim( $result, DIRECTORY_SEPARATOR );
}

/**
 * filterByPrefix
 *
 * One autoloadfile contains only classes with the same prefix as in the filename. So we have to
 * filter the found files.
 *
 * @param array $array "classname" => "path/to/classfile.php"
 * @param string $prefix
 * @return array
 */
function filterByPrefix( $array, $prefix )
{
    $result = array(  );
    foreach( $array as $className => $file )
    {
        if ( preg_match( "/^([a-z]*)".$prefix."([A-Z][a-z0-9]*)?/", $className ) )
        {
            $result[$className]=$file;
        }
    }
    return $result;
}

/**
 * Returns the class/interface-name from a given sourcecode.
 *
 * Searches only the first 100 tokens to avoid including scripts with classes.
 *
 * @param string $source
 * @return false/string
 */
function getClassNamesFromSource( $source )
{
    $classNames = array();
    $tokens = token_get_all( $source );

    //@todo this could be made more elegant by changing the following do loop
    //in a while loop.
    if( 0 === count( $tokens ) )
    {
        return false;
    }

    $i = 0;
    do
    {
        $token = $tokens[$i++];
        if ( $token[0]==T_CLASS || $token[0]==T_INTERFACE )
        {
            $classNames[] = $tokens[$i+1][1];
        }
    } while( array_key_exists( $i, $tokens ));

    return $classNames;
}

class DirectoryIteratorFilter extends FilterIterator
{
    public function __construct( $path )
    {
        parent::__construct(
                new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator( $path )));
    }

    public function accept()
    {
        //$fileInfo = $this->getInnerIterator()->getSubIterator()->getFileInfo();

        $current = parent::current();

        // Don't crawl hidden dirs or files
        $is_hidden_test = preg_match( '/(^|[\/])\.[^\/.]/', $current );
        if ( $is_hidden_test === FALSE or $is_hidden_test > 0 )
        {
            return FALSE;
        }

        // Accept only .php files
        if ( substr( $current, -4 )!=='.php' )
        {
            return FALSE;
        }
        return TRUE;
    }
}


?>
