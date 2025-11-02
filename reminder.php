<?php
	ob_start();
	
	try
	{
		include_once 'custom/head_main.php';

		if( $_GET[ 'token' ] ) //リセットトークンが送信されている場合
		{
			if( !$_POST[ 'post' ] ) //フォームが送信されている場合
				{ drawResetForm(); }
			else //フォームが送信されていない場合
				{ doReset(); }

			exit;
		}

		//パラメータチェック
		ConceptCheck::IsEssential( $_GET , Array( 'type' ) );
		ConceptCheck::IsNotNull( $_GET , Array( 'type' ) );
		ConceptCheck::IsScalar( $_GET , Array( 'type' ) );

		if( !$gm[ $_GET[ 'type' ] ] )
			throw new IllegalAccessException( $_GET[ 'type' ] . 'は定義されていません' );

		if( !$THIS_TABLE_IS_USERDATA[ $_GET[ 'type' ] ] )
			throw new IllegalAccessException( $_GET[ 'type' ] . 'はユーザーテーブルではありません' );

		if( $_GET[ 'type' ] == 'admin' )
				throw new IllegalAccessException( $_GET[ 'type' ] . 'は操作できません' );
		//パラメータチェックここまで

		print System::getHead($gm,$loginUserType,$loginUserRank);
	
		if( !isset($_POST['post']) )
		{// 入力フォーム
			$gm['system']->addHiddenForm( 'post', 'true' );
	        $gm['system']->setVariable( 'error_msg' , "");
	        Template::drawTemplate( $gm['system'], null, 'reminder', $loginUserRank, $_GET['type'], 'SEND_FORM_DESIGN', 'reminder.php?type='.$_GET['type']);
	    }
		else
		{// 入力内容確認
			$check			 = true;
			// 汎用的な空欄チェック
			$html = "";
			if( $_POST['mail'] == '' )
			{// メールアドレスが入力されていない場合
				$html .= Template::getTemplateString( $gm['system'], null, 'reminder', $loginUserRank, $_GET['type'], 'SEND_FALED_DESIGN', false ,null ,'head' );
				$html .= Template::getTemplateString( $gm['system'], null, 'reminder', $loginUserRank, $_GET['type'], 'SEND_FALED_DESIGN', false ,null ,'mail' );
				$check	 = false;
			}
			else
			{// 入力されたメールアドレスのレコードが存在するか確認
				$check	 = false;
				if( isset($_GET['type']) ){
						if(  $THIS_TABLE_IS_USERDATA[ $_GET['type'] ]  )
						{
							$db		 = $gm[ $_GET['type'] ]->getDB();
							$table	 = $db->getTable();
							$table	 = $db->searchTable( $table, 'mail', '=', $_POST[ 'mail' ] );
							$table	 = $db->searchTable(  $table, 'activate', '!', $ACTIVE_NONE  );
							if( $db->getRow($table) != 0 )
							{// レコードが存在する場合メールを送信
								$rec			 = $db->getRecord( $table, 0 );
								$id				 = $db->getData( $rec , 'id' );
								$pass            = $db->getData( $rec , 'pass' );

								if( 'resetter' == $REMINDER_MODE || 'SHA' == $PASSWORD_MODE || 'SHA' == SystemUtil::getPasswordEncode( $pass ) ) //リセット方式の場合
								{
									$token = md5( rand() );
									$fp    = fopen( 'file/reminder/' . $token , 'wb' );

									fputs( $fp , implode( ',' , Array( $_GET[ 'type' ] , $id ) ) );
									fclose( $fp );

									$gm[ $_GET[ 'type' ] ]->setVariable( 'token' , $token );
									$template = Template::getTemplate( 'resetter', $loginUserRank, $_GET['type'], 'SEND_MAIL');
									$sub      = $gm[ $_GET[ 'type' ] ]->getString( $template , $rec , 'submect' );
									$main     = $gm[ $_GET[ 'type' ] ]->getString( $template , $rec , 'main' );

									Mail::sendString( $sub , $main , $MAILSEND_ADDRES, $_POST['mail'], $MAILSEND_NAMES );
								}
								else
								{
									Mail::send( Template::getTemplate( 'reminder', $loginUserRank, $_GET['type'], 'SEND_MAIL') , $MAILSEND_ADDRES, $_POST['mail'], $gm[ $_GET['type'] ], $rec, $MAILSEND_NAMES );
								}
								Template::drawTemplate( $gm['system'], null, 'reminder', $loginUserRank, $_GET['type'], 'SEND_COMP_DESIGN', 'reminder.php?type='.$_GET['type']);
								$check			 = true;
							}
						}
				}else{
					for($i=0; $i<count($TABLE_NAME); $i++)
					{
						if(  $THIS_TABLE_IS_USERDATA[ $TABLE_NAME[$i] ]  )
						{
							$db		 = $gm[ $TABLE_NAME[$i] ]->getDB();
							$table	 = $db->getTable();
							$table	 = $db->searchTable( $table, 'mail', '=', $_POST[ 'mail' ] );
							$table	 = $db->searchTable(  $table, 'activate', '!', $ACTIVE_NONE  );
							if( $db->getRow($table) != 0 )
							{// レコードが存在する場合メールを送信
								$rec			 = $db->getRecord( $table, 0 );
								Mail::send( Template::getTemplate( 'reminder', $loginUserRank, $_GET['type'], 'SEND_MAIL') , $MAILSEND_ADDRES, $_POST['mail'], $gm[ $TABLE_NAME[$i] ], $rec, $MAILSEND_NAMES );
								Template::drawTemplate( $gm['system'], null, 'reminder', $loginUserRank, $_GET['type'], 'SEND_COMP_DESIGN', 'reminder.php?type='.$_GET['type']);
								$check			 = true;
								break;
							}
						}
					}
				}
				
				if( !$check )
				{ 
					$html .= Template::getTemplateString( $gm['system'], null, 'reminder', $loginUserRank, $_GET['type'], 'SEND_FALED_DESIGN', false ,null ,'head' ); 
					$html .= Template::getTemplateString( $gm['system'], null, 'reminder', $loginUserRank, $_GET['type'], 'SEND_FALED_DESIGN', false ,null ,'record' );
				}
			}
	
				
			if( !$check )
			{// 入力内容に不備がある場合
				$html .= Template::getTemplateString( $gm['system'], null, 'reminder', $loginUserRank, $_GET['type'], 'SEND_FALED_DESIGN', false ,null ,'foot' );
				$gm['system']->setVariable( 'error_msg' , $html);
				$gm['system']->addHiddenForm( 'post', 'check' );
				Template::drawTemplate( $gm['system'], null, 'reminder', $loginUserRank, $_GET['type'], 'SEND_FORM_DESIGN', 'reminder.php?type='.$_GET['type']);
			}
	
		}
		
		print System::getFoot($gm,$loginUserType,$loginUserRank);
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

	function drawResetForm()
	{
		global $gm;
		global $loginUserType;
		global $loginUserRank;

			$gm['system']->addHiddenForm( 'post', 'true' );
	        $gm['system']->setVariable( 'error_msg' , "");

		print System::getHead($gm,$loginUserType,$loginUserRank);
		Template::drawTemplate( $gm['system'], null, 'reminder', $loginUserRank, $_GET['type'], 'PASSWORD_RESET_FORM_DESIGN', 'reminder.php?token='.$_GET['token']);
		print System::getFoot($gm,$loginUserType,$loginUserRank);
	}

	function doReset()
	{
		global $gm;
		global $loginUserType;
		global $loginUserRank;
		global $PASSWORD_MODE;

		if( !file_exists( 'file/reminder/' . preg_replace( '/\W/' , '' , $_GET[ 'token' ] ) ) )
			{ exit; }

		$fp   = fopen( 'file/reminder/' . preg_replace( '/\W/' , '' , $_GET[ 'token' ] ) , 'rb' );
		$data = fgets( $fp );

		fclose( $fp );

		List( $type , $id ) = explode( ',' , $data );

		$db  = GMList::getDB( $type );
		$rec = $db->selectrecord( $id );

		$check      = true;

		if( !$rec ) //リセット対象が割り出せない場合
		{
			$html  = Template::getTemplateString( $gm[ 'system' ] , null , 'reminder' , $loginUserRank , $type , 'PASSWORD_RESET_FALED_DESIGN' , false , null , 'find' );
			$check = false;
		}
		else if( '' == $_POST[ 'password' ] ) //パスワードが入力されていない場合
		{
			$html  = Template::getTemplateString( $gm[ 'system' ] , null , 'reminder' , $loginUserRank , $type , 'PASSWORD_RESET_FALED_DESIGN' , false , null , 'password' );
			$check = false;
		}

		if( !$check ) //入力内容に不備がある場合
		{
			$html  = Template::getTemplateString( $gm[ 'system' ] , null , 'reminder' , $loginUserRank , $type , 'PASSWORD_RESET_FALED_DESIGN' , false , null , 'head' ) . $html;
			$html .= Template::getTemplateString( $gm[ 'system' ] , null , 'reminder' , $loginUserRank , $type , 'PASSWORD_RESET_FALED_DESIGN' , false , null , 'foot' );

			print System::getHead($gm,$loginUserType,$loginUserRank);
			print $html;
			print System::getFoot($gm,$loginUserType,$loginUserRank);
		}
		else //入力に問題がない場合
		{
			$db->setData( $rec , 'pass' , SystemUtil::encodePassword( $_POST[ 'password' ] , $PASSWORD_MODE ) );
			$db->updateRecord( $rec );

			unlink( 'file/reminder/' . preg_replace( '/\W/' , '' , $_GET[ 'token' ] ) );

			print System::getHead($gm,$loginUserType,$loginUserRank);
			Template::drawTemplate( $gm['system'], null, 'reminder', $loginUserRank, $_GET['type'], 'PASSWORD_RESET_COMP_DESIGN', 'reminder.php');
			print System::getFoot($gm,$loginUserType,$loginUserRank);
		}
	}
?>
