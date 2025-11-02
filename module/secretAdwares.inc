<?php

	/**********          adwaresの定義          **********/

	$EDIT_TYPE                            = 'secretAdwares';
	$TABLE_NAME[]                         = $EDIT_TYPE;
	$THIS_TABLE_IS_USERDATA[ $EDIT_TYPE ] = false;
	$THIS_TABLE_IS_NOHTML[ $EDIT_TYPE ]   = false;
	$LOGIN_KEY_COLUM[ $EDIT_TYPE ]        = null;
	$LOGIN_PASSWD_COLUM[ $EDIT_TYPE ]     = null;
	$LOGIN_PASSWD_COLUM2[ $EDIT_TYPE ]    = null;
	$LST[ $EDIT_TYPE ]                    = 'module/secret_adwares.csv';
	$TDB[ $EDIT_TYPE ]                    = 'module/secret_adwares.csv';
	$ID_HEADER[ $EDIT_TYPE ]              = 'SA';
	$ID_LENGTH[ $EDIT_TYPE ]              = 8;

	class mod_secretAdwares extends command_base
	{
		/**
			@brief 広告の対象者にユーザーが含まれるか確認する。
			@details ユーザーが含まれる場合は、TRUEを出力します。
			@param
				@li 0 広告ID。
				@li 1 ユーザーID。
		*/
		function hasUser( &$gm_ , $rec_ , $cc_ )
		{
			List( $saID , $uID ) = $cc_;

			if( !$saID )
				return false;

			$saDB    = SystemUtil::getGMforType( 'secretAdwares' )->getDB();
			$saTable = $saDB->getTable();
			$saRec   = $saDB->selectRecord( $saID );

			if( !$saRec )
				return false;

			$nUsers = $saDB->getData( $saRec , 'open_user' );

			if( FALSE !== strpos( $nUsers , $uID ) )
				$this->addBuffer( TRUE );
		}
	}
?>
