<?php

	//更新 : 2010/01/08

	set_time_limit( 0 );

	include_once "./custom/extends/debugConf.php";
	include 'custom/conf.php';
	include './module/module.inc';

	$PEAR_ZIP      = false;  //zip圧縮にPEAR::File_Archiveを使う
	$charcode_flag = true;
	$index_path										 = "./lst/indexs/";

	if( $charcode_flag )
	{
		ini_set( 'output_buffering'              , 'Off');               // 出力バッファリングを指定します
		ini_set( 'default_charset'               , 'Shift_JIS');         // デフォルトの文字コードを指定します
		ini_set( 'extension'                     , 'php_mbstring.dll' ); // マルチバイト文字列を有効にします。
		ini_set( 'mbstring.language'             , 'Japanese' );         // デフォルトを日本語に設定します。
		ini_set( 'mbstring.internal_encoding'    , 'SJIS' );             // 内部文字エンコーディングをSJISに設定します。
		ini_set( 'mbstring.http_input'           , 'auto' );             // HTTP入力文字エンコーディング変換をautoに設定します。
		ini_set( 'mbstring.http_output'          , 'SJIS' );             // HTTP出力文字エンコーディング変換をSJISに設定します。
		ini_set( 'mbstring.encoding_translation' , 'On'   );             // 内部文字エンコーディングへの変換を有効にします。
		ini_set( 'mbstring.detect_order'         , 'auto' );             // 文字コード検出をautoに設定します。
		ini_set( 'mbstring.substitute_character' , 'none' );             // 無効な文字を出力しない。
		mb_http_output( 'SJIS' );
		mb_internal_encoding( 'SJIS' );
	}

	session_start();

	/*		DBMSに接続		*/
	$SQL = SQLConnect::Create( $SQL_MASTER , $SQL_ID , $SQL_PASS , $DB_NAME , $SQL_SERVER , $SQL_PORT );

	if( !$SQL->connect )
	{
		header( 'Content-Type: text/html;charset=shift_jis' );
		Draw::Head( $SQL_MASTER );
		Draw::SQLConnectError();
		return;
	}

	/*		作成済みのテーブル一覧を取得しておく		*/
	$EXISTS = Array();

	if( 'SQLiteDatabase' == $SQL_MASTER )
		$result = $SQL->run( 'SELECT name FROM sqlite_master WHERE type="table" UNION ALL SELECT name FROM sqlite_temp_master WHERE type = "table" ORDER BY name' );
	else if( 'PostgreSQLDatabase' == $SQL_MASTER )
		$result = $SQL->run( 'SELECT tablename FROM pg_tables ORDER BY tablename' );
	else
		$result = $SQL->run( 'show tables' );

	if( $result )
	{
		while( $name = $SQL->fetch( $result ) )
		{
			if( preg_match( '/(.*)_backup(\d*)/' , $name[ 0 ] , $match )  )
			{
				$EXISTS[] = $match[ 1 ] . '_backup';
				$BACKUPS[ $match[ 1 ] ] = $match[ 2 ];
			}
			else
			$EXISTS[] = $name[ 0 ];
		}
	}

	/*		ログイン		*/
	if( !in_array( 'tool_admin_password' , $EXISTS ) )
	{
		$SQL->run( 'CREATE TABLE tool_admin_password (password ' . $SQL->colAlias[ 'string' ] .' )' );
		$SQL->run( 'INSERT INTO tool_admin_password (password ) VALUES ( "' . md5( admin ) . '" )' );
	}

	$result = $SQL->run( 'SELECT password FROM tool_admin_password' );
	$result = $SQL->fetch( $result );
	$TOOL_PASS = $result[ 0 ];

	if( $TOOL_PASS != $_SESSION[ 'tool_login' ] )
	{
		header( 'Content-Type: text/html;charset=shift_jis' );
		if( $TOOL_PASS != md5( $_POST[ 'password' ] ) )
		{
			if( strlen( $_POST[ 'password' ] ) )
				Draw::loginError();

			Draw::loginForm();
			return;
		}
		else
			$_SESSION[ 'tool_login' ] = $TOOL_PASS;
	}

	if( $_GET[ 'phpinfo' ] )
	{
		phpinfo();
		exit();
	}

	/*		ダウンロード系の処理		*/
	switch( $_GET[ 'method' ] )
	{
		/*		ダウンロード		*/
		case 'download' :
			$tdbFile = PathUtil::ModifyTDBFilePath($TDB[ $_GET[ 'table' ] ]);
			header( 'Cache-Control: no-cache, must-revalidate' );
			header( 'Pragma: no-cache' );
			header( 'Content-Type: application/octet-stream' );
			header( 'Content-Disposition: attachment; filename="' . strtolower( $_GET[ 'table' ] ) . '.csv"' );
			header( 'Content-Length: ' . filesize( $tdbFile ) );
			readfile( $tdbFile );
			return;

		/*		一括ダウンロード		*/
		case 'download_all' :

			/*		※PEARのFile_Archiveが別途必要です		*/
			if( $PEAR_ZIP )
			{
				$path = ini_get( 'include_path' );
				ini_set( 'include_path' , $path . ':./pear' );
				include './pear/File/Archive.php';
				File_Archive::extract( File_Archive::read( './tdb/' ) , File_Archive::toArchive( 'tdb.zip' , File_Archive::toOutput() ) );
			}
			else
			{
				$zip = new ZipArchive();
				$zip->open( './tdb/tdb.zip' , ZIPARCHIVE::OVERWRITE );

				foreach( $TABLE_NAME as $name )
					$zip->addFile(PathUtil::ModifyTDBFilePath ($TDB[ $name ]) , $name . '.csv' );

				$zip->close();

				header( 'Cache-Control: no-cache, must-revalidate' );
				header( 'Pragma: no-cache' );
				header( 'Content-Type: application/octet-stream' );
				header( 'Content-Disposition: attachment; filename="tdb.zip"' );
				header( 'Content-Length: ' . filesize( './tdb/tdb.zip' ) );
				readfile( './tdb/tdb.zip' );
			}

			return;

		default :
			break;
	}

	header( 'Content-Type: text/html;charset=shift_jis' );
	Draw::Head( $SQL_MASTER );

	/*		処理の振り分け		*/
	switch( $_GET[ 'method' ] )
	{
		/*		インポート		*/
		case 'import' :

			if( 'true' != $_GET[ 'run' ] )
				Draw::ImportCheck( $_GET[ 'table' ] );
			else
				importProc( $_GET[ 'table' ] , PathUtil::ModifyLSTFilePath($LST[  $_GET[ 'table' ] ]) , PathUtil::ModifyTDBFilePath ($TDB[ $_GET[ 'table' ] ]) , $SQL , $BACKUPS , $EXISTS );

			break;

		/*		TDBインポート		*/
		case 'import_all' :

			if( 'true' != $_GET[ 'run' ] )
				Draw::ImportAllCheck();
			else
			{
				foreach( $TABLE_NAME as $name )
					importProc( $name , PathUtil::ModifyLSTFilePath($LST[  $name ]) , PathUtil::ModifyTDBFilePath ($TDB[ $name ]) , $SQL , $BACKUPS , $EXISTS );
			}

			break;

		/*		完全インポート		*/
		case 'import_complete' :

			if( 'true' != $_GET[ 'run' ] )
				Draw::ImportCompleteCheck();
			else
			{
				$SQL->run( 'ALTER DATABASE '.$DB_NAME.' DEFAULT CHARACTER SET sjis COLLATE sjis_japanese_ci ' );
				foreach( $TABLE_NAME as $name )
					importProc( $name , PathUtil::ModifyLSTFilePath($LST[  $name ]) , PathUtil::ModifyTDBFilePath ($TDB[ $name ]) , $SQL , $BACKUPS , $EXISTS );

				$moduleDir = opendir( './module/added_template/' );

				while( $fileName = readdir( $moduleDir ) )
				{
					if( false !== strpos( $fileName , '.csv' ) )
					{
						$fileName = str_replace( '.csv' , '' , $fileName );
						addTemplateProc( $fileName , 'template' , PathUtil::ModifyLSTFilePath($LST[  'template' ]) , $SQL );
					}
				}
			}
			break;

		/*		エクスポート		*/
		case 'export' :
			exportProc( $_GET[ 'table' ] , PathUtil::ModifyLSTFilePath($LST[  $_GET[ 'table' ] ]) , PathUtil::ModifyTDBFilePath($TDB[ $_GET[ 'table' ] ]) , $SQL );
			break;

		/*		一括エクスポート		*/
		case 'export_all' :

			foreach( $TABLE_NAME as $name )
				exportProc( $name , PathUtil::ModifyLSTFilePath($LST[  $name ]) , PathUtil::ModifyTDBFilePath ($TDB[ $name ]) , $SQL );

			break;

		/*		バックアップ		*/
		case 'backup' :
			backupProc( $_GET[ 'table' ] , $SQL , $BACKUPS );
			break;

		/*		一括バックアップ		*/
		case 'backup_all' :

			foreach( $TABLE_NAME as $name )
				backupProc( $name , $SQL , $BACKUPS );

			break;

		/*		復元		*/
		case 'restore' :

			if( 'true' != $_GET[ 'run' ] )
				Draw::RestoreCheck( $_GET[ 'table' ] );
			else
				RestoreProc( $_GET[ 'table' ] , $SQL , $BACKUPS );

			break;

		/*		一括復元		*/
		case 'restore_all' :

			if( 'true' != $_GET[ 'run' ] )
				Draw::RestoreAllCheck();
			else
			{
				foreach( $TABLE_NAME as $name )
					RestoreProc( $name , $SQL , $BACKUPS );
			}

			break;

		/*		再編成		*/
		case 'struct' :
			if( 'true' != $_GET[ 'run' ] )
				Draw::StructCheck( $_GET[ 'table' ] );
			else
				structProc( $_GET[ 'table' ] , PathUtil::ModifyLSTFilePath($LST[  $_GET[ 'table' ] ]) , PathUtil::ModifyTDBFilePath ($TDB[ $_GET[ 'table' ] ]) , $SQL , $BACKUPS , $EXISTS );

			break;

		/*		一括再編成		*/
		case 'struct_all' :

			if( 'true' != $_GET[ 'run' ] )
				Draw::StructAllCheck();
			else
			{
				foreach( $TABLE_NAME as $name )
					structProc( $name , PathUtil::ModifyLSTFilePath($LST[  $name ]) , PathUtil::ModifyTDBFilePath ($TDB[ $name ]) , $SQL , $BACKUPS , $EXISTS );
			}

			break;

		/*		CSVとDBの比較		*/
		case 'marge' :

			if( !$_GET[ 'run' ] )
			{
				Draw::MargeSelect( $_GET[ 'table' ] , PathUtil::ModifyLSTFilePath($LST[  $_GET[ 'table' ] ]) , PathUtil::ModifyTDBFilePath($TDB[ $_GET[ 'table' ] ]) );
				break;
			}

			margeProc( $_GET[ 'table' ] , PathUtil::ModifyLSTFilePath($LST[  $_GET[ 'table' ] ]) , PathUtil::ModifyTDBFilePath($TDB[ $_GET[ 'table' ] ]) , $SQL );

			break;

		/*		テンプレート追加		*/
		case 'add_template' :
			addTemplateProc( $_GET[ 'name' ] , 'template' , PathUtil::ModifyLSTFilePath($LST[  'template' ]) , $SQL );
			break;

		/*		一括テンプレート追加		*/
		case 'add_template_all' :

			$moduleDir = opendir( './module/added_template/' );

			while( $fileName = readdir( $moduleDir ) )
			{
				if( false !== strpos( $fileName , '.csv' ) )
				{
					$fileName = str_replace( '.csv' , '' , $fileName );
					addTemplateProc( $fileName , 'template' , PathUtil::ModifyLSTFilePath($LST[  'template' ]) , $SQL );
				}
			}

			break;

		/*		テンプレート自動構築		*/
		case 'add_template_auto' :

			importProc( 'template' , PathUtil::ModifyLSTFilePath($LST[ 'template' ]) , PathUtil::ModifyTDBFilePath($TDB[ 'template' ]) , $SQL , $BACKUPS , $EXISTS );

			$moduleDir = opendir( './module/added_template/' );

			while( $fileName = readdir( $moduleDir ) )
			{
				if( false !== strpos( $fileName , '.csv' ) )
				{
					$fileName = str_replace( '.csv' , '' , $fileName );
					addTemplateProc( $fileName , 'template' , PathUtil::ModifyLSTFilePath($LST[ 'template' ]) , $SQL );
				}
			}

			break;

		/*		デザインテンプレート一覧表示		*/
		case 'template_map' :
			templateMapProc( $SQL );
			break;

		/*		パスワード変更		*/
		case 'password' :
			if( $_POST[ 'password' ] )
				passwordProc( $_POST[ 'password' ] , $SQL );
			else
				Draw::PasswordChange();

			break;

		default :
			Draw::TableSelect( $TABLE_NAME , $LST , $TDB , $EXISTS , $BACKUPS );
			break;
	}

	Draw::Foot();

	/*		インポート処理		*/
	function importProc( $_tableName , $_lstFile , $_tdbFile , $_sqlObject , $_backups , $_exists )
	{
		$csv = new CSV( $_tableName , $_lstFile , $_tdbFile );

		if( !$csv->readable() )
		{
			Draw::ImportError( $_tableName );
			return;
		}

		if( !in_array( strtolower( $_tableName ) , $_exists ) )
			$result = $_sqlObject->createTable( $_tableName , $csv );

		/*		検証用テーブルを作成		*/
		if( in_array( 'import_verifi' , $_exists ) )
			$result = $_sqlObject->dropTable( 'import_verifi' );
		
		$result = $_sqlObject->createTable( 'import_verifi' , $csv );

		while( $result && $record = $csv->readRecord() )
			$result = $_sqlObject->insertRecord( 'import_verifi' , $csv , $record );

		/*		元テーブルをバックアップ		*/
		if( $result )
			$result = $_sqlObject->renameTable( $_tableName , $_tableName . '_backup' );

		/*		検証用テーブルを元テーブルにリネーム		*/
		if( $result )
			$result = $_sqlObject->renameTable( 'import_verifi' , $_tableName );

		if( !$result )
		{
			Draw::SQLError( $_sqlObject );
			Draw::ImportError( $_tableName );
			$_sqlObject->renameTable( $_GET[ 'table' ] . '_backup' , $_GET[ 'table' ] );
			return;
		}

		if( $_backups[ $_tableName ] )
			$_sqlObject->dropTable( $_tableName . '_backup' . $_backups[ $_tableName ] );

		$_sqlObject->renameTable( $_tableName . '_backup' , $_tableName . '_backup' . time() );
		Draw::ImportComplete( $_tableName );
		return $result;
	}

	/*		エクスポート処理		*/
	function exportProc( $_tableName , $_lstFile , $_outputFile , $_sqlObject )
	{
		global $SQL_MASTER;

		//password検証の為CSVをロード
		$csv = new CSV( $_tableName , $_lstFile , $_outputFile );
		$pass_keys = array_keys( $csv->colTypes, 'password' );

		if( count( $pass_keys ) ){
			global $CONFIG_SQL_PASSWORD_KEY;
			$_getColNames = Array();
			foreach( $csv->colNames as $_key => $_colName ){
				if( array_search( $_key , $pass_keys ) !== FALSE && $SQL_MASTER == 'MySQLDatabase' ){
					$_getColNames[ $_key ] = "AES_DECRYPT($_colName,'$CONFIG_SQL_PASSWORD_KEY') as " . $_colName;
				}else{
					$_getColNames[ $_key ] = $_colName;
				}
			}
			$sql = 'SELECT '.join(',',$_getColNames).' FROM ' . strtolower( $_tableName );
		}else{
			$sql = 'SELECT * FROM ' . strtolower( $_tableName );
		}
		$result = $_sqlObject->run( $sql );

		if( !$result )
		{
			Draw::SQLError( $_sqlObject );
			Draw::ExportError( $_tableName );
			return;
		}

		$file = fopen( $_outputFile , 'wb' );

		if( !$file )
		{
			Draw::ExportError( $_tableName );
			return;
		}

		while( $data = $_sqlObject->fetch( $result ) )
		{
			/*		PHP5.1.0以上		*/
//			fputcsv( $file , $data );

			/*		PHP5.1.0未満		*/
			for( $i = 0 ; $i < count( $data ) ; $i++ )
				if( FALSE !== strstr( $data[ $i ] , "\n" ) || FALSE !== strstr( $data[ $i ] , '"' ) || FALSE !== strstr( $data[ $i ] , ',' ) )
					$data[ $i ] = '"' . str_replace( '"' , '""' , $data[ $i ] ) . '"';

			fputs( $file , implode( ',' , $data ) . "\n" );
		}

		Draw::ExportComplete( $_tableName );
	}

	/*		バックアップ処理		*/
	function backupProc( $_tableName , $_sqlObject , $_backups )
	{
		$result = $_sqlObject->copyTable( $_tableName ,  $_tableName . '_backup' );

		if( !$result )
		{
			Draw::SQLError( $_sqlObject );
			Draw::BackupError( $_tableName );
		}
		else
		{
			$result = $_sqlObject->dropTable( $_tableName . '_backup' . $_backups[ strtolower( $_tableName ) ] );
			$_sqlObject->renameTable( $_tableName . '_backup' , $_tableName . '_backup' . time() );
			Draw::BackupComplete( $_tableName );
		}
	}

	/*		復元処理		*/
	function RestoreProc( $_tableName , $_sqlObject , $_backups )
	{
		$result = $_sqlObject->dropTable( $_tableName . '_swap' );
		$result = $_sqlObject->renameTable( $_tableName , $_tableName . '_swap' );

		if( $result )
			$result = $_sqlObject->copyTable( $_tableName . '_backup' . $_backups[ strtolower( $_tableName ) ] ,  $_tableName );

		if( !$result )
		{
			Draw::SQLError( $_sqlObject );
			$_sqlObject->renameTable( $_tableName . '_swap' , $_tableName );
			Draw::RestoreError( $_tableName );
		}
		else
			Draw::RestoreComplete( $_tableName );
	}

	/*		再編成処理		*/
	function structProc( $_tableName , $_lstFile , $_tdbFile , $_sqlObject , $_backups , $_exists )
	{
		global $SQL_MASTER;
		global $CONFIG_SQL_PASSWORD_KEY;

		$csv = new CSV( $_tableName , $_lstFile , $_tdbFile );

		if( !$csv->readable() )
		{
			Draw::StructError( $_tableName );
			return;
		}

		if( !in_array( strtolower( $_tableName ) , $_exists ) )
		{
			Draw::StructError( $_tableName );
			return;
		}

		/*		検証用テーブルを作成		*/
		if( in_array( 'struct_verifi' , $_exists ) )
		$result = $_sqlObject->dropTable( 'struct_verifi' );
		$result = $_sqlObject->createTable( 'struct_verifi' , $csv );

		if( !$result )
		{
			Draw::SQLError( $_sqlObject );
			Draw::StructError( $_tableName );
			return;
		}

		/*		パスワードの検証		*/
		if( $SQL_MASTER == 'MySQLDatabase' )
		{
			/*		DBからカラム一覧を取得		*/
			$result = $_sqlObject->run( 'SHOW COLUMNS FROM ' . strtolower( $_tableName ) );

			while( $line = $_sqlObject->fetch( $result , 'assoc' ) )
				$dbColumns[] = $line[ 'Field' ];

			foreach( $dbColumns as $key => $colName )
			{
				/*		カラムの型を調べる		*/
				$index = array_search( $colName , $csv->colNames );

				if( FALSE !== $index )
					$type = $csv->colTypes[ $index ];
				else
					$type = 'unknown';

				/*		パスワードならデコードして取得		*/
				if( 'password' == $type )
					$cols[ $key ] = "AES_DECRYPT($colName,'$CONFIG_SQL_PASSWORD_KEY') as " . $colName;
				else
					$cols[ $key ] = $colName;
			}

			$sql = 'SELECT '.join( ',' , $cols ) . ' FROM ' . strtolower( $_tableName );
		}
		else
			$sql = 'SELECT * FROM ' . strtolower( $_tableName );

		$table = $_sqlObject->run( $sql );

		if( !$table )
		{
			Draw::SQLError( $_sqlObject );
			Draw::StructError( $_tableName );
			return;
		}

		while( $read = $_sqlObject->fetch( $table , 'assoc' ) )
		{
			$data = Array();

			foreach( $csv->colNames as $key )
				$data[ $key ] = $read[ $key ];

			$result = $_sqlObject->insertRecord( 'struct_verifi' , $csv , $data );

			if( !$result )
			{
				Draw::SQLError( $_sqlObject );
				Draw::StructError( $_tableName );
				return;
			}
		}

		/*		元テーブルをバックアップ		*/
		if( $result )
			$result = $_sqlObject->renameTable( $_tableName , $_tableName . '_backup' );

		/*		検証用テーブルを元テーブルにリネーム		*/
		if( $result )
			$result = $_sqlObject->renameTable( 'struct_verifi' , $_tableName );

		if( !$result )
		{
			Draw::SQLError( $_sqlObject );
			Draw::StructError( $_tableName );
			$_sqlObject->renameTable( $_tableName . '_backup' , $_tableName );
			return;
		}

		if( $_backups[ $_tableName ] )
			$_sqlObject->dropTable( $_tableName . '_backup' . $_backups[ $_tableName ] );

		$_sqlObject->renameTable( $_tableName . '_backup' , $_tableName . '_backup' . time() );

		Draw::StructComplete( $_tableName );
	}

	/*		マージ処理		*/
	function margeProc( $_tableName , $_lstFile , $_tdbFile , $_sqlObject )
	{
		$csv = new CSV( $_tableName , $_lstFile , $_tdbFile );

		if( !$csv->readable() )
			return;

		/*		カラム毎の処理を特定する		*/
		for( $i = 0 ; $i < count( $csv->colNames ) ; $i++ )
		{
			$name = $csv->colNames[ $i ];

			if( is_array($_GET[ 'col_' . $name ] ) )
			{
				$operation[ $name ] = implode( ',' , $_GET[ 'col_' . $name ] );
				$columns[]          = $name;

				if( 'int' == $csv->colTypes[ $i ] || 'double' == $csv->colTypes[ $i ] )
					$nums[] = $name;

				if( 'boolean' == $csv->colTypes[ $i ] )
					$booleans[] = $name;
			}
		}

		if( !$columns )
			return;

		Draw::MargeHead( $columns );

		/*		CSVを1行ずつ読み込んで比較		*/
		while( $record = $csv->readRecord() )
		{
			$result = $_sqlObject->run( 'SELECT ' . implode( ',' , $columns ) . ' FROM ' . strtolower( $_GET[ 'table' ] ) . ' WHERE shadow_id = ' . $record[ 'shadow_id' ] );

			if( !$result )
				continue;

			$read   = $_sqlObject->fetch( $result , 'assoc' );

			if( !is_array( $read ) )
				continue;

			$replacedColumns = Array();

			/*		置換するカラムを特定		*/
			foreach( $columns as $column )
			{
				if( false !== strstr( $operation[ $column ] , 'rep' ) )
				{
					if( $record[ $column ] != $read[ $column ] )
						$replacedColumns[] = $column;
				}
			}

			/*		型に合わせて表記を変更		*/
			foreach( $nums as $name )
			{
				$record[ $name ] = ( !$record[ $name ] ? '0' : $record[ $name ] );
				$read[ $name ]   = ( !$read[ $name ]  ? '0' : $read[ $name ] );
			}

			foreach( $booleans as $name )
			{
				$record[ $name ] = ( !$record[ $name ] || '0' == $record[ $name ] || 'false' == strtolower( $record[ $name ] ) || 'f' == strtolower( $record[ $name ] ) ? 'FALSE' : 'TRUE' );
				$read[ $name ]   = ( !$read[ $name ] || '0' == $record[ $name ] || 'f' == strtolower( $read[ $name ] ) || 'false' == strtolower( $read[ $name ] ) ? 'FALSE' : 'TRUE' );
			}

			/*		置換実行		*/
			if( count( $replacedColumns ) )
			{
				$result = $_sqlObject->updateRecord( $_tableName , $csv , $record , $replacedColumns );

				if( !$result )
				{
					Draw::SQLError( $_sqlObject );
					$replacedColumns = Array();
				}
			}

			/*		結果を描画		*/
			Draw::MargeResult( $record , $read , $replacedColumns );
		}

		Draw::MargeFoot();
	}

	/*		モジュールテンプレート追加処理		*/
	/*		p0 : モジュール名		*/
	/*		p1 : テーブル名		*/
	/*		p2 : LSTファイル名		*/
	/*		p3 : TDBファイル名		*/
	/*		p3 : SQLConnectオブジェクト		*/
	function addTemplateProc( $_templateName , $_tableName , $_lstFileName , $_sqlObject )
	{
		$addFile     = './module/added_template/' . $_templateName . '.csv';
		$csv         = new CSV( $_tableName , $_lstFileName , $addFile );
		$resultCount = 0;
		if( !$csv->readable() )
			return;

		while( $record = $csv->readRecord() )
		{
			$result                = $_sqlObject->run( 'SELECT shadow_id FROM ' . strtolower( $_tableName ) . ' ORDER BY shadow_id desc' );
			$result                = $_sqlObject->fetch( $result );
			$record[ 'shadow_id' ] = $result[ 0 ] + 1;

			/*		ID生成		*/
			$result = $_sqlObject->run( 'SELECT id FROM ' . strtolower( $_tableName ) . ' WHERE shadow_id = ' . ( $record[ 'shadow_id' ] - 1 ) );
			$result = $_sqlObject->fetch( $result );
			$id     = $result[ 0 ];

			preg_match( '/([^\\d]*)(\d*)/' , $id , $match );
			$oldLength = strlen( $match[ 2 ] );
			$newNum    = sprintf( '%04d' , ++$match[ 2 ] );

			$record[ 'id' ] = $match[ 1 ] . $newNum;

			/*		重複チェック		*/
			$query = 'SELECT shadow_id FROM ' . strtolower( $_tableName ) . ' WHERE ';
			$terms = Array();

			for( $i = 0 ; $i < count( $csv->colNames ) ; $i++ )
			{
				if( 'shadow_id' == $csv->colNames[ $i ] || 'id' == $csv->colNames[ $i ] || 'regist' == $csv->colNames[ $i ] )
					continue;

				$terms[] = $csv->colNames[ $i ] . ' = ' . $_sqlObject->escape( $record[ $csv->colNames[ $i ] ] , $csv->colTypes[ $i ] );
			}

			$query .= implode( ' AND ' , $terms );
			$result = $_sqlObject->run( $query );
			$result = $_sqlObject->fetch( $result );

			if( $result[ 0 ] )
				continue;

			$result = $_sqlObject->insertRecord( $_tableName , $csv , $record );

			if( $result )
				$resultCount++;
		}

		print '<p>' . $_templateName . ' import : ' . $resultCount . '</p>';
	}

	/*		デザインテンプレート描画処理		*/
	function templateMapProc( $_sql )
	{
		$map = new TemplateMap( $_sql );

		$userTypes = $map->userTypes();

		foreach( $userTypes as $userType )
			print '<a href="#' . ( $userType ? $userType : 'label' ) . '">【' . ( $userType ? $userType : 'label' ) . '】</a>　';

		foreach( $userTypes as $userType )
		{
			$targets = $map->targets( $userType );
			Draw::TemplateMap( $userType , $targets , $map );
		}
	}

	/*		パスワード変更		*/
	function passwordProc( $_password , $_sql )
	{
		$_sql->run( 'UPDATE tool_admin_password SET password = "' . md5( $_password ) . '"' );
		$_SESSION[ 'tool_login' ] = '';

		print '<p>変更しました</p>';
	}

	class SQLConnect
	{
		/*		コンストラクタ		*/
		function __construct( $_dbmsName , $_user , $_password , $_dbName , $_server , $_port )
		{
			$this->server   = $_dbmsName;
			$this->user     = $_user;
			$this->password = $_password;
			$this->dbName   = $_dbName;
			$this->server   = $_server;
			$this->port     = $_port;
		}

		/*		各DBMS用の派生クラスを返す		*/
		/*		p0 : DBMS名		*/
		/*		rt : オブジェクト		*/
		static function Create( $_dbmsName , $_user , $_password , $_dbName , $_server , $_port )
		{
			switch( $_dbmsName )
			{
				case 'MySQLDatabase' :
					return new MySQLConnect( $_dbmsName , $_user , $_password , $_dbName , $_server , $_port );
				case 'PostgreSQLDatabase' :
					return new PostgreSQLConnect( $_dbmsName , $_user , $_password , $_dbName , $_server , $_port );
				case 'SQLiteDatabase' :
                    if( PHP_VERSION_ID >= 50400  ){
                        return new SQLite3Connect( $_dbmsName , $_user , $_password , $_dbName , $_server , $_port );
                    }else{
						return new SQLiteConnect( $_dbmsName , $_user , $_password , $_dbName , $_server , $_port );
                    }
				default :
					return new SQLConnect( $_dbmsName , $_user , $_password , $_dbName , $_server , $_port );
			}
		}

		/*		SQL文を実行		*/
		/*		p0 : SQL文		*/
		function run( $_query )
		{
			$this->lastQuery = $_query;
			print '<p><strong style="color:#f00">DBMSが未特定のため実行できません : </strong>' . $_query . '</p>';
		}

		/*		エラー文字列を取得		*/
		/*		定型処理 : 引数はありません		*/
		function error()
		{
			return 'no error';
		}

		/*		結果をパースする		*/
		/*		p0 : リソースID		*/
		/*		p0 : パース方法(row/array/)		*/
		function fetch( $_resource , $_fetchType = 'row' )
		{
			return null;
		}

		/*		SQL文に組み込むために、型に合わせて適切にエスケープする		*/
		/*		p0 : 値		*/
		/*		p1 : lstでの型定義		*/
		function escape( $_value , $_type )
		{
			switch( $_type )
			{
				case 'password' :
				case 'string' :
				case 'image' :
				case 'varchar' :
				case 'char' :
					return "'" . str_replace( "'" , "\\'" , $_value ) . "'";

				case 'boolean' :
					return ( !$_value || 'false' == strtolower( $_value ) ? 'FALSE' : 'TRUE' );

				default :
					return ( !$_value ? '0' : $_value );
			}
		}

		/*		テーブルを削除する		*/
		/*		p0 : テーブル名		*/
		function dropTable( $_tableName )
		{
			return $this->run( 'DROP TABLE ' . strtolower( $_tableName ) );
		}

		/*		テーブルを作成する		*/
		/*		p0 : テーブル名		*/
		/*		p1 : CSVオブジェクト		*/
		function createTable( $_tableName , $_csvObject )
		{
			$size_type = Array( 'varchar' => 'true', 'char' => 'true' );
			
			for( $i = 0 ; $i < count( $_csvObject->colNames ) ; $i++ )
				$columns[] = implode( ' ' , Array( $_csvObject->colNames[ $i ] , $this->colAlias[ $_csvObject->colTypes[ $i ] ] . ( isset( $size_type[$_csvObject->colTypes[ $i ]] ) ? '('.$_csvObject->colSizes[ $i ].')' : ''  ) , ( 'shadow_id' == $_csvObject->colNames[ $i ] ? 'primary key' : '' ) ) );

			return $this->run( 'CREATE TABLE ' . strtolower( $_tableName ) . ' (' . implode( ',' , $columns ) . ')' );
		}

		/*		レコードを追加する		*/
		/*		p0 : テーブル名		*/
		/*		p1 : CSVオブジェクト		*/
		/*		p2 : CSV->readRecordの値		*/
		function insertRecord( $_tableName , $_csvObject , $_record )
		{
			for( $i = 0 ; $i < count( $_csvObject->colNames ) ; $i++ )
			{
				$name  = $_csvObject->colNames[ $i ];
				$value = $this->escape( $_record[ $name ] , $_csvObject->colTypes[ $i ] );

				$columns[] = $name;
				$values[]  = $value;
			}

			return $this->run( 'INSERT INTO ' . strtolower( $_tableName ) . ' (' . implode( ',' , $columns ) . ') VALUES (' . implode( ',' , $values ) . ')' );
		}

		/*		レコードを更新する		*/
		/*		p0 : テーブル名		*/
		/*		p1 : CSVオブジェクト		*/
		/*		p2 : 更新するレコード		*/
		/*		p3 : 更新するカラム		*/
		function updateRecord( $_tableName , $_csvObject , $_record , $_column )
		{
			$query = 'UPDATE ' . strtolower( $_tableName ) . ' SET ';

			for( $i = 0 ; $i < count( $_csvObject->colNames ) ; $i++ )
			{
				$name  = $_csvObject->colNames[ $i ];
				$value = $this->escape( $_record[ $name ] , $_csvObject->colTypes[ $i ] );

				if( !in_array( $name , $_column ) )
					continue;

				$updates[] = $name . ' = ' . $value;
			}

			if( !$updates )
				return;

			$query .= implode( ',' , $updates );
			$query .= ' WHERE shadow_id = ' . $_record[ 'shadow_id' ];

			return $this->run( $query );
		}

		/*		テーブルをリネームする		*/
		/*		p0 : 元テーブル名		*/
		/*		p1 : 新テーブル名		*/
		function renameTable( $_originName , $_newName )
		{
			return $this->run( 'ALTER TABLE ' . strtolower( $_originName ) . ' RENAME TO ' . strtolower( $_newName ) );
		}

		/*		テーブルを複製する		*/
		/*		p0 : 元テーブル名		*/
		/*		p1 : 新テーブル名		*/
		function copyTable( $_originName , $_newName )
		{
			return $this->run( 'CREATE TABLE ' . strtolower( $_newName ) . ' AS SELECT * FROM ' . strtolower( $_originName ) );
		}
	}

	class MySQLConnect extends SQLConnect
	{
		/*		コンストラクタ		*/
		function __construct( $_dbmsName , $_user , $_password , $_dbName , $_server , $_port )
		{
			parent::__construct( $_dbmsName , $_user , $_password , $_dbName , $_server , $_port );

			$this->colAlias = Array( 'int' => 'int' , 'double' => 'double' , 'string' => 'text' , 'image' => 'text' , 'boolean' => 'boolean' , 'timestamp' => 'int', 'char' => 'char', 'varchar' => 'varchar', 'password' => 'blob' );

			if($_port != "")
				$this->connect  = mysqli_connect( $this->server . ':' . $this->port , $this->user , $this->password );
			else
				$this->connect  = mysqli_connect( $this->server , $this->user , $this->password );

			if( $this->connect )
			{
				mysqli_select_db( $this->connect, $this->dbName );
				//mySQLからの出力コードを内部エンコードに
				if( !function_exists( 'mysqli_set_charset' ) ){
					mysqli_query($this->connect, 'set names sjis');
				//	mysqli_query($this->connect,'SET NAMES binary;');
				}else{
					//set name ***と違い、[mysqli_real_escape_string]にも有効。  正しphp5.2.3、MySQL5.0.7以降のみ利用可能
					mysqli_set_charset($this->connect, 'sjis');
				//	mysqli_set_charset($this->connect, 'binary');
				}
			}
		}

		/*		SQL文を実行		*/
		/*		p0 : SQL文		*/
		function run( $_query )
		{
			$this->lastQuery = $_query;
			return ( $this->connect ? mysqli_query( $this->connect, $_query ) : false );
		}

		/*		エラー文字列を取得		*/
		/*		定型処理 : 引数はありません		*/
		function error()
		{
			return mysqli_error( $this->connect );
		}

		/*		結果をパースする		*/
		/*		p0 : リソースID		*/
		/*		p0 : パース方法(row/array/)		*/
		function fetch( $_resource , $_fetchType = 'row' )
		{
			switch( $_fetchType )
			{
				case 'row' :
					return mysqli_fetch_row( $_resource );

				case 'assoc' :
					return mysqli_fetch_assoc( $_resource );

				default :
					return mysqli_fetch_array( $_resource );
			}
		}
		
		/*		SQL文に組み込むために、型に合わせて適切にエスケープする		*/
		/*		password意外は親クラスを呼ぶ		*/
		/*		p0 : 値		*/
		/*		p1 : lstでの型定義		*/
		function escape( $_value , $_type )
		{
			switch( $_type )
			{
				case 'password' :
					global $CONFIG_SQL_PASSWORD_KEY;
					
					$_value = "'" . str_replace( "'" , "\\'" , $_value ) . "'";
					return "AES_ENCRYPT( $_value, '$CONFIG_SQL_PASSWORD_KEY' )";
				default :
					return parent::escape($_value, $_type );
			}
		}

		/*		テーブルを作成する		*/
		/*		p0 : テーブル名		*/
		/*		p1 : CSVオブジェクト		*/
		function createTable( $_tableName , $_csvObject )
		{
			$size_type = Array( 'varchar' => 'true', 'char' => 'true' );
			$out_type = Array( 'string', 'image', 'password' );
			$auto_index_column = Array( 'id' => true );
			$indexs = array();
			
			for( $i = 0 ; $i < count( $_csvObject->colNames ) ; $i++ ){
				$columns[] = implode( ' ' , Array( $_csvObject->colNames[ $i ] , $this->colAlias[ $_csvObject->colTypes[ $i ] ] . ( isset( $size_type[$_csvObject->colTypes[ $i ]] ) ? '('.$_csvObject->colSizes[ $i ].')' : ''  ) , ( 'shadow_id' == $_csvObject->colNames[ $i ] ? 'primary key' : '' ) ) );
				if( !in_array( $_csvObject->colTypes[ $i ],$out_type )  && isset( $auto_index_column[ $_csvObject->colNames[ $i ] ] ) && $auto_index_column[ $_csvObject->colNames[ $i ] ] ){
					$indexs[] = "UNIQUE INDEX system_".$_csvObject->colNames[ $i ]." ( ".$_csvObject->colNames[ $i ]." )";
				}
			}
			
			foreach( $this->getIndexs( $_csvObject ) as $index_name => $index ){
				$index_string = "";
				if( $index["Index_type"] == "FULLTEXT" ){
					$index_string .= "FULLTEXT ";
				}
				$col_array=array();
				foreach( $index['Column'] as $column_name => $column ){
					$col_array[] = $column_name;
				}
				
				$index_string .= "INDEX ".$index_name . " (" . join(",",$col_array) . " )";
				
				$indexs[] = $index_string;
			}
			
//			print 'CREATE TABLE ' . strtolower( $_tableName ) . ' (' . implode( ',' , array_merge($columns,$indexs,$_csvObject->indexs) ) . ')';

			return $this->run( 'CREATE TABLE ' . strtolower( $_tableName ) . ' (' . implode( ',' , array_merge($columns,$indexs,$_csvObject->indexs) ) . ')' );
		}
		
		function getIndexs( $_csvObject )
		{		
			global $EXISTS;
			$tableName = $_csvObject->tableName;
			
			if( !in_array( $tableName, $EXISTS) ){ return array(); }
			
			$result = $this->run( 'SHOW INDEX FROM ' . strtolower( $tableName ));
			
			$indexs = array();
			
			while( $row = mysqli_fetch_assoc( $result ) ){
				if( $row['Key_name'] != "PRIMARY" && $row['Key_name'] != 'system_'. $row['Column_name'] && !isset($_csvObject->indexs[ $row['Key_name'] ]) ){
					if( !isset( $indexs[ $row['Key_name'] ] ) ){
						$indexs[ $row['Key_name'] ]["Non_unique"]	= $row["Non_unique"];
						$indexs[ $row['Key_name'] ]["Index_type"]	= $row["Index_type"];
						$indexs[ $row['Key_name'] ]["Comment"]		= $row["Comment"];
					}
					$indexs[ $row['Key_name'] ]['Column'][ $row['Column_name'] ] = array(
						"Collation" => $row['Collation'], 
						"Null" => $row['Null'], 
					);
				}
			}
			return $indexs;
		}
	}

	class PostgreSQLConnect extends SQLConnect
	{
		/*		コンストラクタ		*/
		function __construct( $_dbmsName , $_user , $_password , $_dbName , $_server , $_port )
		{
			parent::__construct( $_dbmsName , $_user , $_password , $_dbName , $_server , $_port );

			$this->colAlias = Array( 'int' => 'int4' , 'double' => 'float8' , 'string' => 'text' , 'image' => 'text' , 'boolean' => 'boolean' , 'timestamp' => 'int4', 'char' => 'char', 'varchar' => 'varchar', 'password' => 'text' );

			if($_port != "")
				$this->connect  = pg_pconnect( 'host=' . $this->server . ' port=' . $this->port . ' dbname=' . $this->dbName . ' user=' . $this->user . ' password=' . $this->password );
			else
				$this->connect  = pg_pconnect( 'host=' . $this->server . ' dbname=' . $this->dbName . ' user=' . $this->user . ' password=' . $this->password );

			pg_set_client_encoding('SJIS');

		}

		/*		SQL文を実行		*/
		/*		p0 : SQL文		*/
		function run( $_query )
		{
			$this->lastQuery = $_query;
			return ( $this->connect ? pg_exec( $this->connect , $_query ) : false );
		}

		/*		エラー文字列を取得		*/
		/*		定型処理 : 引数はありません		*/
		function error()
		{
			return pg_last_error();
		}

		/*		結果をパースする		*/
		/*		p0 : リソースID		*/
		/*		p0 : パース方法(row/array/)		*/
		function fetch( $_resource , $_fetchType = 'row' )
		{
			switch( $_fetchType )
			{
				case 'row' :
					return pg_fetch_row( $_resource );

				case 'assoc' :
					return pg_fetch_assoc( $_resource );

				default :
					return pg_fetch_array( $_resource );
			}
		}

		/*		SQL文に組み込むために、型に合わせて適切にエスケープする		*/
		/*		p0 : 値		*/
		/*		p1 : lstでの型定義		*/
		function escape( $_value , $_type )
		{
			return parent::escape( mb_convert_encoding( $_value , pg_client_encoding() , 'SJIS' ) , $_type );
		}

		/*		テーブルを作成する		*/
		/*		p0 : テーブル名		*/
		/*		p1 : CSVオブジェクト		*/
		function createTable( $_tableName , $_csvObject )
		{
			$result = parent::createTable( $_tableName , $_csvObject );

			if( $result )
				$result = $this->run( 'GRANT ALL PRIVILEGES ON ' . strtolower( $_tableName ) . ' TO PUBLIC' );

			return $result;
		}

		/*		テーブルをリネームする		*/
		/*		※Postgresはリネームを参照で実装してるっぽい？のでコピーで実装		*/
		/*		p0 : 元テーブル名		*/
		/*		p1 : 新テーブル名		*/
		function renameTable( $_originName , $_newName )
		{
			$result = $this->copyTable( $_originName , $_newName );

			if( $result )
			{
				$result = $this->dropTable( $_originName );

				if( !$result )
				{
					$this->copyTable( $_newName , $_originName );
					$this->dropTable( $_newName );
				}
			}

			return $result;
		}
	}

	class SQLiteConnect extends SQLConnect
	{
		/*		コンストラクタ		*/
		function __construct( $_dbmsName , $_user , $_password , $_dbName , $_server , $_port )
		{
			parent::__construct( $_dbmsName , $_user , $_password , $_dbName , $_server , $_port );

			$this->colAlias = Array( 'int' => 'integer' , 'double' => 'real' , 'string' => 'text' , 'image' => 'text' , 'boolean' => 'boolean' , 'timestamp' => 'integer', 'char' => 'char', 'varchar' => 'varchar', 'password' => 'text' );
			$this->connect  = sqlite_open( './tdb/' . $this->dbName . '.db' , 0666 , $error );

			//トランザクションを開始する
			$this->run( 'BEGIN' );
		}

		/*		デストラクタ		*/
		function __destruct()
		{
			//トランザクションを終了する
			$this->run( 'END' );
		}

		/*		SQL文を実行		*/
		/*		p0 : SQL文		*/
		function run( $_query )
		{
			$this->lastQuery = $_query;
			return ( $this->connect ? @sqlite_query( mb_convert_encoding($_query, 'UTF-8', mb_internal_encoding() ) , $this->connect ) : false );
		}

		/*		エラー文字列を取得		*/
		/*		定型処理 : 引数はありません		*/
		function error()
		{
			return sqlite_error_string( sqlite_last_error( $this->connect ) );
		}

		/*		結果をパースする		*/
		/*		p0 : リソースID		*/
		/*		p0 : パース方法(row/array/)		*/
		function fetch( $_resource , $_fetchType = 'row' )
		{
			switch( $_fetchType )
			{
				case 'row' :
					$array = sqlite_fetch_array( $_resource , SQLITE_NUM );
					break;
				case 'assoc' :
					$array = sqlite_fetch_array( $_resource , SQLITE_ASSOC );
					break;
				default :
					$array = sqlite_fetch_array( $_resource );
			}

			if($array){
				$array = array_map( create_function( '$x', 'return mb_convert_encoding($x, mb_internal_encoding() , \'UTF-8\' );' ) , $array );
			}
			
			return $array;
		}

		/*		SQL文に組み込むために、型に合わせて適切にエスケープする		*/
		/*		p0 : 値		*/
		/*		p1 : lstでの型定義		*/
		function escape( $_value , $_type )
		{
			switch( $_type )
			{
				case 'password' :
				case 'string' :
				case 'image' :
				case 'varchar' :
				case 'char' :
					return "'" . str_replace( "'" , "''" , sqlite_escape_string( $_value ) ) . "'";

				case 'boolean' :
					return ( !$_value || 'false' == strtolower( $_value ) ? '\'\'' : '1' );

				default :
					return parent::escape( sqlite_escape_string( $_value ) , $_type );
			}
		}

		/*		テーブルをリネームする		*/
		/*		※標準搭載のSQLiteではALTERが使えないので、コピーで実装		*/
		/*		p0 : 元テーブル名		*/
		/*		p1 : 新テーブル名		*/
		function renameTable( $_originName , $_newName )
		{
			$result = $this->copyTable( $_originName , $_newName );

			if( $result )
			{
				$result = $this->dropTable( $_originName );

				if( !$result )
				{
					$this->copyTable( $_newName , $_originName );
					$this->dropTable( $_newName );
				}
			}

			return $result;
		}
	}

class SQLite3Connect extends SQLConnect
{
    /*		コンストラクタ		*/
    function __construct( $_dbmsName , $_user , $_password , $_dbName , $_server , $_port )
    {
        parent::__construct( $_dbmsName , $_user , $_password , $_dbName , $_server , $_port );

        $this->colAlias = Array( 'int' => 'integer' , 'double' => 'real' , 'string' => 'text' , 'image' => 'text' , 'boolean' => 'boolean' , 'timestamp' => 'integer', 'char' => 'char', 'varchar' => 'varchar', 'password' => 'text' );

        $sqlite_db_path = './tdb/';
        try{
            $this->connect = new  SQLite3( $sqlite_db_path . $this->dbName . '.db' );
        }catch( Exception $e ){
            exit("SQLDatabase() : DB CONNECT ERROR. new SQLite3( ".$dbName." )\n");
        }

        //トランザクションを開始する
        $this->run( 'BEGIN' );
    }

    /*		デストラクタ		*/
    function __destruct()
    {
        //トランザクションを終了する
        $this->run( 'END' );
        //sqlite_close( $this->connect );
    }

    /*		SQL文を実行		*/
    /*		p0 : SQL文		*/
    function run( $_query )
    {
        $this->lastQuery = $_query;
        return ( $this->connect ? $this->connect->query( mb_convert_encoding($_query, 'UTF-8', mb_internal_encoding() ) ) : false );
    }

    /*		エラー文字列を取得		*/
    /*		定型処理 : 引数はありません		*/
    function error()
    {
        return ( $this->connect->lastErrorMsg( ) );
    }

    /*		結果をパースする		*/
    /*		p0 : リソースID		*/
    /*		p0 : パース方法(row/array/)		*/
    function fetch( $_resource , $_fetchType = 'row' )
    {
        switch( $_fetchType )
        {
            case 'row' :
                $array = $_resource->fetchArray( SQLITE3_NUM );
                break;
            case 'assoc' :
                $array = $_resource->fetchArray( SQLITE3_ASSOC );
                break;
            default :
                $array = $_resource->fetchArray(  );
        }
        if($array){
            $array = array_map( create_function( '$x', 'return mb_convert_encoding($x, mb_internal_encoding() , \'UTF-8\' );' ) , $array );
        }

        return $array;
    }

    /*		SQL文に組み込むために、型に合わせて適切にエスケープする		*/
    /*		p0 : 値		*/
    /*		p1 : lstでの型定義		*/
    function escape( $_value , $_type )
    {
        switch( $_type )
        {
            case 'password' :
            case 'string' :
            case 'image' :
            case 'varchar' :
            case 'char' :
                if( strlen( $_value )){
                    return parent::escape( SQLite3::escapeString( $_value ) , $_type );
                }else{
                    return "''";
                }

            case 'boolean' :
                return ( !$_value || 'false' == strtolower( $_value ) ? '\'\'' : '1' );

            default :
                return parent::escape( SQLite3::escapeString( $_value ) , $_type );
        }
    }

    /*		テーブルをリネームする		*/
    /*		※標準搭載のSQLiteではALTERが使えないので、コピーで実装		*/
    /*		p0 : 元テーブル名		*/
    /*		p1 : 新テーブル名		*/
    function renameTable( $_originName , $_newName )
    {
        $result = $this->copyTable( $_originName , $_newName );

        if( $result )
        {
            $result = $this->dropTable( $_originName );

            if( !$result )
            {
                $this->copyTable( $_newName , $_originName );
                $this->dropTable( $_newName );
            }
        }

        return $result;
    }
}

	class CSV
	{
		var $tableName;
		var $lstFileName;
		var $tdbFileName;
		var $indexFileName;
		var $addColumnRow;
		
		var $colNames;
		var $colTypes;
		var $colSizes;

		var $indexs;
		/*		コンストラクタ		*/
		/*		p0 : テーブル名		*/
		/*		p0 : lstファイルのパス		*/
		/*		p0 : tdbファイルのパス		*/
		function __construct( $_tableName , $_lstFileName , $_tdbFileName )
		{
			global $ADD_LST;
			global $index_path;
			
			$this->tableName   = $_tableName;
			$this->lstFileName = $_lstFileName;
			$this->tdbFileName = $_tdbFileName;
			
			$this->indexFileName	= $index_path . str_replace("./tdb/","", $_tdbFileName);
			$this->addColumnRow = 0;
			$this->indexs			= array();

			if( !is_file( $this->lstFileName ) )
				return;

			$handle = fopen( $this->lstFileName , 'rb' );

			if( !$handle )
				return;

			$this->colNames[0] = 'shadow_id';
			$this->colTypes[0] = 'int';
			$this->colSizes[0] = '';

			$this->colNames[1] = 'delete_key';
			$this->colTypes[1] = 'boolean';
			$this->colSizes[1] = '';

			while( $readLine = rtrim( fgets( $handle ) ) )
			{
				$readLine = explode( ',' , $readLine );
				$this->colNames[] = $readLine[ 0 ];
				$this->colTypes[] = $readLine[ 1 ];
				$this->colSizes[] = $readLine[ 2 ];
			}

			fclose( $handle );
			
            if( isset($ADD_LST[$this->tableName]) && is_array($ADD_LST[$this->tableName]) && count($ADD_LST[$this->tableName]) ){
            	foreach( $ADD_LST[ $this->tableName ] as $add_lst ){
            		
					if( !is_file( $add_lst ) ){ continue;}
					if( !($handle = fopen( $add_lst , 'rb' )) ){ continue; }

					while( $readLine = rtrim( fgets( $handle ) ) )
					{
						$readLine = explode( ',' , $readLine );
						$this->colNames[] = $readLine[ 0 ];
						$this->colTypes[] = $readLine[ 1 ];
						$this->colSizes[] = $readLine[ 2 ];
						$this->addColumnRow++;
					}
					fclose( $handle );
            	}
            }
            
            //index loading.
			if( !is_file( $this->indexFileName ) )
				return;

			$handle = fopen( $this->indexFileName , 'rb' );

			if( !$handle )
				return;
		
			while( $readLine = rtrim( fgets( $handle ) ) )
			{
				$readLine = explode( ',' , $readLine );
				
				$index_name = array_shift($readLine);
				$preset_name = array_shift($readLine);
				switch( $preset_name ){
					case 'regist':
						$this->indexs[] = 'INDEX '.$index_name.'(regist)';
						break;
					default:
						$index_type = array_shift($readLine).' INDEX';
						$cnt = count($readLine);
						$index_cols = array();
						for( $i=0;$i<$cnt;$i+=2){
							$index_cols[] = $readLine[$i] .$readLine[$i+1];
						}
						
						$this->indexs[$index_name] = $index_type.' '.$index_name.'('.join(',',$index_cols).')';
						break;
				}
				
			}
		}

		/*		csvが読み込み可能か調べる		*/
		function readable()
		{
			if( !is_file( $this->lstFileName ) || !is_file( $this->tdbFileName ) )
				return false;

			if( !$this->tdbReadHandle )
				$this->tdbReadHandle = fopen( $this->tdbFileName , 'rb' );

			return ( $this->tdbReadHandle ? true : false );
		}

		/*		csvを1行ずつ読んで連想配列にして返す		*/
		/*		定型処理 : 引数はありません		*/
		/*		rt : 連想配列又はnull		*/
		function readRecord()
		{
			if( !$this->readable() )
				return null;

			$readLine = CSV::fget( $this->tdbReadHandle );

			if( !$readLine )
				return null;

			for( $i = 0 ; $i < count( $readLine ) ; $i++ )
				$result[ $this->colNames[ $i ] ] = $readLine[ $i ];

			if($this->addColumnRow){
				for( $i ; $i < count( $readLine ) + $this->addColumnRow ; $i++ )
				$result[ $this->colNames[ $i ] ] = "";
			}
				
			return $result;
		}

		/*		ファイルポインタから行を取得し、CSVフィールドを処理する		*/
		/*		参考URL : http://yossy.iimp.jp/wp/?p=56		*/
		/*		p0 : ファイルハンドル		*/
		/*		p1 : 読み込みサイズ		*/
		/*		p2 : 区切り文字		*/
		/*		p3 : 囲み文字		*/
		/*		rt : 配列又はfalse		*/
		static function fget ( &$handle , $length = null , $d = ',' , $e = '"' )
		{
			$d = preg_quote( $d );
			$e = preg_quote( $e );

			$_line = '';

			while ( $eof != true )
			{
				$_line   .= ( empty( $length ) ? fgets( $handle ) : fgets( $handle , $length ) );
				$itemcnt  = preg_match_all('/' . $e . '/' , $_line , $dummy );

				if( $itemcnt % 2 == 0 )
					$eof = true;
			}

			$_csv_line    = preg_replace( '/(?:\r\n|[\r\n])?$/' , $d , trim( $_line ) );
			$_csv_pattern = '/(' . $e . '[^' . $e . ']*(?:' . $e . $e . '[^' . $e . ']*)*' . $e . '|[^' . $d . ']*)' . $d . '/';

			preg_match_all( $_csv_pattern , $_csv_line , $_csv_matches );

			$_csv_data = $_csv_matches[ 1 ];

			for($_csv_i = 0 ; $_csv_i < count($_csv_data) ; $_csv_i++ )
			{
				$_csv_data[ $_csv_i ] = preg_replace('/^' . $e . '(.*)' . $e . '$/s' , '$1' , $_csv_data[ $_csv_i ] );
				$_csv_data[ $_csv_i ] = str_replace( $e . $e , $e , $_csv_data[ $_csv_i ] );
			}

			return ( empty( $_line ) ? false : $_csv_data );
		}

	}

	class Draw
	{
		/*		ヘッダ描画		*/
		/*		p0 : DBMS名		*/
		static function Head( $_dbmsName )
		{
			print '<head>';
			print '<meta http-equiv="Content-Type" content="text/html; charset=shift_jis" />';
			print '<meta name="robots" content="nofollow,noindex,noarchive" />';
			print '</head>';
			print 'DBMS : ' . $_dbmsName . '<br>';
			print '<hr>';
		}

		/*		ログインフォーム描画		*/
		/*		定型処理 : 引数はありません		*/
		static function loginForm()
		{
			print '<form method="post" action="tool.php">';
			print '<input type="password" name="password" value="">';
			print '<input type="submit" value="ログイン">';
			print '</form>';
		}

		/*		ログイン失敗画面描画		*/
		/*		定型処理 : 引数はありません		*/
		static function loginError()
		{
			print '<p>ログインできません</p>';
		}

		/*		パスワード変更画面描画		*/
		/*		定型処理 : 引数はありません		*/
		static function PasswordChange()
		{
			print '<form method="post" action="tool.php?method=password">';
			print '<input type="password" name="password" value="">';
			print '<input type="submit" value="変更">';
			print '</form>';
		}

		/*		フッタ描画		*/
		/*		定型処理 : 引数はありません		*/
		static function Foot()
		{
			print '<hr><table cellpadding="5" style="border:solid 1px #000"><tr>';
			print '<td style="background-color:#cdc"><a href="tool.php">トップページ</a></td>';
			print '<td>tool.phpのトップ画面へ戻ります</td>';
			print '</tr><tr>';
			print '<td style="background-color:#cdc"><a href="tool.php?method=import_complete">完全インポート</a></td>';
			print '<td>初期セットアップ用。全てのCSVとテンプレート設定をデータベースへ上書きします。※運用中のシステムで実行しないでください！</td>';
			print '</tr><tr>';
			print '<td style="background-color:#cdc"><a href="tool.php?method=export_all">一括エクスポート</a></td>';
			print '<td>データベースにある全てのデータをCSVに複写します。</td>';
			print '</tr><tr>';
			print '<td style="background-color:#cdc"><a href="tool.php?method=backup_all">一括バックアップ</a></td>';
			print '<td>データベース上に運用データのバックアップ領域を作ります。<br />※バックアップ領域は1つのテーブルに1つだけとなります。古いバックアップ領域がある場合は上書きされます。</td>';
			print '</tr><tr>';
			print '<td style="background-color:#cdc"><a href="tool.php?method=restore_all">一括復元</a></td>';
			print '<td>現在の運用データをバックアップ時点のデータに戻します。※バックアップの日付を確認してから実行してください！</td>';
			print '</tr><tr>';
			print '<td style="background-color:#cdc"><a href="tool.php?method=struct_all">一括再編成</a></td>';
			print '<td>運用データを保持したまま、テーブル構造をCSVの記述に合わせて変更します。アップデート時や、テーブルに項目を追加した場合などに実行してください。<br />※項目を削除して再編成すると、その項目に保存されている運用データは削除されますのでご注意ください。</td>';
			print '</tr><tr>';
			print '<td style="background-color:#cdc"><a href="tool.php?method=add_template_auto">自動構築(テンプレート)</a></td>';
			print '<td>テンプレート設定をデータベースへ上書きします。アップデート時や、テンプレートの追加・変更時に実行してください。</td>';
			print '</tr><tr>';
//			print '<td style="background-color:#cdc"><a href="tool.php?method=create_tables">自動生成(テーブルカウント)</a></td>';
//			print '<td>システムの動作がおかしい場合は、こちらを実行することで改善される可能性があります。</td>';
//			print '</tr><tr>';
			print '<td style="background-color:#cdc"><a href="tool.php?method=template_map">デザインテンプレートの確認</a></td>';
			print '<td>ユーザー毎のテンプレート設定の一覧を表示します。</td>';
			print '</tr><tr>';
			print '<td style="background-color:#cdc"><a href="tool.php?method=password">パスワード変更</a></td>';
			print '<td>tool.phpのログインパスワードを変更します。</td>';
			print '</tr><tr>';
			print '<td style="background-color:#cdc"><a href="tool.php?phpinfo=true">phpinfoを表示</a></td>';
			print '<td>phpの環境設定情報を表示します。</td>';
			print '</tr><tr>';
//			print '<td style="background-color:#cdc"><a href="tool.php?method=file_delete">不要ファイルの削除</a></td>';
//			print '<td>使用されていないファイルを削除します。</td>';
//			print '</tr></table>';
		}

		/*		テーブル一覧描画		*/
		/*		p0 : テーブル名一覧		*/
		/*		p1 : lst一覧		*/
		/*		p2 : tdb一覧		*/
		/*		p3 : 既存のテーブル一覧		*/
		/*		p4 : バックアップ日時		*/
		static function TableSelect( $_tables , $_lsts , $_tdbs , $_exists , $_backups )
		{
			print '<table cellpadding="5" style="border:solid 1px #000">';

			print '<tr>';
			print '<th>テーブル名</th>';
			print '<th>インポート<br>(CSVからDBへ)</th>';
			print '<th>エクスポート<br>(DBからCSVへ)</th>';
			print '<th>再編成</th>';
			print '<th>バックアップ</th>';
			print '<th>復元</th>';
			print '<th>DBとCSVを比較</th>';
			print '<th>TDBをダウンロード</th>';
			print '</tr>';

			foreach( $_tables as $name )
			{
				print '<tr id="row' . ++$row . '" onmouseover="document.getElementById(\'row' . $row . '\').style.backgroundColor=\'#cdc\'" onmouseout="document.getElementById(\'row' . $row . '\').style.backgroundColor=\'#fff\'">';
				print '<td>' . $name . '</td>';

				/*		インポート		*/
				if( is_file(PathUtil::ModifyLSTFilePath($_lsts[ $name ]) ) && is_file(PathUtil::ModifyTDBFilePath($_tdbs[ $name ]) ) )
					print '<td><a href="tool.php?method=import&table=' . $name . '">インポート</a></td>';
				else
					print '<td style="background-color:#333;color:#fff;">csv not found</td>';

				/*		エクスポート/再編成/バックアップ		*/
				if( in_array( strtolower( $name ) , $_exists ) )
				{
					print '<td><a href="tool.php?method=export&table=' . $name . '">エクスポート</a></td>';
					print '<td><a href="tool.php?method=struct&table=' . $name . '">再編成</a></td>';
					print '<td><a href="tool.php?method=backup&table=' . $name . '">バックアップ</a></td>';
				}
				else
					print '<td colspan="3" style="background-color:#333;color:#fff;">table not found</td>';

				/*		復元		*/
				if( $_backups[ strtolower( $name ) ] )
					print '<td><a href="tool.php?method=restore&table=' . $name . '">' . date( 'Y/n/j G:i:s' , $_backups[ strtolower( $name ) ] ) . '</a></td>';
				else
					print '<td style="background-color:#333;color:#fff;">backup not found</td>';

				/*		比較		*/
				if( !in_array( strtolower( $name ) , $_exists ) )
					print '<td style="background-color:#333;color:#fff;">table not found</td>';
				else if( !is_file(PathUtil::ModifyLSTFilePath ($_lsts[ $name ]) ) || !is_file(PathUtil::ModifyTDBFilePath ($_tdbs[ $name ]) ) )
					print '<td style="background-color:#333;color:#fff;">csv not found</td>';
				else
					print '<td><a href="tool.php?method=marge&table=' . $name . '">比較</a></td>';

				/*		ダウンロード		*/
				if( is_file(PathUtil::ModifyTDBFilePath($_tdbs[ $name ]) ) )
					print '<td><a href="tool.php?method=download&table=' . $name . '">ダウンロード</a></td>';
				else
					print '<td style="background-color:#333;color:#fff;">csv not found</td>';

				print '</tr>';
			}

			print '</table>';
		}

		/*		マージ結果画面のヘッダを描画		*/
		/*		p0 : 処理するカラム一覧		*/
		static function MargeHead( $_columns )
		{
			print '<p>青は相違項目、赤は置換対象となった項目です。</p>';
			print '<table cellpadding="5" style="border:solid 1px #000;margin:10px 0px;">';
			print '<tr><th>&nbsp;</th>';

			if( !in_array( 'shadow_id' , $_columns ) )
				array_unshift( $_columns , 'shadow_id' );

			foreach( $_columns as $name )
				print '<th style="background-color:#' . ( $count++ % 2 ? 'eee' : 'cdc' ) . '">' . $name . '</th>';

			print '</tr>';
		}

		/*		マージ結果画面のフッタを描画		*/
		/*		定型処理 : 引数はありません		*/
		static function MargeFoot()
		{
			print '</table>';
		}

		/*		マージ項目選択画面描画		*/
		/*		p0 : テーブル名一覧		*/
		/*		p1 : lst		*/
		/*		p2 : tdb		*/
		static function MargeSelect( $_tableName , $_lstFile , $_tdbFile )
		{
			$csv = new CSV( $_tableName , $_lstFile , $_tdbFile );

			print '<form method="get" action="tool.php">';
			print '<input type="hidden" name="method" value="marge">';
			print '<input type="hidden" name="table" value="' . $_tableName . '">';
			print '<input type="hidden" name="run" value="true">';
			print '<table cellpadding="5" style="border:solid 1px #000">';

			print '<tr>';
			print '<th>カラム名</th>';
			print '<th>相違行の表示</th>';
			print '<th>相違行を置換<br>(CSVからDBへ)</th>';
			print '</tr>';

			foreach( $csv->colNames as $name )
			{
				if( 'shadow_id' == $name )
					continue;

				print '<tr style="background-color:#' . ( $count++ % 2 ? 'fff' : 'cdc' ) . '">';
				print '<td>' . $name . '</td>';
				print '<td><input type="checkbox" checked name="col_' . $name . '[]" value="view_diff">表示</td>';
				print '<td><input type="checkbox" name="col_' . $name . '[]" value="rep_diff">置換</td>';
				print '</tr>';
			}

			print '</table><br><input type="submit" value="実行"></form>';
		}

		/*		テンプレートマップを描画		*/
		static function TemplateMap( $_userType , $_targets , $_map )
		{
			print '<table cellpadding="5" width="100%" style="border:solid 1px #000;margin:30px 0px"><tr style="background-color:#9cc"><th colspan="6"><a name="' . ( $_userType ? $_userType : 'label' ) . '">【' . ( $_userType ? $_userType : 'label' ) . '】</a></th></tr>';
			print '<tr><th>target</th><th>label</th><td style="background-color:#ffc">ACTIVE_NONE(1)</td><td style="background-color:#fc9">ACTIVE_ACTIVATE(2)</td><td style="background-color:#fc3">ACTIVE_ACCEPT(4)</td><td style="background-color:#f90">ACTIVE_DENY(8)</td></tr>';

			foreach( $_targets as $target )
			{
				$targetBG = ( $targetRow++ % 2 ? '#ccd' : '#fff' );
				$labels = $_map->labels( $_userType , $target );

				foreach( $labels as $label )
				{
					/*		非色分け		*/
//					print '<tr style="background-color:' . ( $row++ % 2 ? '#fff' : '#cdc' ) . '"><td style="background-color:' . $targetBG . '">' . $target . '</td>';

					/*		色分け		*/
					if( FALSE != strstr( $label , 'REGIST' ) )
						$color = '#f93';
					else if( FALSE != strstr( $label , 'EDIT' ) )
						$color = '#fc6';
					else if( FALSE != strstr( $label , 'DELETE' ) )
						$color = '#f66';
					else if( FALSE != strstr( $label , 'SEARCH' ) )
						$color = '#9cf';
					else if( FALSE != strstr( $label , 'INFO' ) )
						$color = '#afa';
					else if( FALSE != strstr( $label , 'OTHER' ) || FALSE != strstr( $label , 'INCLUDE' ) )
						$color = '#999';
					else
						$color = '#fff';

					print '<tr style="background-color:' . $color . '"><td style="background-color:' . $targetBG . '">' . $target . '</td>';
					print '<td>' . $label . '</td>';

					$files = $_map->files( $_userType , $target , $label );

					$drawer = new Drawer();

					for( $i = 0 ; $i < 4 ; $i++ )
					{
						$duplicate = $_map->duplicate( $_userType , $target , $label , pow( 2 , $i ) );
						$drawer->push( $files[ $i ] , $duplicate );
					}

					print $drawer->string();
					print '</tr>';
				}
			}

			print '</table>';
		}

		/*		インポート確認画面描画		*/
		/*		p0 : テーブル名		*/
		static function ImportCheck( $_tableName )
		{
			print '<p><strong style="color:#f00">' . $_tableName . ' をインポートしようとしています！</strong></p>';
			print '<p>インポートが成功した場合、現在のテーブルが置き換えられます。<br>問題がないことを確認した上で実行してください。</p>';
			print '<p><a href="tool.php?method=import&table=' . $_tableName . '&run=true">' . $_tableName . 'のインポートを実行する</a></p>';
		}

		/*		TDBインポート確認画面描画		*/
		/*		定型処理 : 引数はありません		*/
		static function ImportAllCheck()
		{
			print '<p><strong style="color:#f00">TDBインポートを実行しようとしています！</strong></p>';
			print '<p>全てのテーブルが置き換えられます。<br>問題がないことを確認した上で実行してください。</p>';
			print '<p><a href="tool.php?method=import_all&run=true">TDBインポートを実行する</a></p>';
		}

		/*		完全インポート確認画面描画		*/
		/*		定型処理 : 引数はありません		*/
		static function ImportCompleteCheck()
		{
			print '<p><strong style="color:#f00">完全インポートを実行しようとしています！</strong></p>';
			print '<p>全てのテーブルが置き換えられます。<br>問題がないことを確認した上で実行してください。</p>';
			print '<p><a href="tool.php?method=import_complete&run=true">完全インポートを実行する</a></p>';
		}

		/*		インポート完了画面描画		*/
		/*		p0 : テーブル名		*/
		static function ImportComplete( $_tableName )
		{
			print '<p>' . $_tableName . 'をインポートしました。元のテーブルはバックアップされます。</p>';
		}

		/*		インポート失敗画面描画		*/
		/*		p0 : テーブル名		*/
		static function ImportError( $_tableName )
		{
			print '<p><strong style="color:#f00">' . $_tableName . 'をインポートできませんでした。</strong><br>（' . $_tableName . 'の現在のデータは変更されていません。）</p>';
		}

		/*		エクスポート完了画面描画		*/
		/*		p0 : テーブル名		*/
		static function ExportComplete( $_tableName )
		{
			print '<p>' . $_tableName . 'をエクスポートしました。</p>';
		}

		/*		エクスポート失敗画面描画		*/
		/*		p0 : テーブル名		*/
		static function ExportError( $_tableName )
		{
			print '<p><strong style="color:#f00">' . $_tableName . 'をエクスポートできませんでした。</strong></p>';
		}

		/*		バックアップ完了画面描画		*/
		/*		p0 : テーブル名		*/
		static function BackupComplete( $_tableName )
		{
			print '<p>' . $_tableName . 'をバックアップしました。</p>';
		}

		/*		バックアップ失敗画面描画		*/
		/*		p0 : テーブル名		*/
		static function BackupError( $_tableName )
		{
			print '<p><strong style="color:#f00">' . $_tableName . 'をバックアップできませんでした。</strong></p>';
		}

		/*		復元確認画面描画		*/
		/*		p0 : テーブル名		*/
		static function RestoreCheck( $_tableName )
		{
			print '<p><strong style="color:#f00">' . $_tableName . ' を復元しようとしています！</strong></p>';
			print '<p>復元が成功した場合、現在のテーブルが置き換えられます。<br>問題がないことを確認した上で実行してください。</p>';
			print '<p><a href="tool.php?method=restore&table=' . $_tableName . '&run=true">' . $_tableName . 'の復元を実行する</a></p>';
		}

		/*		一括復元確認画面描画		*/
		/*		定型処理 : 引数はありません		*/
		static function RestoreAllCheck()
		{
			print '<p><strong style="color:#f00">一括復元を実行しようとしています！</strong></p>';
			print '<p>全てのテーブルが置き換えられます。<br>問題がないことを確認した上で実行してください。</p>';
			print '<p><a href="tool.php?method=restore_all&run=true">一括復元を実行する</a></p>';
		}

		/*		復元完了画面描画		*/
		/*		p0 : テーブル名		*/
		static function RestoreComplete( $_tableName )
		{
			print '<p>' . $_tableName . 'を復元しました。</p>';
		}

		/*		復元失敗画面描画		*/
		/*		p0 : テーブル名		*/
		static function RestoreError( $_tableName )
		{
			print '<p><strong style="color:#f00">' . $_tableName . 'を復元できませんでした。</strong><br>（' . $_tableName . 'の現在のデータは変更されていません。）</p>';
		}

		/*		再編成確認画面描画		*/
		/*		p0 : テーブル名		*/
		static function StructCheck( $_tableName )
		{
			print '<p><strong style="color:#f00">' . $_tableName . ' を再編成しようとしています！</strong></p>';
			print '<p>DB上のテーブルのカラムをCSVに合わせて変更します。<br/>変更が成功した場合、現在のテーブルが置き換えられます。<br>問題がないことを確認した上で実行してください。</p>';
			print '<p><a href="tool.php?method=struct&table=' . $_tableName . '&run=true">' . $_tableName . 'の再編成を実行する</a></p>';
		}

		/*		一括再編成確認画面描画		*/
		/*		p0 : テーブル名		*/
		static function StructAllCheck()
		{
			print '<p><strong style="color:#f00">一括再編成を実行しようとしています！</strong></p>';
			print '<p>全てのテーブルが置き換えられます。<br>問題がないことを確認した上で実行してください。</p>';
			print '<p><a href="tool.php?method=struct_all&run=true">一括再編成を実行する</a></p>';
		}

		/*		再編成失敗画面描画		*/
		/*		p0 : テーブル名		*/
		static function StructError( $_tableName )
		{
			print '<p><strong style="color:#f00">' . $_tableName . 'の再編成に失敗しました。</strong><br>（' . $_tableName . 'の現在のデータは変更されていません。）</p>';
		}

		/*		再編成完了画面描画		*/
		/*		p0 : テーブル名		*/
		static function StructComplete( $_tableName )
		{
			print '<p>' . $_tableName . 'を再編成しました。</p>';
		}

		/*		マージ処理内容を描画		*/
		/*		p0 : CSVデータ		*/
		/*		p1 : DBデータ		*/
		/*		p2 : 置換されたカラム一覧		*/
		static function MargeResult( $_csvRecord , $_dbRecord , $_replacedColumns )
		{
			$differColumns = Array();

			foreach( $_dbRecord as $key => $value )
			{
				if( $value != $_csvRecord[ $key ] )
					$differColumns[] = $key;
			}

			if( !$differColumns )
				return;

			print '<tr>';
			print '<th style="background-color:#cdc">CSV</th>';
			print '<td rowspan="2" style="background-color:#333;color=#fff;text-align:center">' . $_csvRecord[ 'shadow_id' ] . '</td>';

			$columns = array_keys( $_dbRecord );

			foreach( $columns as $name )
			{
				if( 'shadow_id' == $name )
					continue;

				$count++;

				if( in_array( $name , $_replacedColumns ) )
					print '<td style="background-color:#f66">' . $_csvRecord[ $name ] . '</td>';
				else if( in_array( $name , $differColumns ) )
					print '<td style="background-color:#3cf">' . $_csvRecord[ $name ] . '</td>';
				else
					print '<td rowspan="2" style="background-color:#' . ( $count % 2 ? 'eee' : 'cdc' ) . '">' . $_csvRecord[ $name ] . '</td>';
			}

			print '</tr><tr>';
			print '<th style="background-color:#9a9">DB</th>';
			$count = 0;

			foreach( $columns as $name )
			{
				if( 'shadow_id' == $name )
					continue;

				if( in_array( $name , $_replacedColumns ) )
					print '<td style="background-color:#c66">' . $_dbRecord[ $name ] . '</td>';
				else if( in_array( $name , $differColumns ) )
					print '<td style="background-color:#09f">' . $_dbRecord[ $name ] . '</td>';
				else
					continue;
			}

			print '</tr>';
		}

		/*		DBMS接続エラー描画		*/
		/*		定型処理 : 引数はありません		*/
		static function SQLConnectError()
		{
			print '<p>DBMSに接続できません</p>';
		}

		/*		SQL実行失敗時のエラーメッセージ描画		*/
		/*		p0 : SqlConnectオブジェクト		*/
		static function SQLError( $_sqlObject )
		{
			print '<div style="background-color:#ffc;padding:5px;border:solid 1px #333">';
			print '<p><strong style="color:#f00">次のSQL文の実行でエラーが発生しました。</strong><br>' . $_sqlObject->lastQuery . '</p>';
			print '<p><strong>SQL エラー メッセージ : </strong><br>' . $_sqlObject->error() . '</p>';
			print '</div>';
		}
	}

	class templateMap
	{
		/*		コンストラクタ		*/
		/*		p0 : SQLConnectオブジェクト		*/
		function __construct( $_sqlObject )
		{
			$this->sql = $_sqlObject;
		}

		/*		ユーザータイプ一覧を取得		*/
		/*		定型処理 : 引数はありません		*/
		function userTypes()
		{
			$resource = $this->sql->run( 'SELECT user_type FROM template' );

			while( $result = $this->sql->fetch( $resource ) )
			{
				foreach( explode( '/' , $result[ 0 ] ) as $value )
					$userTypes[ $value ] = 1;
			}

			return array_keys( $userTypes );
		}

		/*		ターゲット一覧を取得		*/
		/*		p0 : ユーザータイプ		*/
		function targets( $_userType )
		{
			$resource = $this->sql->run( 'SELECT target_type FROM template WHERE user_type LIKE "%/' . $_userType . '/%"' );

			while( $result = $this->sql->fetch( $resource ) )
				$targets[ $result[ 0 ] ] = 1;

			return array_keys( $targets );
		}

		/*		ラベル一覧を取得		*/
		/*		p0 : ユーザータイプ		*/
		/*		p1 : ターゲット		*/
		function labels( $_userType , $_target )
		{
			$resource = $this->sql->run( 'SELECT label FROM template WHERE user_type LIKE "%/' . $_userType . '/%" AND target_type = "' . $_target . '" ORDER BY target_type asc , label asc' );

			while( $result = $this->sql->fetch( $resource ) )
				$labels[ $result[ 0 ] ] = 1;

			return array_keys( $labels );
		}

		/*		ファイル一覧を取得		*/
		/*		p0 : ユーザータイプ		*/
		/*		p1 : ターゲット		*/
		/*		p2 : ラベル		*/
		function files( $_userType , $_target , $_label )
		{
			$resource = $this->sql->run( 'SELECT file , activate FROM template WHERE user_type LIKE "%/' . $_userType . '/%" AND target_type = "' . $_target . '" AND label = "' . $_label . '" ORDER BY target_type asc , label asc' );

			while( $result = $this->sql->fetch( $resource , 'assoc' ) )
			{
				if( 1 & $result[ 'activate' ] )
					$files[ 0 ][] = $result[ 'file' ];
				if( 2 & $result[ 'activate' ] )
					$files[ 1 ][] = $result[ 'file' ];
				if( 4 & $result[ 'activate' ] )
					$files[ 2 ][] = $result[ 'file' ];
				if( 8 & $result[ 'activate' ] )
					$files[ 3 ][] = $result[ 'file' ];
			}

			return $files;
		}

		function duplicate( $_userType , $_target , $_label , $_activate )
		{
			$resource = $this->sql->run( 'SELECT owner , activate FROM template WHERE user_type LIKE "%/' . $_userType . '/%" AND target_type = "' . $_target . '" AND label = "' . $_label . '" AND activate & ' . $_activate . ' = ' . $_activate );

			while( $result = $this->sql->fetch( $resource ) )
			{
				if( isset( $owner[ $result[ 0 ] ] ) )
					return true;

				$owner[ $result[ 0 ] ] = 1;
			}

			return false;
		}
	}

	class drawer
	{
		function push( $_files , $_duplicate )
		{
			if( !$this->length )
			{
				$this->files  = $_files;
				$this->dup    = $_duplicate;
				$this->length = 1;
				return;
			}
			else if( $this->dup == $_duplicate )
			{
				if( is_array( $this->files ) && is_array( $_files ) )
				{
					if( !count( array_diff( $this->files , $_files ) ) && !count( array_diff( $_files , $this->files ) ) )
					{
						$this->length++;
						return;
					}
				}
				else
				{
					if( !$this->files && !$_files )
					{
						$this->length++;
						return;
					}
				}
			}

			$this->rfiles[]  = $this->files;
			$this->rdup[]    = $this->dup;
			$this->rlength[] = $this->length;

			$this->files  = $_files;
			$this->dup    = $_duplicate;
			$this->length = 1;
		}

		function string()
		{
			$this->rfiles[]  = $this->files;
			$this->rdup[]    = $this->dup;
			$this->rlength[] = $this->length;

			$color = Array( '#ffc' , '#fc9' , '#fc3' , '#f90' );
			$index = 0;

			for( $i = 0 ; $i < count( $this->rfiles ) ; $i++ )
			{
				if( is_array( $this->rfiles[ $i ] ) )
				{
					if( $this->rdup[ $i ] )
						print '<td colspan="' . $this->rlength[ $i ] . '" style="border:solid 1px #666;background-color:#f00;color:#fff;font-weight:bold;">' . implode( '<br>' , $this->fileCheck( $this->rfiles[ $i ] ) ) . '</td>';
					else
						print '<td colspan="' . $this->rlength[ $i ] . '" style="border:solid 1px #666;background-color:' . $color[ $index ] . '">' . implode( '<br>' , $this->fileCheck( $this->rfiles[ $i ] ) ) . '</td>';
				}
				else
					print '<td colspan="' . $this->rlength[ $i ] . '" style="background-color:#fff">&nbsp;</td>';

				$index += $this->rlength[ $i ];
			}
		}

		function fileCheck( $_files )
		{
			global $template_path;

			for ( $i = 0 ; $i < count( $_files ) ; $i++ )
			{
				if( !is_file( $template_path . $_files[ $i ] ) )
					$_files[ $i ] .= '　<span style="background-color:#000;color:#fff;font-weight:bold;padding:2px;">No File!</span>';
				else if( 0 === filesize( $template_path . $_files[ $i ] ) )
					$_files[ $i ] .= '　<span style="background-color:#f00;color:#fff;font-weight:bold;padding:2px;">Empty File!</span>';
			}

			return $_files;
		}
	}
?>
