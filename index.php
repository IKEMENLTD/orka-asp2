<?php

	/*******************************************************************************************************
	 * <PRE>
	 *
	 * index.php - ��p�v���O����
	 * �C���f�b�N�X�y�[�W���o�͂��܂��B
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

		//�Љ�R�[�h����
		friendProc();

		switch($loginUserType)
		{
		default:
			print System::getHead($gm,$loginUserType,$loginUserRank);
			
			if( $loginUserType != $NOT_LOGIN_USER_TYPE )
				Template::drawTemplate( $gm[ $loginUserType ] , $rec , $loginUserType , $loginUserRank , '' , 'TOP_PAGE_DESIGN' );
			else
				Template::drawTemplate( $gm[ 'system' ] , $rec , $loginUserType , $loginUserRank , '' , 'TOP_PAGE_DESIGN' );
			
			print System::getFoot($gm,$loginUserType,$loginUserRank);
			break;
		}
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