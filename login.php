<?php

/*******************************************************************************************************
 * <PRE>
 *
 * login.php - 専用プログラム
 * ログインページを出力します。
 *
 * </PRE>
 *******************************************************************************************************/

	ob_start();

	try
	{
		include_once 'custom/head_main.php';

		//パラメータチェック
		ConceptCheck::IsScalar( $_GET , Array( 'logout' , 'run' , 'type' ) );
		ConceptCheck::IsScalar( $_POST , Array( 'type' , $LOGIN_KEY_FORM_NAME , $LOGIN_PASSWD_FORM_NAME ) );
		//パラメータチェックここまで

		print System::getHead($gm,$loginUserType,$loginUserRank);

		if( isset( $_GET['logout'] )  )
		{// ログアウト処理モード
			$sys	 = SystemUtil::getSystem( $loginUserType );
			if( $sys->logoutProc( $loginUserType ) ){
				SystemUtil::logout($loginUserType);
			}
		
			SystemUtil::innerLocation( "index.php" );
		}
		else if( accountLockLogic::isTryOver() )
			{ Template::drawTemplate( $gm[ 'system' ] , null , $loginUserType , $loginUserRank , '' , 'LOGIN_LOCK_DESIGN' ); }
		else
		{
			if( isset( $_POST[ $LOGIN_KEY_FORM_NAME ] ) || $_GET['run'] == 'true' )
			{
				$login	 = false;
				$id 	 = "";
				if(isset( $_POST[ $LOGIN_PASSWD_FORM_NAME ] )){
					// ログインパスワードなどがPOSTされている場合。
					if( isset($_GET['type']) || isset($_POST['type']) )
					{
						if( isset($_POST['type']) ){ $_GET['type'] = $_POST['type']; }
						$id = SystemUtil::login_check( $_GET['type'] , $_POST[ $LOGIN_KEY_FORM_NAME ] , $_POST[ $LOGIN_PASSWD_FORM_NAME ] );
						if($id){
							$login = true;
							$loginUserType=$_GET['type'];
						}
					}
					else
					{
						for($i=0; $i<count($TABLE_NAME); $i++)
						{
							if(  $THIS_TABLE_IS_USERDATA[ $TABLE_NAME[$i] ]  )
							{
								$id = SystemUtil::login_check( $TABLE_NAME[$i], $_POST[ $LOGIN_KEY_FORM_NAME ], $_POST[ $LOGIN_PASSWD_FORM_NAME ] );
								if($id){
									$login = true;
									$loginUserType=$TABLE_NAME[$i];
									break;
								}
							}
						}
					}
				}
				$sys	 = SystemUtil::getSystem( $loginUserType );
				$login = $sys->loginProc( $login , $loginUserType , $id );
		
				if( $login )
				{
					accountLockLogic::resetTryCount();
					SystemUtil::login($id,$loginUserType);
					SystemUtil::innerLocation( "index.php" );
				}
				else
					{ accountLockLogic::addTryCount(); }
				
				Template::drawTemplate( $gm[ $loginUserType ] , $rec , $loginUserType , $loginUserRank , '' , 'LOGIN_FALED_DESIGN' );
			}
			else
			{
				if( $loginUserType != $NOT_LOGIN_USER_TYPE ){
					Template::drawTemplate( $gm[ $loginUserType ] , $rec , $loginUserType , $loginUserRank , '' , 'LOGIN_PAGE_DESIGN' );
				}else{
				Template::drawTemplate( $gm[ 'system' ] , $rec , $loginUserType , $loginUserRank , '' , 'LOGIN_PAGE_DESIGN' );
				}
			}
		
		}
		
		print System::getFoot($gm,$loginUserType,$loginUserRank);
	}
	catch( Exception $e_ )
	{
		ob_end_clean();

		$errorManager = new ErrorManager();
		$errorManager->OutputErrorLog( $errorManager->GetExceptionStr( $e_ ) );

		ExceptionManager::DrawErrorPage(get_class( $e_ ) );
	}

	ob_end_flush();
?>