<?php

	/*		conf.phpの設定項目		*/
	class ConfSetting
	{
		static private $Settings = Array
		(
			'$NOT_LOGIN_USER_TYPE'            => "'nobody'" ,
			'$NOT_HEADER_FOOTER_USER_TYPE'    => "'nothf'" ,
			'$LOGIN_KEY_FORM_NAME'            => "'mail'" ,
			'$LOGIN_PASSWD_FORM_NAME'         => "'passwd'" ,
			'$ADD_LOG'                        => 'true' ,
			'$UPDATE_LOG'                     => 'true' ,
			'$DELETE_LOG'                     => 'true' ,
			'$SESSION_NAME'                   => "'loginid'" ,
			'$COOKIE_NAME'                    => "'loginid'" ,
			'$ACTIVE_NONE'                    => '1' ,
			'$ACTIVE_ACTIVATE'                => '2' ,
			'$ACTIVE_ACCEPT'                  => '4' ,
			'$ACTIVE_DENY'                    => '8' ,
			'$ACTIVE_ALL'                     => '15' ,
			'$template_path'                  => "'./template/pc/'" ,
			'$system_path'                    => "'./custom/system/'" ,
			'$FORM_TAG_DRAW_FLAG'             => "'variable'" ,
			'$DB_LOG_FILE'                    => "'./logs/dbaccess.log'" ,
			'$COOKIE_PATH'                    => "'/'" ,
			'$terminal_type'                  => '0' ,
			'$sid'                            => "''",
			'$js_file_paths["all"]["jquery"]' => "'./js/jquery.js'"
		);

		static private $Groups = Array
		(
			'$NOT_LOGIN_USER_TYPE'            => 1 ,
			'$NOT_HEADER_FOOTER_USER_TYPE'    => 1 ,
			'$LOGIN_KEY_FORM_NAME'            => 2 ,
			'$LOGIN_PASSWD_FORM_NAME'         => 2 ,
			'$ADD_LOG'                        => 3 ,
			'$UPDATE_LOG'                     => 3 ,
			'$DELETE_LOG'                     => 3 ,
			'$SESSION_NAME'                   => 4 ,
			'$COOKIE_NAME'                    => 4 ,
			'$ACTIVE_NONE'                    => 5 ,
			'$ACTIVE_ACTIVATE'                => 5 ,
			'$ACTIVE_ACCEPT'                  => 5 ,
			'$ACTIVE_DENY'                    => 5 ,
			'$ACTIVE_ALL'                     => 5 ,
			'$template_path'                  => 6 ,
			'$system_path'                    => 6 ,
			'$FORM_TAG_DRAW_FLAG'             => 7 ,
			'$DB_LOG_FILE'                    => 7 ,
			'$COOKIE_PATH'                    => 7 ,
			'$terminal_type'                  => 8 ,
			'$sid'                            => 8 ,
			'$js_file_paths["all"]["jquery"]' => 9
		);

		/*		コンストラクタ		*/
		/*		p0 : 設定配列		*/
		function __construct( $_settings )
		{
			foreach( $_settings as $key => $value )
				self::$Settings[ $key ] = $value;
		}

		/*		設定値を取得		*/
		/*		定型処理 : 引数はありません		*/
		static function GetConf()
		{
			return self::$Settings;
		}

		/*		conf文字列を取得		*/
		/*		定型処理 : 引数はありません		*/
		static function GetConfString()
		{
			$result = '/**********          基本設定          **********/' . "\n";

			foreach( self::$Settings as $key => $value )
			{
				if( $maxLength < strlen( $key ) )
					$maxLength = strlen( $key );
			}

			foreach( self::$Settings as $key => $value )
			{
				if( $prevGroup != self::$Groups[ $key ] )
				{
					$result .= "\n";
					$prevGroup = self::$Groups[ $key ];
				}

				$result .= "\t" . $key;

				for( $i = strlen( $key ) ; $i < $maxLength ; $i++ )
					$result .= ' ';

				$result .= ' = ' . $value . ';' . "\n";
			}

			$result .= "\n";

			$result .= "\t" . 'include_once "./custom/extends/sqlConf.php";' . "\n";
			$result .= "\t" . 'include_once "./custom/extends/mobileConf.php";' . "\n";
			$result .= '//' . "\t" . 'include_once "./include/extends/";' . "\n";
			$result .= '//' . "\t" . 'include_once "./include/extends/MobileUtil.php";' . "\n";

			$result .= "\n";

			return $result;
		}
	}
?>