<?php

	/*		設定ファイル読み込み・解析		*/
	class Option
	{
		var $fileName;
		var $handle;

		/*		コンストラクタ		*/
		/*		p0 : 設定記述ファイル		*/
		function __construct( $_fileName )
		{
			if( !is_file( $_fileName ) )
				die( 'ファイルが見つかりません : ' . $_fileName );

			$this->fileName = $_fileName;
			$this->handle   = fopen( $_fileName , 'rb' );

			if( !$this->handle )
				die( 'ファイルをオープンできません : ' . $_fileName );

			$this->load();
		}

		/*		ファイルを読み込む		*/
		/*		定型処理 : 引数はありません		*/
		private function load()
		{
			while( !feof( $this->handle ) )
			{
				$read = rtrim( fgets( $this->handle ) );
				$read = preg_replace( '/^[\t\s]*/' , '' , $read );
//				$read = preg_replace( '/\/\/.*$/' , '' , $read );
				$read = preg_replace( '/;[\t\s]*$/' , '' , $read );

				switch( $this->lineType( $read ) )
				{
					case 'beginTag' :
						$this->tagName = $this->getTagName( $read );
						break;

					case 'endTag' :
						$this->addSetting( $this->tagName );
						$this->tagName = null;
						break;

					case 'null' :
						if( $this->tagName )
							$this->addSetting( $this->tagName );
						break;

					default :
						if( $this->tagName )
							$this->stackSetting( $read );
						break;
				}
			}
		}

		/*		行の種類を判断する		*/
		/*		p0 : 読み込んだ行文字列		*/
		private function lineType( $_read )
		{
			if( preg_match( '/^<(\w*)>/' , $_read , $match ) )
				return 'beginTag';
			else if( preg_match( '/^<\\/(\w*)>/' , $_read , $match ) )
			{
				if( $this->tagName != $match[ 1 ] )
					die( '閉じタグが一致しません' );

				return 'endTag';
			}

			return ( $_read ? 'data' : 'null' );
		}

		/*		タグ名を取得		*/
		/*		p0 : タグ文字列		*/
		private function getTagName( $_read )
		{
			preg_match( '/^<(\w*)>/' , $_read , $match );
			return strtolower( $match[ 1 ] );
		}

		/*		設定を追加する		*/
		/*		p0 : 読み込んだ行文字列		*/
		private function stackSetting( $_read )
		{
			if( !$this->tagName )
				return;

			$this->settings[] = $_read;
		}

		/*		設定を追加する		*/
		/*		p0 : タグ名		*/
		private function addSetting( $_tagName )
		{
			if( 'csv' == $_tagName )
			{
				foreach( $this->settings as $value )
					$settings[] = explode( ',' , $value );
			}
			else
			{
				foreach( $this->settings as $value )
				{
					if( !preg_match( '/([^\s\t]*)[\s\t]*=[\s\t]*(.*)/' , $value , $match ) )
						die( '設定の書式が正しくありません : ' . $_tagName . ' : ' . $value );

					$settings[ $match[ 1 ] ] = $match[ 2 ];
				}
			}

			switch( $_tagName )
			{
				case 'conf' :
					new ConfSetting( $settings );
					break;

				case 'table' :
					new TableSetting( $settings );
					new PresetSetting( $settings );
					break;

				case 'label' :
					new LabelSetting( $settings );
					break;

				case 'csv' :
					new CSVSetting( $settings );
					break;
			}

			$this->settings = Array();
		}
	}

?>
