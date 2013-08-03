<?php

require_once 'abstract.php';

/**
 * Class AnattaDesign_Shell_Translate
 */
class AnattaDesign_Shell_Translate extends Mage_Shell_Abstract {

	/**
	 * @var array
	 */
	private $strings = array();

	/**
	 * @param $string string
	 */
	private function addString( $string ) {
		if( !in_array( $string, $this->strings ) )
			$this->strings[] = $string;
	}

	/**
	 * Run script
	 */
	public function run() {
		// if module doesn't exist show error
		$module = $this->getArg( 'module' ) ? $this->getArg( 'module' ) : $this->getArg( 'm' );
		$modules = array_keys( (array)Mage::getConfig()->getNode( 'modules' )->children() );
		if( !in_array( $module, $modules ) )
			die( 'Error: Module doesn\'t exist' . PHP_EOL );

		// traverse through all module files in code directory for translatable strings
		$dir = Mage::getModuleDir( '', $module );
		/* @var $dir Varien_Directory_Collection */
		$dir = Varien_Directory_Factory::getFactory( $dir );
		$dir->walk( array( $this, 'dir' ) );
		unset( $dir );

		// traverse through all module files in design/frontend/base/default/template directory for translatable strings
		$dir = Mage::getBaseDir( 'design' ) . DS . 'frontend' . DS . 'base' . DS . 'default' . DS . 'template' . DS;
		foreach( explode( '_', strtolower( $module ) ) as $path )
			$dir = $dir . $path . DS;
		if( file_exists( $dir ) ) {
			/* @var $dir Varien_Directory_Collection */
			$dir = Varien_Directory_Factory::getFactory( $dir );
			$dir->walk( array( $this, 'dir' ) );
			unset( $dir );
		}

		// traverse through all module files in design/adminhtml/default/default/template directory for translatable strings
		$dir = Mage::getBaseDir( 'design' ) . DS . 'adminhtml' . DS . 'default' . DS . 'default' . DS . 'template' . DS;
		foreach( explode( '_', strtolower( $module ) ) as $path )
			$dir = $dir . $path . DS;
		if( file_exists( $dir ) ) {
			/* @var $dir Varien_Directory_Collection */
			$dir = Varien_Directory_Factory::getFactory( $dir );
			$dir->walk( array( $this, 'dir' ) );
			unset( $dir );
		}

		// traverse through all module files in design/install/default/default/template directory for translatable strings
		$dir = Mage::getBaseDir( 'design' ) . DS . 'install' . DS . 'default' . DS . 'default' . DS . 'template' . DS;
		foreach( explode( '_', strtolower( $module ) ) as $path )
			$dir = $dir . $path . DS;
		if( file_exists( $dir ) ) {
			/* @var $dir Varien_Directory_Collection */
			$dir = Varien_Directory_Factory::getFactory( $dir );
			$dir->walk( array( $this, 'dir' ) );
			unset( $dir );
		}

		// go through the config.xml to find translatable strings in defined layouts
		$dir = Mage::getModuleDir( 'etc', $module );
		$layoutfiles = array();
		/* @var $dir Varien_File_Object */
		$dir = Varien_Directory_Factory::getFactory( $dir . DS . 'config.xml' );
		$xml = new Varien_Simplexml_Config( (string)$dir );
		$xml = $xml->getNode();
		foreach( array( 'global', 'frontend', 'adminhtml', 'install' ) as $scope ) {
			if( isset( $xml->$scope ) && isset( $xml->$scope->layout ) && isset( $xml->$scope->layout->updates ) ) {
				$layoutfiles[$scope] = array();
				foreach( $xml->$scope->layout->updates->children() as $child ) {
					$file = $child->file;
					$file = Mage::getBaseDir( 'design' ) . DS . $scope . DS . 'base' . DS . 'default' . DS . 'layout' . DS . $file;
					$file = Varien_Directory_Factory::getFactory( $file );
					$this->processXML( $file );
					$layoutfiles[$scope][] = $file;
				}
			}

			if( isset( $xml->$scope ) )
				foreach( $xml->$scope->children() as $child )
					if( isset( $child->layouts ) )
						foreach( $child->layouts->children() as $layout )
							$this->_processtemplate( $layout->template, $scope );
		}
		unset( $dir );

		foreach( $layoutfiles as $scope => $files )
			foreach( $files as $file )
				$this->processLayout( $file, $scope );

		// create translation files from all the collected strings
		$dir = Varien_Directory_Factory::getFactory( Mage::getBaseDir( 'locale' ) );
		/* @var $dir Varien_Directory_Collection */
		foreach( $dir->getItems() as $item )
			$this->generateCSV( $item, $module );
		unset( $dir );
	}

	/**
	 * Process the directory & search each file for translation strings
	 *
	 * @param $dir Varien_Directory_Collection|Varien_File_Object
	 *
	 * @return AnattaDesign_Shell_Translate
	 */
	public function dir( $dir ) {
		if( $dir instanceof Varien_Directory_Collection )
			return $dir->walk( array( $this, 'dir' ) );

		$function = 'process' . strtoupper( $dir->getExtension() );
		if( is_callable( array( $this, $function ) ) )
			$this->$function( $dir );

		unset( $dir );
		return $this;
	}

	/**
	 * Generates a CSV file containing all the translation strings
	 *
	 * @param        $locale Varien_Directory_Collection
	 * @param string $module
	 *
	 * @internal param string $file
	 */
	public function generateCSV( $locale, $module = "test" ) {
		if( !( $locale instanceof Varien_Directory_Collection ) )
			return;

		$strings = array_unique( $this->strings );
		$file = $locale->getPath() . DS . $module . '.csv';

		if( file_exists( $file ) && !is_readable( $file ) ) {
			echo "Existing file $file is not readable\n";
			return;
		}

		if( 'en_US' === $locale->getDirName() && 0 === strpos( $module, 'Mage_' ) ) {
			echo "Skipping en_US translation for magento core module\n";
			return;
		}

		foreach( $strings as &$string ) {
			$string = str_replace( '"', '\"', $string );
		}
		unset( $string );

		$translations = file( $file, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES );
		$translations = array_unique( $translations );
		foreach( $translations as &$t ) {
			preg_match( '/.+(\",\").+/', $t, $matches, PREG_OFFSET_CAPTURE );
			array_shift( $matches );
			foreach( $matches as $k => $v ) {
				if( '\\' === $t{$v[1] - 1} || ( '"' === $t{$v[1] - 1} && '"' !== $t{$v[1] - 2} ) )
					unset( $matches[$k] );
			}

			if( !count( $matches ) )
				continue;

			$t = array( substr( $t, 1, $matches[0][1] - 1 ), substr( $t, $matches[0][1] + 3, -1 ) );
			$t[0] = str_replace( '""', '\"', $t[0] );
			$t[0] = str_replace( '"', '\"', $t[0] );
			$t[0] = str_replace( '\\\\', '\\', $t[0] );
			$t[1] = str_replace( '""', '\"', $t[1] );
			$t[1] = str_replace( '"', '\"', $t[1] );
			$t[1] = str_replace( '\\\\', '\\', $t[1] );

			if( in_array( $t[0], $strings ) )
				$strings = array_diff( $strings, array( $t[0] ) );
		}
		unset( $t );

		foreach( $strings as $s )
			$translations[] = array( $s, $s );

		$func = 'if(($a[0]!==$a[1]&&$b[0]!==$b[1])||($a[0]===$a[1]&&$b[0]===$b[1]))return strcmp($a[0], $b[0]);elseif($b[0]===$b[1])return 1;else return -1;';
		usort( $translations, create_function( '$a, $b', $func ) );

		ob_start();
		foreach( $translations as $string )
			echo "\"$string[0]\",\"$string[1]\"\n";
		file_put_contents( $file, str_replace( '\"', '""', ob_get_clean() ) );
	}

	/**
	 * @param $file Varien_File_Object
	 *
	 * @return AnattaDesign_Shell_Translate
	 */
	public function processPHP( $file ) {
		if( !( $file instanceof Varien_File_Object ) )
			return $this;

		$this->_translateFunction( $file );

		$source = file_get_contents( $file );
		$tokens = token_get_all( $source );

		reset( $tokens );
		while( $token = next( $tokens ) ) {
			// $token is equivalent to array( <token ID> , <actual token> , <line number> )

			if( is_string( $token ) || T_OBJECT_OPERATOR !== $token[0] )
				continue;

			do {
				$token = next( $tokens );
			} while( is_array( $token ) && T_WHITESPACE === $token[0] );

			if( is_string( $token ) || T_STRING !== $token[0] || 'setTemplate' !== $token[1] )
				continue;

			$line = $token[2];

			do {
				$token = next( $tokens );
			} while( is_array( $token ) && T_WHITESPACE === $token[0] );

			if( '(' !== $token )
				continue;

			do {
				$token = next( $tokens );
			} while( is_array( $token ) && T_WHITESPACE === $token[0] );

			if( is_string( $token ) || T_CONSTANT_ENCAPSED_STRING !== $token[0] ) {
				echo "Invalid template string detected in $file at line $line\n";
				continue;
			}

			$text = substr( $token[1], 1, strlen( $token[1] ) - 2 );

			do {
				$token = next( $tokens );
			} while( is_array( $token ) && T_WHITESPACE === $token[0] );

			if( ',' !== $token && ')' !== $token ) {
				echo "Invalid translation string detected in $file at line $line\n";
				continue;
			}

			$this->_processtemplate( $text );
		}

		return $this;
	}

	protected function _translateFunction( $file ) {
		if( !( $file instanceof Varien_File_Object ) )
			return $this;

		$source = file_get_contents( $file );
		$tokens = token_get_all( $source );

		while( $token = next( $tokens ) ) {
			// $token is equivalent to array( <token ID> , <actual token> , <line number> )

			if( is_string( $token ) || T_OBJECT_OPERATOR !== $token[0] )
				continue;

			do {
				$token = next( $tokens );
			} while( is_array( $token ) && T_WHITESPACE === $token[0] );

			if( is_string( $token ) || T_STRING !== $token[0] || '__' !== $token[1] )
				continue;

			$line = $token[2];

			do {
				$token = next( $tokens );
			} while( is_array( $token ) && T_WHITESPACE === $token[0] );

			if( '(' !== $token )
				continue;

			do {
				$token = next( $tokens );
			} while( is_array( $token ) && T_WHITESPACE === $token[0] );

			if( is_string( $token ) || T_CONSTANT_ENCAPSED_STRING !== $token[0] ) {
				echo "Invalid translation string detected in $file at line $line\n";
				continue;
			}

			$text = substr( $token[1], 1, strlen( $token[1] ) - 2 );

			do {
				$token = next( $tokens );
			} while( is_array( $token ) && T_WHITESPACE === $token[0] );

			if( ',' !== $token && ')' !== $token ) {
				echo "Invalid translation string detected in $file at line $line\n";
				continue;
			}

			$this->addString( $text );
		}

		return $this;
	}

	/**
	 * @param $file Varien_File_Object
	 *
	 * @return AnattaDesign_Shell_Translate
	 */
	public function processXML( $file ) {
		if( !( $file instanceof Varien_File_Object ) )
			return $this;

		$xml = new Varien_Simplexml_Config( (string)$file );

		$this->_xmlelement( $xml->getNode() );

		return $this;
	}

	/**
	 * @param $file Varien_File_Object
	 * @param $type string
	 *
	 * @return AnattaDesign_Shell_Translate
	 */
	public function processLayout( $file, $type ) {
		if( !( $file instanceof Varien_File_Object ) )
			return $this;

		$xml = new Varien_Simplexml_Config( (string)$file );

		$this->_searchtemplates( $xml->getNode(), $type );

		return $this;
	}

	/**
	 * @param $file Varien_File_Object
	 *
	 * @return AnattaDesign_Shell_Translate
	 */
	public function processPHTML( $file ) {
		$this->_translateFunction( $file );
		return $this;
	}

	/**
	 * Process an xml Element & check for available translation strings
	 *
	 * @param $xml Varien_Simplexml_Element
	 */
	protected function _xmlelement( $xml ) {
		if( !$xml->hasChildren() )
			return;

		if( $tags = $xml->getAttribute( 'translate' ) ) {
			$tags = explode( ' ', $tags );
			foreach( $tags as $tag ) {
				$string = trim( $xml->$tag );
				if( false !== strpos( $string, '<![CDATA[' ) )
					$string = substr( $string, 9, strlen( $string ) - 11 );
				$this->addString( $string );
			}
		}

		foreach( $xml->children() as $child )
			$this->_xmlelement( $child );
	}

	/**
	 * Process an xml Element & check for available templates
	 *
	 * @param $xml  Varien_Simplexml_Element
	 * @param $type string
	 */
	protected function _searchtemplates( $xml, $type ) {
		if( $template = $xml->getAttribute( 'template' ) )
			$this->_processtemplate( $template, $type );

		if( !$xml->hasChildren() )
			return;

		if( isset( $xml->template ) && is_string( $xml->template ) )
			$this->_processtemplate( $xml->template, $type );

		foreach( $xml->children() as $child )
			$this->_searchtemplates( $child, $type );
	}

	/**
	 * Search for the template name in all themes & process all of those files
	 *
	 * @param      $template string
	 * @param null $types
	 *
	 * @internal param string $type
	 */
	protected function _processtemplate( $template, $types = null ) {
		if( !$types )
			$types = array( 'frontend', 'adminhtml', 'install' );
		$types = (array)$types;

		foreach( $types as $type ) {
			$dir = array_filter( array_diff( scandir( Mage::getBaseDir( 'design' ) . DS . $type ), array( '..', '.' ) ), 'is_dir' );
			foreach( $dir as $package ) {
				$themes = array_filter( array_diff( scandir( Mage::getBaseDir( 'design' ) . DS . $type . DS . $package ), array( '..', '.' ) ), 'is_dir' );
				foreach( $themes as &$theme )
					$theme = Mage::getBaseDir( 'design' ) . DS . $type . DS . $package . DS . $theme;
				unset( $theme );
				foreach( $themes as $theme ) {
					if( is_dir( $theme . DS . 'template' ) )
						if( is_readable( $theme . DS . 'template' . DS . $template ) ) {
							$file = Varien_Directory_Factory::getFactory( $theme . DS . 'template' . DS . $template );
							if( $file instanceof Varien_File_Object )
								$this->processPHTML( $file );
							unset( $file );
						}
				}
			}
		}
	}

	/**
	 * Check is show usage help
	 *
	 */
	protected function _showHelp() {
		parent::_showHelp();

		// if module is not specified show help
		if( !$this->getArg( 'module' ) && !$this->getArg( 'm' ) ) {
			die( $this->usageHelp() );
		}
	}

	/**
	 * Retrieve Usage Help Message
	 *
	 * @return string
	 */
	public function usageHelp() {
		return <<<USAGE
Usage:  php -f translate.php -- [options]

  --module -m   Module Name
  -h            Short alias for help
  help          This help

USAGE;

	}

}

$shell = new AnattaDesign_Shell_Translate();
$shell->run();