<?php

	ob_start();

	try
	{
		include_once 'custom/head_main.php';

		if( 'admin' != $loginUserType ) //管理者でない場合
			{ throw new IllegalAccessException( '不正なアクセスです' ); }

		if( !isset( $_POST[ 'post' ] ) ) //パラメータが送信されていない場合
			{ throw new IllegalAccessException( '不正なアクセスです' ); }

		$count = ( int )( $_POST[ 'delete_day' ] );

		if( 0 >= $count ) //パラメータが送信されていない場合
			{ throw new InvalidArgumentException( '引数 delete_day は無効です[' . $_POST[ 'delete_day' ] . ']' ); }

		$year  = date( 'Y' );
		$month = date( 'n' );
		$day   = date( 'j' );

		$time = mktime( 0 , 0 , 0 , $month , $day - $count , $year );

		print System::getHead( $gm , $loginUserType , $loginUserRank );

		$db    = GMList::getDB( 'access' );
		$table = $db->getTable();
		$table = $db->searchTable( $table , 'regist' , '<' , $time );

		$db->realDeleteTable( $table );

		$_GET[ 'type' ] = 'access';
		$sys            = SystemUtil::getSystem( $_GET[ 'type' ] );
		$dummy          = Array();

		$sys->drawDeleteComp( $gm , $dummy , $loginUserType , $loginUserRank );

		print System::getFoot( $gm , $loginUserType , $loginUserRank );
	}
	catch( Exception $e_ )
	{
		ob_end_clean();

		//エラーメッセージをログに出力
		$errorManager = new ErrorManager();
		$errorMessage = $errorManager->GetExceptionStr( $e_ );

		$errorManager->OutputErrorLog( $errorMessage );

		//例外に応じてエラーページを出力
		$className = get_class( $e_ );
		ExceptionManager::DrawErrorPage($className );
	}

	ob_end_flush();
