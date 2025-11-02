<?php

	/*		その他のテンプレート		*/
	class CSVSetting
	{
		static private $Settings = Array();

		/*		コンストラクタ		*/
		/*		p0 : 設定配列		*/
		function __construct( $_settings )
		{
			foreach( $_settings as $setting )
			{
				$template                = Array();
				$template[ 'shadow_id' ] = array_shift( $setting );
				$template[ 'deleted' ]   = array_shift( $setting );
				$template[ 'id' ]        = array_shift( $setting );
				$template[ 'user' ]      = array_shift( $setting );
				$template[ 'target' ]    = array_shift( $setting );
				$template[ 'activate' ]  = array_shift( $setting );
				$template[ 'owner' ]     = array_shift( $setting );
				$template[ 'label' ]     = array_shift( $setting );
				$template[ 'file' ]      = array_shift( $setting );
				$template[ 'regist' ]    = array_shift( $setting );

				$template[ 'user' ] = preg_replace( '{/(.*)/}' , '$1' , $template[ 'user' ] );

				self::$Settings[] = $template;
			}
		}

		/*		テンプレート設定配列を取得		*/
		/*		定型処理 : 引数はありません		*/
		static function GetTemplates()
		{
			return self::$Settings;
		}
	}
?>