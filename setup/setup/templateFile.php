<?php

	/*		テンプレート出力		*/
	class TemplateFile
	{
		private static $Templates = Array();

		/*		template.csvとhtmlの出力		*/
		/*		定型処理 : 引数はありません		*/
		static function Save()
		{
			foreach( tableSetting::GetTemplates() as $value )
				self::$Templates[] = $value;

			foreach( labelSetting::GetTemplates() as $value )
				self::$Templates[] = $value;

			foreach( csvSetting::GetTemplates() as $value )
				self::$Templates[] = $value;

			self::Sort();
			self::Index();
			self::HTML();

			$handle = fopen( 'tdb/template.csv' , 'wb' );

			foreach( self::$Templates as $file )
				fputs( $handle , implode( ',' , $file ) . "\n" );

			fclose( $handle );
		}

		/*		テンプレートを並べ替える		*/
		/*		定型処理 : 引数はありません		*/
		static private function Sort()
		{
			for( $i = 0 ; $i < count( self::$Templates ) ; $i++ )
			{
				$elem = self::$Templates[ $i ];
				$sort[ $elem[ 'user' ] ][ $elem[ 'target' ] ][ $elem[ 'label' ] ][ $elem[ 'activate' ] ][ $elem[ 'owner' ] ][] = $elem;
			}

			self::$Templates = Array();

			foreach( $sort as $user => $targets )
			{
				foreach( $targets as $target => $labels )
				{
					foreach( $labels as $label => $activates )
					{
						foreach( $activates as $activate => $owners )
						{
							foreach( $owners as $owner => $elems )
							{
								foreach( $elems as $elem )
									self::$Templates[] = $elem;
							}
						}
					}
				}
			}
		}

		/*		IDを割り当てる		*/
		/*		定型処理 : 引数はありません		*/
		static private function Index()
		{
			$id = 1;

			for( $i = 0 ; $i < count( self::$Templates ) ; $i++ )
			{
				self::$Templates[ $i ][ 'shadow_id' ] = $i;
				self::$Templates[ $i ][ 'id' ]        = sprintf( 'T%04d' , $i );
				self::$Templates[ $i ][ 'user' ]      = '/' . self::$Templates[ $i ][ 'user' ] . '/';
				$id++;
			}
		}

		/*		HTMLファイルを出力する		*/
		/*		定型処理 : 引数はありません		*/
		static private function HTML()
		{
			$settings = ConfSetting::GetConf();
			$path     = $settings[ '$template_path' ];
			$path     = preg_replace( '/^[\'"](.*)[\'"]$/' , '$1' , $path );

			foreach( self::$Templates as $template )
				self::CreateFile( $path , $template[ 'file' ] );
		}

		/*		ファイルを生成する		*/
		/*		定型処理 : 引数はありません		*/
		static private function CreateFile( $_path , $_file )
		{
			if( is_file( $_path . $_file ) )
				return;

			$paths = explode( '/' , $_path . $_file );
			array_pop( $paths );

			foreach( $paths as $read )
			{
				$path .= $read . '/';

				if( !is_file( $path ) )
					mkdir( $path );
			}

			$handle = fopen( $_path . $_file , 'wb' );

			$presets = TableSetting::getPresets();

			if( in_array( $_file , array_keys( $presets ) ) )
			{
				foreach( PresetSetting::getSettings() as $lst )
					if( $lst[ 'name' ] == $presets[ $_file ][ 'user' ] )
					{
						foreach( array_keys( $lst[ 'column' ] ) as $column )
						{
							if( !$column )
								continue;

							if( 'regist' == $presets[ $_file ][ 'type' ] )
								fputs( $handle , '<!--# form text ' . $column . ' #-->' . "\n" );
							else
								fputs( $handle , '<!--# value ' . $column . ' #-->' . "\n" );
						}
					}
			}

			fclose( $handle );
		}
	}

?>