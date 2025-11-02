<?php

	/*		ラベルテンプレート		*/
	class LabelSetting
	{
		static private $Settings = Array();

		/*		コンストラクタ		*/
		/*		p0 : 設定配列		*/
		function __construct( $_settings )
		{
			$labels = array_keys( self::$Settings );

			foreach( $_settings as $label => $file )
			{
				if( isset( self::$Settings[ $label ] ) )
					die( 'ラベルが重複しています : ' . $label );

				self::$Settings[ $label ] = $file;
			}
		}

		/*		テンプレート設定配列を取得		*/
		/*		定型処理 : 引数はありません		*/
		static function GetTemplates()
		{
			foreach( self::$Settings as $label => $file )
			{
				$template                = Array();
				$template[ 'shadow_id' ] = '';
				$template[ 'deleted' ]   = '';
				$template[ 'id' ]        = '';
				$template[ 'user' ]      = '';
				$template[ 'target' ]    = '';
				$template[ 'activate' ]  = '15';
				$template[ 'owner' ]     = '3';
				$template[ 'label' ]     = $label;
				$template[ 'file' ]      = $file;
				$template[ 'regist' ]    = '0';

				$result[] = $template;
			}

			return $result;
		}
	}
?>