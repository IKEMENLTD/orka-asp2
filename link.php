<?php

	/*******************************************************************************************************
	 * <PRE>
	 *
	 * link.php - ��p�v���O����
	 * �L���N���b�N���v������v���O�����B
	 * 
	 * �ȉ��̌`���ŃA�N�Z�X���ꂽ�ꍇ�A�N���b�N��V��ǉ����čL���ɐݒ肳�ꂽURL�Ƀ��_�C���N�g���܂��B
	 * http://example.com/link.php?adwares=[foo]&id=[var]
	 * foo = �L��ID
	 * var = �A�t�B���G�C�^�[ID
	 *
	 * </PRE>
	 *******************************************************************************************************/

	/*******************************************************************************************************
	 * ���C������
	 *******************************************************************************************************/

	ob_start();

	try
	{
		include_once 'custom/head_main.php';
		include_once 'custom/extends/afadConf.php';
		include_once 'module/afad_postback.inc';

		CheckQuery();
		RequestGUID();
		SetTierParent();

		$adwares = GetAdwares();

		if( IsEnoughBudget( $adwares ) ) //�\�Z���]���Ă���ꍇ
		{
			if( IsPassageWait( $adwares ) ) //�Œ�҂����Ԃ��߂��Ă���ꍇ
			{
				if( IsThroughBlackList() ) //�u���b�N���X�g�Ɉ���������Ȃ������ꍇ
				{
					$access = AddAccess( $adwares );
					$pay    = AddClickReward( $adwares , $access );
				}
			}

			DoRedirect( $adwares , $access );
		}
		else //�\�Z���]���Ă��Ȃ��ꍇ
			DoRedirectToOver( $adwares );
	}
	catch( Exception $e_ )
	{
		ob_end_clean();

		WriteErrorLog( $e_ );

		DoRedirectToIndex();
	}

	ob_end_flush();

	/*******************************************************************************************************
	 * �֐�
	 *******************************************************************************************************/

	//���`�F�b�N //

	/**
		@brief   �N�G�������؂���B
		@details �N�G���ɕs���Ȓl���܂܂��ꍇ�A��O���X���[���܂��B
	*/
	function CheckQuery()
	{
		ConceptCheck::IsEssential( $_GET , Array( 'adwares' , 's_adwares' ) , 'or' );
		ConceptCheck::IsNotNull( $_GET , Array( 'adwares' , 's_adwares' ) , 'or' );
		ConceptCheck::IsScalar( $_GET , Array( 'adwares' , 'id' , 's_adwares' , 'url' ) );
		ConceptCheck::IsScalar( $_COOKIE , Array( 'adwares_cookie' ) );
	}

	/**
		@brief  �L���̗\�Z�����؂���B
		@param  $adwares_ RecordModel�I�u�W�F�N�g�B
		@return �\�Z���ݒ肳��Ă��Ȃ��A�܂��͗]���Ă���ꍇ��true�B\n
		        �\�Z���]���Ă��Ȃ��ꍇ��false�B
	*/
	function IsEnoughBudget( $adwares_ )
	{
		global $ADWARES_LIMIT_TYPE_YEN;          //���ʕ�V
		global $ADWARES_LIMIT_TYPE_CNT;          //�N���b�N��
		global $ADWARES_LIMIT_TYPE_CNT_CLICK;    //�N���b�N��V
		global $ADWARES_LIMIT_TYPE_CNT_CONTINUE; //�p����V

		//�\�Z�̐ݒ���擾����
		$budgetValue = $adwares_->getData( 'limits' );
		$budgetType  = $adwares_->getData( 'limit_type' );

		//�ݒ�ɉ����Ĕ�r������U�蕪����
		switch( $budgetType )
		{
			case $ADWARES_LIMIT_TYPE_YEN : //���ʕ�V�z
				return ( $adwares_->getData( 'money_count' ) < $budgetValue );

			case $ADWARES_LIMIT_TYPE_CNT : //�N���b�N��
				return ( $adwares_->getData( 'pay_count' ) < $budgetValue );

			case $ADWARES_LIMIT_TYPE_CNT_CLICK : //�N���b�N��V�z
				return ( $adwares_->getData( 'click_money_count' ) < $budgetValue );

			case $ADWARES_LIMIT_TYPE_CNT_CONTINUE : //�p����V�z
				return ( $adwares_->getData( 'continue_money_count' ) < $budgetValue );

			default : //�\�Z����Ȃ�
				return true;
		}
	}

	/**
		@brief  �Œ�҂����Ԃ����؂���B
		@param  $adwares_ RecordModel�I�u�W�F�N�g�B
		@return �N���b�N�������Ȃ����A�N���b�N��V�̍Œ�҂����Ԃ��o�߂��Ă���ꍇ��true�B\n
		        �N���b�N��V�̍Œ�҂����Ԃ��o�߂��Ă��Ȃ��ꍇ��false�B
	*/
	function IsPassageWait( $adwares_ )
	{
		//���݂̌o�ߎ��Ԃ��擾����
		$passageTime = GetPassageTime( $adwares_ );

		if( 0 > $passageTime ) //�A�N�Z�X��������Ȃ��ꍇ
			return true;

		//�N���b�N��V�̍Œ�҂����Ԃ��擾����
		$waitNum  = $adwares_->getData( 'span' );
		$waitUnit = $adwares_->getData( 'span_type' );
		$magnify  = Array( 's' => 1 , 'm' => 60 , 'h' => 60 * 60 , 'd' => 60 * 60 * 24 , 'w' => 60 * 60 * 24 * 7, );
		$waitTime = $waitNum * $magnify[ $waitUnit ];

		return ( $waitTime < $passageTime );
	}

	/**
		@brief  IP��UA���u���b�N���X�g�Ɣ�r����B
		@return �u���b�N���X�g�ƈ�v����ꍇ��false�A����ȊO��true�B
	*/
	function IsThroughBlackList()
	{
		$db      = GMlist::getDB( 'blacklist' );
		$table   = $db->getTable();
		$ipTable = $db->searchTable( $table , 'blacklist_mode' , '=' , 'ipaddress' );
		$ips     = $db->getDataList( $ipTable , 'ipaddress' );
		$uaTable = $db->searchTable( $table , 'blacklist_mode' , '=' , 'user_agent' );
		$uas     = $db->getDataList( $uaTable , 'user_agent' );

		foreach( $ips as $ip ) //�S�Ă�IP�u���b�N���X�g������
		{
			if( FALSE !== strpos( $_SERVER[ 'REMOTE_ADDR' ] , $ip ) ) //�w�蕔�����܂܂��ꍇ
				{ return false; }
		}

		foreach( $uas as $ua ) //�S�Ă�UA�u���b�N���X�g������
		{
			if( FALSE !== strpos( $_SERVER[ 'HTTP_USER_AGENT' ] , $ip ) ) //�w�蕔�����܂܂��ꍇ
				{ return false; }
		}

		return true;
	}

	/**
		@brief  ���̃��[�U�[�̃A�N�Z�X����������B
		@param  $access_ TableModel�I�u�W�F�N�g�B
		@return TableModel�I�u�W�F�N�g�B
	*/
	function SearchAccess( $access_ )
	{
		global $terminal_type; //�[�����

		if( 0 >= $terminal_type ) //PC����̃A�N�Z�X�̏ꍇ
			$access_->search( 'ipaddress' , '=' , getenv( 'REMOTE_ADDR' ) );
		else //�g�т���̃A�N�Z�X�̏ꍇ
		{
			$utn = MobileUtil::getMobileID();

			if( $utn ) //�̎��ʔԍ����擾�ł���ꍇ
				$access_->search( 'utn' , '=' , $utn );
			else //�̎��ʔԍ����擾�ł��Ȃ��ꍇ
				$access_->search( 'useragent' , '=' , getenv( 'HTTP_USER_AGENT' ) );
		}

		return $access_;
	}

	//���擾

	/**
		@brief  �L���f�[�^���擾����B
		@return RecordModel�I�u�W�F�N�g�B
	*/
	function GetAdwares()
	{
		if( $_GET[ 's_adwares' ] ) //�N���[�Y�h�L��ID���w�肳��Ă���ꍇ
			return new RecordModel( 'secretAdwares' , $_GET[ 's_adwares' ] );
		else if( $_GET[ 'adwares' ] ) //�ʏ�̍L��ID���w�肳��Ă���ꍇ
			return new RecordModel( 'adwares' , $_GET[ 'adwares' ] );
		else //�L��ID���w�肳��Ă��Ȃ��ꍇ
			throw new InvalidQueryException( '�L��ID���w�肳��Ă��܂���' );
	}

	/**
		@brief  cookie���烆�[�U�[���ʃn�b�V�����擾����B
		@return ���[�U�[���ʃn�b�V���B
	*/
	function GetCookieID()
	{
		if( $_COOKIE[ 'adwares_cookie' ] ) //����cookie���Z�b�g����Ă���ꍇ
			$cookieID = $_COOKIE[ 'adwares_cookie' ];
		else //cookie���Ȃ��ꍇ
			$cookieID = md5( time() . getenv( 'REMOTE_ADDR' ) );

		return $cookieID;
	}

	/**
		@brief  ���̃��[�U�[�̍ŏI�A�N�Z�X����̌o�ߎ��Ԃ��擾����B
		@param  $adwares_ RecordModel�I�u�W�F�N�g�B
		@return �A�N�Z�X�����������ꍇ�͌o�ߎ��ԁB\n
		        �A�N�Z�X��������Ȃ������ꍇ��-1�B
	*/
	function GetPassageTime( $adwares_ )
	{
		if( $adwares_->getData( 'use_cookie_interval' ) ) //�N���b�N�Ǘ���cookie���g�p����ꍇ
		{
			if( $_COOKIE[ 'interval_' . $adwares_->getID() ] ) //cookie���擾�ł���ꍇ
				$passageTime = time() - $_COOKIE[ 'interval_' . $adwares_->getID() ];
			else //cookie���擾�ł��Ȃ��ꍇ
				$passageTime = -1;
		}
		else //�N���b�N�Ǘ��ɃA�N�Z�X���O���g�p����ꍇ
		{
			//�A�N�Z�X����������
			$access = new TableModel( 'access' );
			$access = SearchAccess( $access );

			$access->search( 'adwares' , '=' , $adwares_->getID() );
			$access->sortDesc( 'regist' );
			$row = $access->getRow();

			if( $row ) //���R�[�h�����������ꍇ
			{
				$aRec   = $access->getRecordModel( 0 );
				$regist = $aRec->getData( 'regist' );

				$passageTime = time() - $regist;
			}
			else //���R�[�h��������Ȃ��ꍇ
				$passageTime = -1;
		}

		return $passageTime;
	}

	//������

	/**
		@brief  �A�N�Z�X���O��ǉ�����B
		@param  $adwares_ , RecordModel�I�u�W�F�N�g�B
		@return RecordModel�I�u�W�F�N�g�B
	*/
	function AddAccess( $adwares_ )
	{
		global $ACTIVE_NONE;

		$cookieID = GetCookieID();

		//�A�N�Z�X���R�[�h��o�^����
		$access = new FactoryModel( 'access' );
		$access->setID( md5( time() . getenv( 'REMOTE_ADDR' ) ) );
		$access->setData( 'ipaddress'    , getenv( 'REMOTE_ADDR' ) );
		$access->setData( 'cookie'       , $cookieID );
		$access->setData( 'adwares_type' , $adwares_->getType() );
		$access->setData( 'adwares'      , $adwares_->getID() );
		$access->setData( 'owner'        , SafeString( $_GET[ 'id' ] ) );
		$access->setData( 'useragent'    , SafeString( getenv( 'HTTP_USER_AGENT' ) ) );
		$access->setData( 'referer'      , SafeString( getenv( 'HTTP_REFERER' ) ) );
		$access->setData( 'state'        , $ACTIVE_NONE );
		$access->setData( 'utn'          , MobileUtil::getMobileID() );

		// AFAD連携: セッションID受け取り処理（設計書6.1節準拠）
		HandleAFADSession($adwares_, $access);

		$access = $access->register();

		UpdateCookie( $adwares_ , $cookieID );

		return $access;
	}

	/**
		@brief �N���b�N��V��ǉ�����B
		@param $adwares_ RecordModel�I�u�W�F�N�g�B
		@param $access_  RecordModel�I�u�W�F�N�g�B
	*/
	function AddClickReward( $adwares_ , $access_ )
	{
		global $terminal_type;
		global $ACTIVE_NONE;
		global $ACTIVE_ACTIVATE;

		$nUser = new RecordModel( 'nUser' , $access_->getData( 'owner' ) );

		if( 'secretAdwares' == $adwares_->getType() ) //�N���[�Y�h�L���̏ꍇ
		{
			$users = $adwares_->getData( 'open_user' );

			if( FALSE === strpos( $users , $nUser->getID() ) ) //���J���[�U�[�Ɋ܂܂�Ȃ��ꍇ
				return;
		}

		//�N���b�N��V�̐ݒ���擾����
		$clickReward    = $adwares_->getData( 'click_money' );
		$clickReception = $adwares_->getData( 'click_auto' );

		if( 0 >= $clickReward ) //�N���b�N��V�̐ݒ肪�Ȃ��ꍇ
			return;

		//�N���b�N��V���R�[�h��o�^����
		$pay = new FactoryModel( 'click_pay' );
		$pay->setID( md5( time() . getenv( 'REMOTE_ADDR' ) ) );
		$pay->setData( 'access_id' , $access_->getID() );
		$pay->setData( 'owner' , SafeString( $_GET[ 'id' ] ) );
		$pay->setData( 'adwares_type' , $adwares_->getType() );
		$pay->setData( 'adwares' , $adwares_->getID() );
		$pay->setData( 'cost' , $clickReward );
		$pay->setData( 'tier1_rate'   , SystemUtil::getSystemData( 'child_per' ) );
		$pay->setData( 'tier2_rate'   , SystemUtil::getSystemData( 'grandchild_per' ) );
		$pay->setData( 'tier3_rate'   , SystemUtil::getSystemData( 'greatgrandchild_per' ) );

		if( $clickReception ) //�����F�؂��L���̏ꍇ
		{
			$pay->setData( 'state' , $ACTIVE_ACTIVATE );
			$pay = $pay->register();

			//���[�U�[�̕�V�ɒǉ�����
			$pDB  = $pay->getDB();
			$tier = 0;
			AddPay( $_GET[ 'id' ] , $clickReward , $pDB , $pay->getRecord() , $tier );

			//�L���̗\�Z�Ɍv�シ��
			$currentReward = $adwares_->getData( 'money_count' );
			$currentClick  = $adwares_->getData( 'click_money_count' );

			$adwares_->setData( 'money_count' , $currentReward + $clickReward + $tier );
			$adwares_->setData( 'click_money_count' , $currentClick + 1 );
			$adwares_->update();

			//���[���ʒm
			sendPayMail( $pay->getRecord() , 'click_pay' );
		}
		else //�����F�؂������̏ꍇ
		{
			$pay->setData( 'state' , 0 );
			$pay = $pay->register();

			sendDisabledPayMail( $pay->getRecord() , 'click_pay' );
		}

		if( !IsEnoughBudget( $adwares_ ) ) //�\�Z���I�[�o�[�����ꍇ
		{
			$adwares_->setData( 'open' , false );
			$adwares_->update();
		}
	}

	/**
		@brief cookie���X�V����B
		@param $adwares_  RecordModel�I�u�W�F�N�g�B
		@param $cookieID_ ���[�U�[���ʃn�b�V���B
	*/
	function UpdateCookie( $adwares_ , $cookieID_ )
	{
		if( $_SERVER[ 'HTTPS' ] )
		{
			setcookie( 'interval_' . $adwares_->getID() , time()     , time() + 60 * 60 * 24 * 30 , '/; SameSite=none' , '' , true );
			setcookie( 'adwares_cookie'                 , $cookieID_ , time() + 60 * 60 * 24 * 7  , '/; SameSite=none' , '' , true );
		}
		else
		{
			setcookie( 'interval_' . $adwares_->getID() , time()     , time() + 60 * 60 * 24 * 30 );
			setcookie( 'adwares_cookie'                 , $cookieID_ , time() + 60 * 60 * 24 * 7  );
		}
	}

	/**
		@brief  ��������G�X�P�[�v����B
		@param  $str_ �C�ӂ̕�����B
		@return �G�X�P�[�v���ꂽ������B
	*/
	function SafeString( $str_ )
	{
		$str = substr( $str_ , 0 , 4096 );
		$str = h( $str );

		return $str;
	}

	/**
		@brief �e�����i�[����B
	*/
	function SetTierParent()
	{
		global $USE_AFFILIATE_BANNER_PARENT;
		global $PARENT_MAX_ROW;

		if( !$USE_AFFILIATE_BANNER_PARENT ) //�e���Z�b�g�@�\���L���łȂ��ꍇ
			{ return; }

		if( !isset( $_GET[ 'id' ] ) ) //���[�U�[ID���w�肳��Ă���ꍇ
			{ return; }

		if( '999' != $PARENT_MAX_ROW )
		{
			$nTable = new TableModel( 'nUser' );

			$nTable->search( 'parent' , '=' , $_GET[ 'id' ] );

			$nRow = $nTable->getRow();

			if( $PARENT_MAX_ROW <= $row )
				{ return; }
		}

		$_SESSION[ 'friend' ] = $_GET[ 'id' ];
	}

	/**
		@brief �G���[���O���o�͂���B
		@param $e_ ��O�I�u�W�F�N�g�B
	*/
	function WriteErrorLog( $e_ )
	{
		//�G���[���b�Z�[�W�����O�ɏo��
		$errorManager = new ErrorManager();
		$errorMessage = $errorManager->GetExceptionStr( $e_ );

		$errorManager->OutputErrorLog( $errorMessage );
	}

	//�����_�C���N�g

	/**
		@brief �L���ɐݒ肳�ꂽ���KURL�փ��_�C���N�g����B
		@param $adwares_ RecordModel�I�u�W�F�N�g�B
		@param $access_  RecordModel�I�u�W�F�N�g�B
	*/
	function DoRedirect( $adwares_ , $access_ = null )
	{
		global $mobile_flag;
		global $terminal_type;

		if( $adwares_->getData( 'url_users' ) ) //URL�����[�U�[�̔C�Ӑݒ�̏ꍇ
			{ $url = $_GET[ 'url' ]; }

		if( !$url ) //URl���ݒ肳��Ă��Ȃ��ꍇ
		{
			if( $mobile_flag ) //�g�ы@�\���L���ł���ꍇ
			{
				if( 0 >= $terminal_type ) //PC����̃A�N�Z�X�̏ꍇ
					$url = $adwares_->getData( 'url' );
				else //�g�т���̃A�N�Z�X�̏ꍇ
				{
					$url = $adwares_->getData( 'url_m' );

					if( !$url ) //URL���ݒ肳��Ă��Ȃ��ꍇ
						$url = $adwares_->getData( 'url' );
				}
			}
			else //�g�ы@�\�������ł���ꍇ
				{ $url = $adwares_->getData( 'url' ); }
		}

		if( !$url ) //URL���ݒ肳��Ă��Ȃ��ꍇ
			$url = 'index.php';

		if( $access_ ) //�A�N�Z�X�f�[�^�����݂���ꍇ
		{
			if( FALSE === strpos( $url , '?' ) ) //URL�Ƀp�����[�^������ꍇ
				$url .= '?aid=' . $access_->getID();
			else //URL�Ƀp�����[�^���Ȃ��ꍇ
				$url .= '&aid=' . $access_->getID();
		}

		print '<script>';

		if( $access_ ) //�A�N�Z�X�f�[�^�����݂���ꍇ
		{
			if( $access_->getID() )
				{ print 'localStorage.setItem( "afl_tracking_aid" , "' . $access_->getID() . '" );'; }
		}

		print 'location.href="' . $url . '"';
		print '</script>';

		exit();
	}

	/**
		@brief �V�X�e���̃g�b�v�y�[�W�փ��_�C���N�g����B
	*/
	function DoRedirectToIndex()
	{
		header( 'Location: index.php' );
		exit();
	}

	/**
		@brief �L���ɐݒ肳�ꂽ�\�Z�I�[�o�[����URL�փ��_�C���N�g����B
		@param $adwares_ RecordModel�I�u�W�F�N�g�B
	*/
	function DoRedirectToOver( $adwares_ )
	{
		global $terminal_type;

		$url = $adwares_->getData( 'url_over' );

		if( !$url ) //URL���ݒ肳��Ă��Ȃ��ꍇ
			$url = 'index.php';

		header( 'Location: ' . $url );
		exit();
	}

	/**
		@brief   uid�t��URL�փ��_�C���N�g����B
		@details DoCoMo�[������̎��ʔԍ����擾���邽�߁A�N�G����uid�����݂��Ȃ��ꍇ��uid��t�����ă��_�C���N�g���܂��B
	*/
	function RequestGUID()
	{
		global $terminal_type;

		if( MobileUtil::$TYPE_NUM_DOCOMO != $terminal_type ) //DoCoMo�[���łȂ��ꍇ
			return;

		if( 'on' == $_GET[ 'guid' ] ) //����guid�p�����[�^���Z�b�g����Ă���ꍇ
			return;

		//GET�p�����[�^�������p�����߂ɕ����񉻂���
		$paramStr = '';

		foreach( Array( 'id' , 'adwares' , 's_adwares' , 'url' ) as $key )
		{
			if( array_key_exists( $key , $_GET ) )
				$paramStr .= '&' . $key . '=' . $_GET[ $key ];
		}

		//���_�C���N�g
		header( 'Location: link.php?guid=on' . $paramStr );
		exit();
	}
?>
