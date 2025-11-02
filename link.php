<?php

	/*******************************************************************************************************
	 * <PRE>
	 *
	 * link.php - 専用プログラム
	 * 広告クリックを計測するプログラム。
	 * 
	 * 以下の形式でアクセスされた場合、クリック報酬を追加して広告に設定されたURLにリダイレクトします。
	 * http://example.com/link.php?adwares=[foo]&id=[var]
	 * foo = 広告ID
	 * var = アフィリエイターID
	 *
	 * </PRE>
	 *******************************************************************************************************/

	/*******************************************************************************************************
	 * メイン処理
	 *******************************************************************************************************/

	ob_start();

	try
	{
		include_once 'custom/head_main.php';

		CheckQuery();
		RequestGUID();
		SetTierParent();

		$adwares = GetAdwares();

		if( IsEnoughBudget( $adwares ) ) //予算が余っている場合
		{
			if( IsPassageWait( $adwares ) ) //最低待ち時間を過ぎている場合
			{
				if( IsThroughBlackList() ) //ブラックリストに引っかからなかった場合
				{
					$access = AddAccess( $adwares );
					$pay    = AddClickReward( $adwares , $access );
				}
			}

			DoRedirect( $adwares , $access );
		}
		else //予算が余っていない場合
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
	 * 関数
	 *******************************************************************************************************/

	//■チェック //

	/**
		@brief   クエリを検証する。
		@details クエリに不正な値が含まれる場合、例外をスローします。
	*/
	function CheckQuery()
	{
		ConceptCheck::IsEssential( $_GET , Array( 'adwares' , 's_adwares' ) , 'or' );
		ConceptCheck::IsNotNull( $_GET , Array( 'adwares' , 's_adwares' ) , 'or' );
		ConceptCheck::IsScalar( $_GET , Array( 'adwares' , 'id' , 's_adwares' , 'url' ) );
		ConceptCheck::IsScalar( $_COOKIE , Array( 'adwares_cookie' ) );
	}

	/**
		@brief  広告の予算を検証する。
		@param  $adwares_ RecordModelオブジェクト。
		@return 予算が設定されていない、または余っている場合はtrue。\n
		        予算が余っていない場合はfalse。
	*/
	function IsEnoughBudget( $adwares_ )
	{
		global $ADWARES_LIMIT_TYPE_YEN;          //成果報酬
		global $ADWARES_LIMIT_TYPE_CNT;          //クリック数
		global $ADWARES_LIMIT_TYPE_CNT_CLICK;    //クリック報酬
		global $ADWARES_LIMIT_TYPE_CNT_CONTINUE; //継続報酬

		//予算の設定を取得する
		$budgetValue = $adwares_->getData( 'limits' );
		$budgetType  = $adwares_->getData( 'limit_type' );

		//設定に応じて比較処理を振り分ける
		switch( $budgetType )
		{
			case $ADWARES_LIMIT_TYPE_YEN : //成果報酬額
				return ( $adwares_->getData( 'money_count' ) < $budgetValue );

			case $ADWARES_LIMIT_TYPE_CNT : //クリック回数
				return ( $adwares_->getData( 'pay_count' ) < $budgetValue );

			case $ADWARES_LIMIT_TYPE_CNT_CLICK : //クリック報酬額
				return ( $adwares_->getData( 'click_money_count' ) < $budgetValue );

			case $ADWARES_LIMIT_TYPE_CNT_CONTINUE : //継続報酬額
				return ( $adwares_->getData( 'continue_money_count' ) < $budgetValue );

			default : //予算上限なし
				return true;
		}
	}

	/**
		@brief  最低待ち時間を検証する。
		@param  $adwares_ RecordModelオブジェクト。
		@return クリック履歴がないか、クリック報酬の最低待ち時間を経過している場合はtrue。\n
		        クリック報酬の最低待ち時間を経過していない場合はfalse。
	*/
	function IsPassageWait( $adwares_ )
	{
		//現在の経過時間を取得する
		$passageTime = GetPassageTime( $adwares_ );

		if( 0 > $passageTime ) //アクセスが見つからない場合
			return true;

		//クリック報酬の最低待ち時間を取得する
		$waitNum  = $adwares_->getData( 'span' );
		$waitUnit = $adwares_->getData( 'span_type' );
		$magnify  = Array( 's' => 1 , 'm' => 60 , 'h' => 60 * 60 , 'd' => 60 * 60 * 24 , 'w' => 60 * 60 * 24 * 7, );
		$waitTime = $waitNum * $magnify[ $waitUnit ];

		return ( $waitTime < $passageTime );
	}

	/**
		@brief  IPとUAをブラックリストと比較する。
		@return ブラックリストと一致する場合はfalse、それ以外はtrue。
	*/
	function IsThroughBlackList()
	{
		$db      = GMlist::getDB( 'blacklist' );
		$table   = $db->getTable();
		$ipTable = $db->searchTable( $table , 'blacklist_mode' , '=' , 'ipaddress' );
		$ips     = $db->getDataList( $ipTable , 'ipaddress' );
		$uaTable = $db->searchTable( $table , 'blacklist_mode' , '=' , 'user_agent' );
		$uas     = $db->getDataList( $uaTable , 'user_agent' );

		foreach( $ips as $ip ) //全てのIPブラックリストを処理
		{
			if( FALSE !== strpos( $_SERVER[ 'REMOTE_ADDR' ] , $ip ) ) //指定部分が含まれる場合
				{ return false; }
		}

		foreach( $uas as $ua ) //全てのUAブラックリストを処理
		{
			if( FALSE !== strpos( $_SERVER[ 'HTTP_USER_AGENT' ] , $ip ) ) //指定部分が含まれる場合
				{ return false; }
		}

		return true;
	}

	/**
		@brief  このユーザーのアクセスを検索する。
		@param  $access_ TableModelオブジェクト。
		@return TableModelオブジェクト。
	*/
	function SearchAccess( $access_ )
	{
		global $terminal_type; //端末種別

		if( 0 >= $terminal_type ) //PCからのアクセスの場合
			$access_->search( 'ipaddress' , '=' , getenv( 'REMOTE_ADDR' ) );
		else //携帯からのアクセスの場合
		{
			$utn = MobileUtil::getMobileID();

			if( $utn ) //個体識別番号が取得できる場合
				$access_->search( 'utn' , '=' , $utn );
			else //個体識別番号が取得できない場合
				$access_->search( 'useragent' , '=' , getenv( 'HTTP_USER_AGENT' ) );
		}

		return $access_;
	}

	//■取得

	/**
		@brief  広告データを取得する。
		@return RecordModelオブジェクト。
	*/
	function GetAdwares()
	{
		if( $_GET[ 's_adwares' ] ) //クローズド広告IDが指定されている場合
			return new RecordModel( 'secretAdwares' , $_GET[ 's_adwares' ] );
		else if( $_GET[ 'adwares' ] ) //通常の広告IDが指定されている場合
			return new RecordModel( 'adwares' , $_GET[ 'adwares' ] );
		else //広告IDが指定されていない場合
			throw new InvalidQueryException( '広告IDが指定されていません' );
	}

	/**
		@brief  cookieからユーザー識別ハッシュを取得する。
		@return ユーザー識別ハッシュ。
	*/
	function GetCookieID()
	{
		if( $_COOKIE[ 'adwares_cookie' ] ) //既にcookieがセットされている場合
			$cookieID = $_COOKIE[ 'adwares_cookie' ];
		else //cookieがない場合
			$cookieID = md5( time() . getenv( 'REMOTE_ADDR' ) );

		return $cookieID;
	}

	/**
		@brief  このユーザーの最終アクセスからの経過時間を取得する。
		@param  $adwares_ RecordModelオブジェクト。
		@return アクセスが見つかった場合は経過時間。\n
		        アクセスが見つからなかった場合は-1。
	*/
	function GetPassageTime( $adwares_ )
	{
		if( $adwares_->getData( 'use_cookie_interval' ) ) //クリック管理にcookieを使用する場合
		{
			if( $_COOKIE[ 'interval_' . $adwares_->getID() ] ) //cookieが取得できる場合
				$passageTime = time() - $_COOKIE[ 'interval_' . $adwares_->getID() ];
			else //cookieが取得できない場合
				$passageTime = -1;
		}
		else //クリック管理にアクセスログを使用する場合
		{
			//アクセスを検索する
			$access = new TableModel( 'access' );
			$access = SearchAccess( $access );

			$access->search( 'adwares' , '=' , $adwares_->getID() );
			$access->sortDesc( 'regist' );
			$row = $access->getRow();

			if( $row ) //レコードが見つかった場合
			{
				$aRec   = $access->getRecordModel( 0 );
				$regist = $aRec->getData( 'regist' );

				$passageTime = time() - $regist;
			}
			else //レコードが見つからない場合
				$passageTime = -1;
		}

		return $passageTime;
	}

	//■処理

	/**
		@brief  アクセスログを追加する。
		@param  $adwares_ , RecordModelオブジェクト。
		@return RecordModelオブジェクト。
	*/
	function AddAccess( $adwares_ )
	{
		global $ACTIVE_NONE;

		$cookieID = GetCookieID();

		//アクセスレコードを登録する
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

		$access = $access->register();

		UpdateCookie( $adwares_ , $cookieID );

		return $access;
	}

	/**
		@brief クリック報酬を追加する。
		@param $adwares_ RecordModelオブジェクト。
		@param $access_  RecordModelオブジェクト。
	*/
	function AddClickReward( $adwares_ , $access_ )
	{
		global $terminal_type;
		global $ACTIVE_NONE;
		global $ACTIVE_ACTIVATE;

		$nUser = new RecordModel( 'nUser' , $access_->getData( 'owner' ) );

		if( 'secretAdwares' == $adwares_->getType() ) //クローズド広告の場合
		{
			$users = $adwares_->getData( 'open_user' );

			if( FALSE === strpos( $users , $nUser->getID() ) ) //公開ユーザーに含まれない場合
				return;
		}

		//クリック報酬の設定を取得する
		$clickReward    = $adwares_->getData( 'click_money' );
		$clickReception = $adwares_->getData( 'click_auto' );

		if( 0 >= $clickReward ) //クリック報酬の設定がない場合
			return;

		//クリック報酬レコードを登録する
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

		if( $clickReception ) //自動認証が有効の場合
		{
			$pay->setData( 'state' , $ACTIVE_ACTIVATE );
			$pay = $pay->register();

			//ユーザーの報酬に追加する
			$pDB  = $pay->getDB();
			$tier = 0;
			AddPay( $_GET[ 'id' ] , $clickReward , $pDB , $pay->getRecord() , $tier );

			//広告の予算に計上する
			$currentReward = $adwares_->getData( 'money_count' );
			$currentClick  = $adwares_->getData( 'click_money_count' );

			$adwares_->setData( 'money_count' , $currentReward + $clickReward + $tier );
			$adwares_->setData( 'click_money_count' , $currentClick + 1 );
			$adwares_->update();

			//メール通知
			sendPayMail( $pay->getRecord() , 'click_pay' );
		}
		else //自動認証が無効の場合
		{
			$pay->setData( 'state' , 0 );
			$pay = $pay->register();

			sendDisabledPayMail( $pay->getRecord() , 'click_pay' );
		}

		if( !IsEnoughBudget( $adwares_ ) ) //予算をオーバーした場合
		{
			$adwares_->setData( 'open' , false );
			$adwares_->update();
		}
	}

	/**
		@brief cookieを更新する。
		@param $adwares_  RecordModelオブジェクト。
		@param $cookieID_ ユーザー識別ハッシュ。
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
		@brief  文字列をエスケープする。
		@param  $str_ 任意の文字列。
		@return エスケープされた文字列。
	*/
	function SafeString( $str_ )
	{
		$str = substr( $str_ , 0 , 4096 );
		$str = h( $str );

		return $str;
	}

	/**
		@brief 親情報を格納する。
	*/
	function SetTierParent()
	{
		global $USE_AFFILIATE_BANNER_PARENT;
		global $PARENT_MAX_ROW;

		if( !$USE_AFFILIATE_BANNER_PARENT ) //親情報セット機能が有効でない場合
			{ return; }

		if( !isset( $_GET[ 'id' ] ) ) //ユーザーIDが指定されている場合
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
		@brief エラーログを出力する。
		@param $e_ 例外オブジェクト。
	*/
	function WriteErrorLog( $e_ )
	{
		//エラーメッセージをログに出力
		$errorManager = new ErrorManager();
		$errorMessage = $errorManager->GetExceptionStr( $e_ );

		$errorManager->OutputErrorLog( $errorMessage );
	}

	//■リダイレクト

	/**
		@brief 広告に設定された正規URLへリダイレクトする。
		@param $adwares_ RecordModelオブジェクト。
		@param $access_  RecordModelオブジェクト。
	*/
	function DoRedirect( $adwares_ , $access_ = null )
	{
		global $mobile_flag;
		global $terminal_type;

		if( $adwares_->getData( 'url_users' ) ) //URLがユーザーの任意設定の場合
			{ $url = $_GET[ 'url' ]; }

		if( !$url ) //URlが設定されていない場合
		{
			if( $mobile_flag ) //携帯機能が有効である場合
			{
				if( 0 >= $terminal_type ) //PCからのアクセスの場合
					$url = $adwares_->getData( 'url' );
				else //携帯からのアクセスの場合
				{
					$url = $adwares_->getData( 'url_m' );

					if( !$url ) //URLが設定されていない場合
						$url = $adwares_->getData( 'url' );
				}
			}
			else //携帯機能が無効である場合
				{ $url = $adwares_->getData( 'url' ); }
		}

		if( !$url ) //URLが設定されていない場合
			$url = 'index.php';

		if( $access_ ) //アクセスデータが存在する場合
		{
			if( FALSE === strpos( $url , '?' ) ) //URLにパラメータがある場合
				$url .= '?aid=' . $access_->getID();
			else //URLにパラメータがない場合
				$url .= '&aid=' . $access_->getID();
		}

		print '<script>';

		if( $access_ ) //アクセスデータが存在する場合
		{
			if( $access_->getID() )
				{ print 'localStorage.setItem( "afl_tracking_aid" , "' . $access_->getID() . '" );'; }
		}

		print 'location.href="' . $url . '"';
		print '</script>';

		exit();
	}

	/**
		@brief システムのトップページへリダイレクトする。
	*/
	function DoRedirectToIndex()
	{
		header( 'Location: index.php' );
		exit();
	}

	/**
		@brief 広告に設定された予算オーバー時のURLへリダイレクトする。
		@param $adwares_ RecordModelオブジェクト。
	*/
	function DoRedirectToOver( $adwares_ )
	{
		global $terminal_type;

		$url = $adwares_->getData( 'url_over' );

		if( !$url ) //URLが設定されていない場合
			$url = 'index.php';

		header( 'Location: ' . $url );
		exit();
	}

	/**
		@brief   uid付きURLへリダイレクトする。
		@details DoCoMo端末から個体識別番号を取得するため、クエリにuidが存在しない場合はuidを付加してリダイレクトします。
	*/
	function RequestGUID()
	{
		global $terminal_type;

		if( MobileUtil::$TYPE_NUM_DOCOMO != $terminal_type ) //DoCoMo端末でない場合
			return;

		if( 'on' == $_GET[ 'guid' ] ) //既にguidパラメータがセットされている場合
			return;

		//GETパラメータを引き継ぐために文字列化する
		$paramStr = '';

		foreach( Array( 'id' , 'adwares' , 's_adwares' , 'url' ) as $key )
		{
			if( array_key_exists( $key , $_GET ) )
				$paramStr .= '&' . $key . '=' . $_GET[ $key ];
		}

		//リダイレクト
		header( 'Location: link.php?guid=on' . $paramStr );
		exit();
	}
?>
