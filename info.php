<?php

	/*******************************************************************************************************
	 * <PRE>
	 *
	 * info.php - 汎用プログラム
	 * 登録内容詳細内容出力。
	 *
	 * </PRE>
	 *******************************************************************************************************/

	ob_start();

	try
	{
		include_once 'custom/head_main.php';

		//パラメータチェック
		ConceptCheck::IsEssential( $_GET , Array( 'type' , 'id' ) );
		ConceptCheck::IsNotNull( $_GET , Array( 'type' , 'id' ) );
		ConceptCheck::IsScalar( $_GET , Array( 'type' , 'id' ) );

		if( !$gm[ $_GET[ 'type' ] ] )
			throw new IllegalAccessException( $key . 'は定義されていません' );

		if( $THIS_TABLE_IS_NOHTML[ $_GET[ 'type' ] ] )
			throw new IllegalAccessException( $key . 'は操作できません' );
		//パラメータチェックここまで

		print System::getHead($gm,$loginUserType,$loginUserRank);
		
		$sys	 = SystemUtil::getSystem( $_GET["type"] );
		
		if(   $THIS_TABLE_IS_NOHTML[ $_GET['type'] ] || !isset(  $gm[ $_GET['type'] ]  )   )
		{
			$sys->drawSearchError( $gm, $loginUserType, $loginUserRank );
		}
		else
		{
			$db		 = $gm[ $_GET['type'] ]->getDB();
	        
	        if( !isset($_GET['id']) && $_GET['type'] == $loginUserType ){
	            $_GET['id'] = $LOGIN_ID;
	        }
	        
			$rec	 = $db->selectRecord($_GET['id']);
			
			if( !isset($rec) )
			{// 該当データが見つからなかった場合。
				$sys->drawInfoError( $gm, $loginUserType, $loginUserRank );
			}
			else
			{
	            
	            if($sys->infoCheck($gm, $rec, $loginUserType, $loginUserRank )){
	                $sys->infoProc( $gm, $rec, $loginUserType, $loginUserRank );
	                
					$gm[ $_GET['type'] ]->setForm( $rec );
					$gm[ $_GET['type'] ]->addHiddenForm( 'post', 'true' );
					
					$sys->doInfo( $gm, $rec, $loginUserType, $loginUserRank );
					
					// アクセス権限に応じて内容を描画。
					$sys->drawInfo( $gm, $rec, $loginUserType, $loginUserRank );
	            }else{
	                //該当データの表示許可が降りなかった。
	    			$sys->drawInfoError( $gm, $loginUserType, $loginUserRank );
	            }
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
?>