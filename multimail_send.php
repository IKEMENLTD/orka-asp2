<?PHP
//get or post で渡されたデータにメールを送るだけのPHPプログラム
//Javascriptからの呼び出しで利用する

//送信を行ったメール数を表示する。

	ob_start();

	try
	{
		include_once './custom/head_main.php';

		//パラメータチェック
		ConceptCheck::IsEssential( $_POST , Array( 'main' , 'sub' , 'send_id' ) );
		ConceptCheck::IsNotNull( $_POST , Array( 'main' , 'sub' , 'send_id' ) );
		ConceptCheck::IsScalar( $_POST , Array( 'main' , 'sub' , 'send_id' ) );
		//パラメータチェックここまで

	    print ' { '; //json start
	    if( $multimail_send_user[$loginUserType] &&  count($_POST) > 0 ){
	
			$_POST[ 'main' ] = urldecode( $_POST[ 'main' ] );
			$_POST[ 'sub' ]  = urldecode( $_POST[ 'sub' ] );
	
	        ini_set("mbstring.internal_encoding","SJIS"); // 内部文字エンコーディングをSJISに設定します。  
	        
	        //UTF-8　→　SJIS
	        $main = mb_convert_encoding( ($_POST['main']) ,'SJIS' , 'UTF-8');
	        $sub =  mb_convert_encoding( ($_POST['sub']) ,'SJIS' , 'UTF-8');
	        
	        $send	 = explode(  '/', $_POST['send_id'] );
	        $db = $gm[ 'multimail' ]->getDB();
	        $rec = $db->getNewRecord();
	        $db->setData( $rec , 'sub' , $sub );
	        $cnt=0;
	        for( $i=0; $i<count($send); $i++ ){
	            if( strlen( $send[$i] ) <= 0)
	                break;
	            // メール着信通知の送信
	            for($j=0; $j<count($TABLE_NAME); $j++){
	                if(  $THIS_TABLE_IS_USERDATA[ $TABLE_NAME[$j] ]  ){
	                    $udb		 = $gm[ $TABLE_NAME[$j] ]->getDB();
	                    $utable		 = $udb->searchTable(  $udb->getTable(), 'id', '=', $send[$i] );
	                    if( $udb->getRow($utable) != 0 ){
	                        $urec	 = $udb->getRecord($utable, 0);
	                        Mail::sendString( addslashes( $sub ) , addslashes( $main ) , $MAILSEND_ADDRES, $db->getData($urec, 'mail'), $MAILSEND_NAMES );
	                        $cnt++;
	                        break;
	                    }
	                }
	            }
	        }
	        print ' "count" : '.$cnt." ,";
	        print ' "success" : true ';
	    }else{
	        if($multimail_send_user[$loginUserType]){
	            //送信権限がありません。
	            print ' "error" : "001" ,';
	        }else if(count($_POST) > 0){
	            print ' "error" : "002" ,';
	        }else{
	            print ' "error" : "000" ,';
	        }
	        print ' "success" : false ';
	    }
	    
	    print ' } '; //json end
	}
	catch( Exception $e_ )
	{
		ob_end_clean();

		//エラーメッセージをログに出力
		$errorManager = new ErrorManager();
		$errorMessage = $errorManager->GetExceptionStr( $e_ );

		$errorManager->OutputErrorLog( $errorMessage );

		//jsonフォーマットでエラーを出力
		print '{ "success" : false , "error" : "100" }';
	}

	ob_end_flush();
?>