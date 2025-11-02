<?php

	try
	{
		include_once 'custom/head_main.php';

		if( $NOT_LOGIN_USER_TYPE == $loginUserType ) //ログインしていない場合
			{ $drawGM = $gm[ 'system' ]; }
		else //ログインしている場合
			{ $drawGM = $gm[ $loginUserType ]; }

		print System::getHead( $gm , $loginUserType , $loginUserRank );

		if( array_key_exists( 'token' , $_GET ) ) //トークンがGETで送信されている場合
			{ $_POST[ 'token' ] = $_GET[ 'token' ]; }

		if( !array_key_exists( 'token' , $_POST ) ) //トークンが送信されていない場合
			{ throw new InvalidArgumentException( '引数 token は無効です' ); }

		//ログイン履歴を探す
		$aDB   = GMList::getDB( 'admin' );
		$aRec  = $aDB->selectRecord( 'ADMIN' );
		$aID   = $aDB->getData( $aRec , 'mail' );

		$db    = GMList::getDB( 'accountLock' );
		$table = $db->getTable();
		$table = $db->searchTable( $table , 'login_id' , '=' , $aID );
		$table = $db->searchTable( $table , 'unlock_token' , '=' , $_POST[ 'token' ] );
		$row   = $db->getRow( $table );

		if( !$row ) //管理者のロック履歴がない場合
			{ throw new RuntimeException( 'unlock.phpの処理を完了できません' ); }

		//パスワードを確認する
		$rec      = $db->getRecord( $table , 0 );
		$password = $db->getData( $rec , 'onetime_password' );

		if( array_key_exists( 'password' , $_POST ) ) //パスワードが送信されている場合
		{
			if( $password == $_POST[ 'password' ] ) //パスワードが一致する場合
			{
				$db->setData( $rec , 'try_time' , '' );
				$db->updateRecord( $rec );

				Template::drawTemplate( $drawGM , $rec , '' , $loginUserRank , 'accountLock' , 'ACCOUNT_UNLOCK_SUCCESS_PAGE_DESIGN' );
			}
			else //パスワードが一致しない場合
				{ Template::drawTemplate( $drawGM , $rec , '' , $loginUserRank , 'accountLock' , 'ACCOUNT_UNLOCK_FAILED_PAGE_DESIGN' ); }
		}
		else //パスワードが送信されていない場合
			{ Template::drawTemplate( $drawGM , $rec , '' , $loginUserRank , 'accountLock' , 'ACCOUNT_UNLOCK_PAGE_DESIGN' ); }

		print System::getFoot( $gm , $loginUserType , $loginUserRank );
	}
	catch( Exception $e )
	{
		ob_end_clean();

		//エラーメッセージをログに出力
		$errorManager = new ErrorManager();
		$errorMessage = $errorManager->GetExceptionStr( $e );

		$errorManager->OutputErrorLog( $errorMessage );

		//例外に応じてエラーページを出力
		$className = get_class( $e );
		ExceptionManager::DrawErrorPage($className );
	}
