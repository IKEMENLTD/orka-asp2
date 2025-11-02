<?php

	/*		テーブル定義と関連テンプレート		*/
	class TableSetting
	{
		private static $TableSettings    = Array();
		private static $TemplateSettings = Array();
		private static $Presets          = Array();

		/*		コンストラクタ		*/
		/*		p0 : 設定配列		*/
		function __construct( $_settings )
		{
			foreach( $_settings as $key => $value )
			{
				switch( $key )
				{
					case 'name' :
					case 'userData' :
					case 'noHTML' :
					case 'idHeader' :
					case 'idLength' :
					case 'myIndex' :
						$tableSettings[ $key ] = $value;
						break;

					default :
						$templateSettings[ $key ] = $value;
						break;
				}
			}

			if( $tableSettings[ 'myIndex' ] )
				$templateSettings[ 'index' ] = 'all-15-' . $tableSettings[ 'name' ];

			$this->addTableSetting( $tableSettings );
			$this->addTemplateSetting( $tableSettings[ 'name' ] , $templateSettings );
		}

		/*		テーブル定義を追加する		*/
		/*		p0 : 設定配列		*/
		private function addTableSetting( $_settings )
		{
			$result[ '$EDIT_TYPE' ]                            = $_settings[ 'name' ];
			$result[ '$TABLE_NAME[]' ]                         = '$EDIT_TYPE';
			$result[ '$THIS_TABLE_IS_USERDATA[ $EDIT_TYPE ]' ] = ( $_settings[ 'userData' ] ? $_settings[ 'userData' ] : 'false' );
			$result[ '$THIS_TABLE_IS_NOHTML[ $EDIT_TYPE ]' ]   = ( $_settings[ 'noHTML' ] ? $_settings[ 'noHTML' ] : 'false' );
			$result[ '$LOGIN_KEY_COLUM[ $EDIT_TYPE ]' ]        = "'" . 'mail' . "'";
			$result[ '$LOGIN_PASSWD_COLUM[ $EDIT_TYPE ]' ]     = "'" . 'pass' . "'";
			$result[ '$LOGIN_PASSWD_COLUM2[ $EDIT_TYPE ]' ]    = "'" . 'pass_confirm' . "'";
			$result[ '$LST[ $EDIT_TYPE ]' ]                    = "'" . './lst/' . strtolower( $_settings[ 'name' ] ) . '.csv' . "'";
			$result[ '$TDB[ $EDIT_TYPE ]' ]                    = "'" . './tdb/' . strtolower( $_settings[ 'name' ] ) . '.csv' . "'";

			if( !( isset( $_settings[ 'idHeader' ] ) && !$_settings[ 'idHeader' ] ) )
				$result[ '$ID_HEADER[ $EDIT_TYPE ]' ] = "'" . $_settings[ 'idHeader' ] . "'";

			if( !isset( $_settings[ 'idLength' ] ) )
				$result[ '$ID_LENGTH[ $EDIT_TYPE ]' ] = 8;
			else if( $_settings[ 'idLength' ] )
				$result[ '$ID_LENGTH[ $EDIT_TYPE ]' ] = $_settings[ 'idLength' ];

			self::$TableSettings[] = $result;
		}

		/*		テンプレート定義を追加する		*/
		/*		p0 : ターゲット名		*/
		/*		p1 : 設定配列		*/
		private function addTemplateSetting( $_target , $_settings )
		{
			if( !is_array( $_settings ) )
				return;

			$this->registerd = Array();

			foreach( $_settings as $key => $values )
			{
				foreach( explode( ';' , $values ) as $value )
				{
					$setting = $this->getUserSetting( $value );
					$presets = $this->getPreset( $key , $setting[ 'user' ] );

					foreach( $presets as $label => $file )
					{
						$this->addPreset( $label , $setting[ 'user' ] , ( $setting[ 'dir' ] ? $setting[ 'dir' ] : $_target ) . '/' . $file . $setting[ 'suffix' ] . '.html' );

						$template                = Array();
						$template[ 'shadow_id' ] = '';
						$template[ 'deleted' ]   = '';
						$template[ 'id' ]        = '';
						$template[ 'user' ]      = $setting[ 'user' ];
						$template[ 'target' ]    = $_target;
						$template[ 'activate' ]  = $setting[ 'activate' ];
						$template[ 'owner' ]     = $setting[ 'owner' ];
						$template[ 'label' ]     = $label;

						$fileName = $file;

						if( $setting[ 'format' ] )
							$fileName = str_replace( '*' , $file , $setting[ 'format' ] );

						$template[ 'file' ]      = ( $setting[ 'dir' ] ? $setting[ 'dir' ] : $_target ) . '/' . $fileName . '.html';
						$template[ 'regist' ]    = 0;

						if( 'HEAD_DESIGN' == $label || 'FOOT_DESIGN' == $label || 'TOP_PAGE_DESIGN' == $label )
							$template[ 'target' ] = '';

						if( 0 === strpos( $key , '/' ) )
						{
							$match = false;

							for( $i = 0 ; $i < count( self::$TemplateSettings ) ; $i++ )
							{
								if( self::$TemplateSettings[ $i ][ 'user' ] == $template[ 'user' ] && self::$TemplateSettings[ $i ][ 'target' ] == $template[ 'target' ] && self::$TemplateSettings[ $i ][ 'owner' ] == $template[ 'owner' ] && self::$TemplateSettings[ $i ][ 'activate' ] == $template[ 'activate' ] && self::$TemplateSettings[ $i ][ 'label' ] == $template[ 'label' ] )
								{
									self::$TemplateSettings[ $i ] = $template;
									$match = true;
									break;
								}
							}

							if( !$match )
								self::$TemplateSettings[] = $template;
						}
						else
							self::$TemplateSettings[] = $template;
					}
				}
			}
		}

		/*		conf文字列を取得		*/
		/*		定型処理 : 引数はありません		*/
		static function GetConfString()
		{
			$headers = Array();

			foreach( self::$TableSettings as $setting )
			{
				if( $setting[ '$ID_HEADER[ $EDIT_TYPE ]' ] )
					$headers[] = $setting[ '$ID_HEADER[ $EDIT_TYPE ]' ];
			}

			$result = '/**********          テーブル定義          **********/' . "\n\n";

			foreach( self::$TableSettings as $setting )
			{
				if( !isset( $setting[ '$ID_HEADER[ $EDIT_TYPE ]' ] ) )
					$setting[ '$ID_HEADER[ $EDIT_TYPE ]' ] = "''";
				else if( !$setting[ '$ID_HEADER[ $EDIT_TYPE ]' ] )
				{
					for( $i = 1 ; $i < strlen( $setting[ '$EDIT_TYPE' ] ) ; $i++ )
					{
						$header = "'" . strtoupper( substr( $setting[ '$EDIT_TYPE' ] , 0 , $i ) ) . "'";

						if( in_array( $header , $headers ) )
							continue;

						$setting[ '$ID_HEADER[ $EDIT_TYPE ]' ] = $header;
						$headers[]                             = $header;
						break;
					}
				}

				if( !isset( $setting[ '$ID_LENGTH[ $EDIT_TYPE ]' ] ) )
					$setting[ '$ID_LENGTH[ $EDIT_TYPE ]' ] = '0';

				if( 'false' == $setting[ '$THIS_TABLE_IS_USERDATA[ $EDIT_TYPE ]' ] )
				{
					$setting[ '$LOGIN_KEY_COLUM[ $EDIT_TYPE ]' ]     = 'null';
					$setting[ '$LOGIN_PASSWD_COLUM[ $EDIT_TYPE ]' ]  = 'null';
					$setting[ '$LOGIN_PASSWD_COLUM2[ $EDIT_TYPE ]' ] = 'null';
				}

				$result .= "\n" . '/**********          ' . $setting[ '$EDIT_TYPE' ] . 'の定義          **********/' . "\n\n";

				$setting[ '$EDIT_TYPE' ] = "'" . $setting[ '$EDIT_TYPE' ] . "'";

				foreach( $setting as $key => $value )
				{
					if( $maxLength < strlen( $key ) )
						$maxLength = strlen( $key );
				}

				foreach( $setting as $key => $value )
				{
					$result .= "\t" . $key;

					for( $i = strlen( $key ) ; $i < $maxLength ; $i++ )
						$result .= ' ';

					$result .= ' = ' . $value . ';' . "\n";
				}

				$result .= "\n";
			}

			return $result;
		}

		/*		テーブル設定配列を取得		*/
		/*		定型処理 : 引数はありません		*/
		static function GetTables()
		{
			return self::$TableSettings;
		}

		/*		テンプレート設定配列を取得		*/
		/*		定型処理 : 引数はありません		*/
		static function GetTemplates()
		{
			return self::$TemplateSettings;
		}

		/*		テンプレートラベルとファイル名のプリセットを取得		*/
		/*		p0 : プリセット名		*/
		/*		p1 : ユーザー名		*/
		private function getPreset( $_name , $_user )
		{
			switch( $_name )
			{
				case 'headfoot' :
					return Array
					(
						'HEAD_DESIGN' => 'Head' ,
						'FOOT_DESIGN' => 'Foot' ,
					);

				case 'index' :
					return Array
					(
						'TOP_PAGE_DESIGN'  => 'Index' ,
					);

				case 'regist' :
					$result = Array
					(
						'REGIST_FORM_PAGE_DESIGN'  => 'Regist' ,
						'REGIST_CHECK_PAGE_DESIGN' => 'RegistCheck' ,
						'REGIST_COMP_PAGE_DESIGN'  => 'RegistComp' ,
					);

					if( !$this->registerd[ $_user ] )
					{
						$result[ 'REGIST_ERROR_DESIGN' ] = 'RegistFaled';
						$this->registerd[ $_user ] = true;
					}

					return $result;

				case '/regist' :
					return Array( 'REGIST_FORM_PAGE_DESIGN'  => 'Regist' );

				case '/registCheck' :
					return Array( 'REGIST_CHECK_PAGE_DESIGN'  => 'RegistCheck' );

				case '/registComp' :
					return Array( 'REGIST_COMP_PAGE_DESIGN'  => 'RegistComp' );

				case '/registError' :
					return Array( 'REGIST_ERROR_DESIGN'  => 'RegistFaled' );

				case 'edit' :
					$result = Array
					(
						'EDIT_FORM_PAGE_DESIGN'  => 'Edit' ,
						'EDIT_CHECK_PAGE_DESIGN' => 'EditCheck' ,
						'EDIT_COMP_PAGE_DESIGN'  => 'EditComp' ,
					);

					if( !$this->registerd[ $_user ] )
					{
						$result[ 'REGIST_ERROR_DESIGN' ] = 'RegistFaled';
						$this->registerd[ $_user ] = true;
					}

					return $result;

				case '/edit' :
					return Array( 'EDIT_FORM_PAGE_DESIGN'  => 'Edit' );

				case '/editCheck' :
					return Array( 'EDIT_CHECK_PAGE_DESIGN'  => 'EditCheck' );

				case '/editComp' :
					return Array( 'EDIT_COMP_PAGE_DESIGN'  => 'EditComp' );

				case 'delete' :
					return Array
					(
						'DELETE_CHECK_PAGE_DESIGN' => 'DeleteCheck' ,
						'DELETE_COMP_PAGE_DESIGN'  => 'DeleteComp' ,
					);

				case '/deleteCheck' :
					return Array( 'DELETE_CHECK_PAGE_DESIGN'  => 'DeleteCheck' );

				case '/deleteComp' :
					return Array( 'DELETE_COMP_PAGE_DESIGN'  => 'DeleteComp' );

				case 'search' :
					return Array
					(
						'SEARCH_FORM_PAGE_DESIGN' => 'Search' ,
						'SEARCH_RESULT_DESIGN'   => 'SearchResultFormat' ,
						'SEARCH_LIST_PAGE_DESIGN' => 'List' ,
						'SEARCH_NOT_FOUND_DESIGN' => 'SearchFaled'
					);

				case '/search' :
					return Array( 'SEARCH_FORM_PAGE_DESIGN' => 'Search' );

				case '/searchResult' :
					return Array( 'SEARCH_RESULT_DESIGN' => 'SearchResultFormat' );

				case '/searchList' :
					return Array( 'SEARCH_LIST_PAGE_DESIGN' => 'List' );

				case '/searchFaled' :
					return Array( 'SEARCH_NOT_FOUND_DESIGN' => 'SearchFaled' );

				case 'info' :
					return Array
					(
						'INFO_PAGE_DESIGN' => 'Info' ,
					);
			}
		}

		/*		ユーザー権限情報を取得		*/
		/*		p0 : データ		*/
		private function getUserSetting( $_data )
		{
			$split = explode( '-' , $_data , 3 );

			$result[ 'owner' ]    = ( 'self' == $split[ 0 ] ? 1 : ( 'other' == $split[ 0 ] ? 2 : 3 ) );
			$result[ 'activate' ] = $split[ 1 ];

			if( preg_match( '/^(.*)\((.*)\)$/' , $split[ 2 ] , $match ) )
			{
				$result[ 'user' ]   = $match[ 1 ];
				$result[ 'format' ] = $match[ 2 ];

				if( preg_match( '/^(.*)\/(.*)$/' , $result[ 'format' ] , $match ) )
				{
					$result[ 'dir' ]    = $match[ 1 ];
					$result[ 'format' ] = $match[ 2 ];
				}
			}
			else
				$result[ 'user' ] = $split[ 2 ];

			return $result;
		}

		/*		テンプレートファイルの内容プリセットを取得		*/
		static function getPresets()
		{
			return self::$Presets;
		}

		/*		テンプレートファイルの内容プリセットを登録		*/
		private function addPreset( $_label , $_user , $_file )
		{
			switch( $_label )
			{
				case 'REGIST_FORM_PAGE_DESIGN' :
				case 'EDIT_FORM_PAGE_DESIGN' :
					self::$Presets[ $_file ][ 'user' ] = $_user;
					self::$Presets[ $_file ][ 'type' ] = 'regist';
					break;

				case 'REGIST_CHECK_PAGE_DESIGN' :
				case 'EDIT_CHECK_PAGE_DESIGN' :
				case 'DELETE_CHECK_PAGE_DESIGN' :
				case 'INFO_PAGE_DESIGN' :
					self::$Presets[ $_file ][ 'user' ] = $_user;
					self::$Presets[ $_file ][ 'type' ] = 'info';
					break;

				default :
					break;
			}
		}
	}
?>