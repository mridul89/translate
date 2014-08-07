<?php
	require_once 'abstract.php';
	$path = '/Users/saurabhchaudhary/www/Awesome-Checkout/js';
	runAtOutsideDir( $path ) 
	function runAtOutsideDir( $path ) {
			$dirCode = Varien_Directory_Factory::getFactory( $path . DS . 'app' . DS . 'code' );
			$dirCode->walk( array( $this, 'dir' ) );

	}
	function dir( $dir ) {
		if( $dir instanceof Varien_Directory_Collection )
			return $dir->walk( array( $this, 'dir' ) );
		$this->processJs( $dir );

		unset( $dir );
		return $this;
	}

	function processJs( $file ) {
		$source = file_get_contents( $file->getPathname() );
		$startPos = 0;
		do{
			$start = strpos($source, 'AC.translate(',$startPos);
			$end = strpos($source, ')',$start);
			$string = substr($source, ($start + 13), $end - ($start + 13));
			echo str_replace("'", "", $string);
			echo "\n";
			$startPos = $end;
		}while( $start );
	}

