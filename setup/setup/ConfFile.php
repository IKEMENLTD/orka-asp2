<?php

	/*		Ý’èo—Í		*/
	class ConfFile
	{
		private static $Templates = Array();

		/*		conf.php‚Ìo—Í		*/
		/*		’èŒ^ˆ— : ˆø”‚Í‚ ‚è‚Ü‚¹‚ñ		*/
		static function Save()
		{
			$handle = fopen( 'custom/conf.php' , 'wb' );
			fputs( $handle , '<?php' . "\n" );
			fputs( $handle , ConfSetting::GetConfString() );
			fputs( $handle , TableSetting::GetConfString() );
			fputs( $handle , "\n" . '?>' );
			fclose( $handle );

			$settings = TableSetting::GetTables();

			foreach( $settings as $setting )
			{
				$lst = $setting[ '$LST[ $EDIT_TYPE ]' ];
				$tdb = $setting[ '$TDB[ $EDIT_TYPE ]' ];

				$lst = preg_replace( '/^[\'"](.*)[\'"]$/' , '$1' , $lst );
				$tdb = preg_replace( '/^[\'"](.*)[\'"]$/' , '$1' , $tdb );

				if( !is_file( $lst ) )
				{
					$handle = fopen( $lst , 'wb' );
					fputs( $handle , 'id,string' . "\n" );
					fputs( $handle , 'regist,timestamp' . "\n" );
					fclose( $handle );
				}

				if( !is_file( $tdb ) )
				{
					$handle = fopen( $tdb , 'wb' );
					fclose( $handle );
				}
			}
		}
	}

?>