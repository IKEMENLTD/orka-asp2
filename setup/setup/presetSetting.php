<?php

	/*		プリセット情報		*/
	class PresetSetting
	{
		static private $Settings = Array();

		function __construct( $_settings )
		{
			$lst = './lst/' . $_settings[ 'name' ] . '.csv';

			if(  !is_file( $lst ) )
				return;

			$file = fopen( $lst , 'rb' );

			while( !feof( $file ) )
			{
				$read                = rtrim( fgets( $file ) );
				List( $key , $type ) = explode( ',' , $read , 3 );
				$setting[ 'column' ][ $key ] = $type;
			}

			fclose( $file );

			$setting[ 'name' ] = $_settings[ 'name' ];
			array_push( self::$Settings , $setting );
		}

		function getSettings()
		{
			return self::$Settings;
		}
	}
?>