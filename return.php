<?PHP

	ob_start();

	try
	{
		include_once 'custom/head_main.php';

		//パラメータチェック
		ConceptCheck::IsScalar( $_GET , Array( 'action' , 'pass' , 'm' , 'd' ) );
		//パラメータチェックここまで
	
	//ページ遷移
	switch($_GET['action']){
	    case 'report':
			SystemUtil::download( 'batch_returnss.csv' , 'report/batch_returnss.csv' );
			break;

	    case 'exe':
			
			if($loginUserType != "admin" && $_GET["pass"] != $cron_pass){ throw new IllegalAccessException( "ACCESS ERROR." ); }
		
	        //nUserのpayが換金可能額以上なのをsearch
	        $db = $gm['nUser']->getDB();
	        
	        $rdb = $gm['returnss']->getDB();
	        
	        
	        $table = $db->searchTable( $db->getTable() , 'pay' , '>=' , $ADWARES_EXCHANGE );
	        $table = $db->searchTable( $table , 'activate' , '=' , $ACTIVE_ACCEPT );
	        $time = time();
	        $csv_data = "";
	        //結果をループで回して全額換金して、returnssに投げていく
	        $row = $db->getRow( $table );
	        if($row==0){
	            print System::getHead($gm,$loginUserType,$loginUserRank);
	            Template::drawTemplate( $gm[ $loginUserType ], null, 'returnss', $loginUserRank, '', 'RETURNSS_EXECUTE_ERROR' );
	            print System::getFoot($gm,$loginUserType,$loginUserRank);
	            exit();
	        }
	        for($i=0 ; $row > $i ; $i++ ){
	            $rec = $db->getRecord( $table , $i );
	            
	            $id  = $db->getData( $rec , 'id' );
	            $pay = $db->getData( $rec , 'pay' );
	            
	            $rrec = $rdb->getNewRecord();
	            $rid = md5( time(). $id );
	            $rdb->setData(  $rrec, 'id',		$rid );
	            $rdb->setData(  $rrec, 'owner',		$id  );
	            $rdb->setData(  $rrec, 'state',		'管理者確認待ち'  );
	            $rdb->setData(  $rrec, 'cost',		$pay  );
	            $rdb->setData(  $rrec, 'regist',    $time );
	            $rdb->addRecord( $rrec );
	            
	            $csv_data .= $rid.','.$id.','.$pay.','.$time."\n";
	        }
	        
	        //換金申請投げたnUserのpayをゼロに(上のループでやるとgetRecordが変わってしまう)
	/*        for($i=0 ; $row > $i ; $i++ ){
	            $rec = $db->getRecord( $table , $i );
	            $rec  = $db->setData( $rec , 'cost' , 0 );
	            $db->updateRecord( $rec );
	        }*/
	        $db->setTableDataUpdate( $table , 'pay' , 0 );
	        
	        //全部csvに入れとく
	        $filename = "./report/batch_returnss.csv";
	        $handle = fopen($filename, 'w');
	        if(fwrite($handle, $csv_data) === FALSE){
				throw new FileIOException( "Cannot write to file ($filename)" );
	        }
	        fclose($handle);
	    
	        //登録完了
	        $gm[ $loginUserType ]->setVariable('cnt',$row);
	        
	        print System::getHead($gm,$loginUserType,$loginUserRank);
	        Template::drawTemplate( $gm[ $loginUserType ], null, 'returnss', $loginUserRank, '', 'RETURNSS_EXECUTE_SUCCESS' );
	        print System::getFoot($gm,$loginUserType,$loginUserRank);
	        exit();
	        
	        break;
	    case 'zenginkyo':
			if($loginUserType != "admin" && $_GET["pass"] != $cron_pass){ throw new IllegalAccessException( "ACCESS ERROR." ); }
	    	$db = $gm['zenginkyo']->getDB();
			$rec = $db->selectRecord('ADMIN');
			
			$branch_code = $db->getData( $rec, 'branch_code');
			$bank_type = $db->getData( $rec, 'bank_type');
			$number = $db->getData( $rec, 'number');
			
			//ヘッダレコード
			$head = '';
			$head .= '1';//データ区分 1
			$head .= '21';//種別コード(総合) 2
			$head .= '0';//コード区分(JIS) 1
			$head .= sprintf('%10s',substr($branch_code,0,2).($bank_type==1?1:2).$number);//委託者コード 10
			$head .= sprintf('%40s',mb_convert_kana($db->getData( $rec, 'name_kana'), "k"));//委託者名カナ 40
			$head .= sprintf('%02d%02d',$_GET['m'],$_GET['d']);//取組日 4
			$head .= sprintf('%4s',$db->getData( $rec, 'bank_code'));//仕向金融機関番号 4
			$head .= sprintf('%15s',mb_convert_kana($db->getData( $rec, 'bank_name_kana'), "k"));//仕向金融機関名 15
			$head .= sprintf('%3s',$branch_code);//仕向支店番号 3
			$head .= sprintf('%15s',mb_convert_kana($db->getData( $rec, 'branch_name_kana'), "k"));//仕向支店名15
			$head .= $bank_type;//預金種目 1
			$head .= sprintf('%7s',$number);//口座番号 7
			$head .= str_repeat(' ',17);//ダミー 17
			
			//データレコード
			$data = '';
	        $rdb = $gm['returnss']->getDB();
	        $ndb = $gm['nUser']->getDB();
	        $rtable = $rdb->searchTable( $rdb->getTable(), 'state', '=', '入金待ち' );
			
	        $row = $rdb->getRow( $rtable );
	        $cnt = 0;
	        for($i=0;$i<$row;$i++){
	        	$rrec = $rdb->getRecord( $rtable, $i );
	        	$id = $rdb->getData( $rrec, 'owner' );
	        	$nrec = $ndb->selectRecord( $id );
	        	
	        	$cost = $rdb->getData( $rrec, 'cost' );
				$data .= '2';//データ区分 1
				$data .= sprintf('%4s',$ndb->getData( $nrec,'bank_code' ));//被仕向金融機関番号 4
	//			$data .= sprintf('%15s',mb_convert_kana($ndb->getData( $nrec, 'bank'), "k"));//被仕向金融機関名 15
				$data .= str_repeat(' ',15);//被仕向金融機関名 15
				$data .= sprintf('%3s',$ndb->getData( $nrec,'branch_code' ));//被仕向支店番号3
	//			$data .= sprintf('%15s',mb_convert_kana($ndb->getData( $nrec, 'branch'), "k"));//被仕向支店名15
				$data .= str_repeat(' ',15);//被仕向支店名15
				$data .= str_repeat(' ',4);//手形交換所番号4
				$data .= $ndb->getData( $nrec,'bank_type' );//預金種目1
				$data .= sprintf('%7s',$ndb->getData( $nrec,'number' ));//口座番号7
				$data .= sprintf('%30s',mb_convert_kana($ndb->getData( $nrec, 'bank_name'), "k"));//受取人名30
				$data .= sprintf('%10d',$cost);//振込金額10
				$data .= ' ';//新規コード数字1
				$data .= sprintf('%10s',$id);//顧客コード1 10
				$data .= str_repeat(' ',10);//顧客コード2 10
				$data .= ' ';//振込区分 1
				$data .= ' ';//識別表示 1
				$data .= str_repeat(' ',7);//ダミー 7
				$data .= "\n";
				$cnt += $cost;
	        }
	        
	        //トレーラーレコード
	        $trailer ="";
			$trailer .= '2';//データ区分 1
			$trailer .= sprintf('%6d',$row);//合計件数 6
			$trailer .= sprintf('%12d',$cnt);//合計金額 12
			$trailer .= str_repeat(' ',101);//ダミー 101
	        
	        //フッターレコード
	        $foot = "";
			$foot .= '9';//データ区分 1
			$foot .= str_repeat(' ',119);//ダミー 119
	        
			$file_name = 'zenginkyo.txt';
			header('Cache-Control: public');
			header('Pragma:');
	        header('Content-Disposition: attachment; filename="'.$file_name.'"');
			header('Content-type: application/x-octet-stream; name="'.$file_name.'"; charset=Shift_JIS');
			
			print $head."\n";
			print $data;
			print $trailer."\n";
			print $foot;
			
			break;
	    default:
			print System::getHead($gm,$loginUserType,$loginUserRank);
	        Template::drawTemplate( $gm[ $loginUserType ], null, 'returnss', $loginUserRank, '', 'RETURNSS_ACTION_INDEX' );
			print System::getFoot($gm,$loginUserType,$loginUserRank);
	        break;
		}
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