<?php

	/*******************************************************************************************************
	 * <PRE>
	 *
	 * other.php - ��p�v���O����
	 * key�ɂĎw�肵���t�@�C�����o�͂��܂��B
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
		if( isset( $_GET[ 'page' ] ) )
		{
			ConceptCheck::IsNotNull( $_GET , Array( 'page' ) );
			ConceptCheck::IsScalar( $_GET , Array( 'page' ) );
		}
		else
		{
			ConceptCheck::IsEssential( $_GET , Array( 'key' ) );
			ConceptCheck::IsNotNull( $_GET , Array( 'key' ) );
			ConceptCheck::IsScalar( $_GET , Array( 'key' ) );
		}
		//�p�����[�^�`�F�b�N�����܂�

		print System::getHead($gm,$loginUserType,$loginUserRank);

		$type = '';
		if( isset( $_GET['type'] ) && strlen( $_GET['type'] ) ) { $type = "_".$_GET['type']; }
		
		if(  isset( $_GET['page'] ) && strlen( $_GET['page'] ) )
		{
			if( preg_match( '/\W/' , $_GET[ 'page' ] ) )
				$HTML = Template::getLabelFile( 'ERROR_PAGE_DESIGN' );
			else
			{
				$HTML = $template_path . 'other/' . $_GET[ 'page' ] . '.html';
	
				if( !is_file( $HTML ) )
					$HTML = Template::getLabelFile( 'ERROR_PAGE_DESIGN' );
			}
		}
		else if(  isset( $_GET['key'] ) && strlen( $_GET['key'] ) )
		{
	        $HTML = Template::getTemplate( $loginUserType , $loginUserRank , $_GET['key'] , 'OTHER_PAGE_DESIGN'.$type );
	        if( ! strlen($HTML) ) { $HTML = Template::getLabelFile( 'ERROR_PAGE_DESIGN' ); }
	    }
		else { $HTML =  Template::getLabelFile( 'ERROR_PAGE_DESIGN' ); }
	
	    if(  $loginUserType == $NOT_LOGIN_USER_TYPE  )
	         print $gm['system']->getString($HTML , null ,null );
	    else
	         print $gm[$loginUserType]->getString( $HTML , null ,null );
	
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