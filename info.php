<?php

	/*******************************************************************************************************
	 * <PRE>
	 *
	 * info.php - �ėp�v���O����
	 * �o�^���e�ڍד��e�o�́B
	 *
	 * </PRE>
	 *******************************************************************************************************/

	ob_start();

	try
	{
		include_once 'custom/head_main.php';

	// Redirect to login page if not authenticated
	if ($loginUserType == $NOT_LOGIN_USER_TYPE) {
		SystemUtil::innerLocation('login.php');
		exit;
	}

		//�p�����[�^�`�F�b�N
		ConceptCheck::IsEssential( $_GET , Array( 'type' , 'id' ) );
		ConceptCheck::IsNotNull( $_GET , Array( 'type' , 'id' ) );
		ConceptCheck::IsScalar( $_GET , Array( 'type' , 'id' ) );

		if( !$gm[ $_GET[ 'type' ] ] )
			throw new IllegalAccessException( $key . '�͒�`����Ă��܂���' );

		if( $THIS_TABLE_IS_NOHTML[ $_GET[ 'type' ] ] )
			throw new IllegalAccessException( $key . '�͑���ł��܂���' );
		//�p�����[�^�`�F�b�N�����܂�

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
			{// �Y���f�[�^��������Ȃ������ꍇ�B
				$sys->drawInfoError( $gm, $loginUserType, $loginUserRank );
			}
			else
			{
	            
	            if($sys->infoCheck($gm, $rec, $loginUserType, $loginUserRank )){
	                $sys->infoProc( $gm, $rec, $loginUserType, $loginUserRank );
	                
					$gm[ $_GET['type'] ]->setForm( $rec );
					$gm[ $_GET['type'] ]->addHiddenForm( 'post', 'true' );
					
					$sys->doInfo( $gm, $rec, $loginUserType, $loginUserRank );
					
					// �A�N�Z�X�����ɉ����ē��e��`��B
					$sys->drawInfo( $gm, $rec, $loginUserType, $loginUserRank );
	            }else{
	                //�Y���f�[�^�̕\�������~��Ȃ������B
	    			$sys->drawInfoError( $gm, $loginUserType, $loginUserRank );
	            }
			}
		}
		print System::getFoot($gm,$loginUserType,$loginUserRank);
	}
	catch( Exception $e_ )
	{
		ob_end_clean();

		//�G���[���b�Z�[�W�����O�ɏo��
		$errorManager = new ErrorManager();
		$errorMessage = $errorManager->GetExceptionStr( $e_ );

		$errorManager->OutputErrorLog( $errorMessage );

		//��O�ɉ����ăG���[�y�[�W���o��
		$className = get_class( $e_ );
		ExceptionManager::DrawErrorPage($className );
	}

	ob_end_flush();
?>