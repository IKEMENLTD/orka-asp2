<?php

/*
・report作成用モジュール
 $_GET['type']が存在する場合は、そのDBのtdb内容をそのまま取得して返す。
 $_GET['case']が存在する場合は、/module/Report.incで定義された形式でreportを出力する。

*/
    
	ob_start();

	try
	{
		include_once 'custom/head_main.php';

		//パラメータチェック
		ConceptCheck::IsScalar( $_GET , Array( 'type' , 'run' , 'case' ) );
		ConceptCheck::IsScalar( $_POST , Array( 'run' , 'case' , 'y' , 'm' ) );
		//パラメータチェックここまで

	    $rp = new mod_report();
	    
	    if( ! $rp->user_type_check($loginUserType) ){
			print System::getHead($gm,$loginUserType,$loginUserRank);
	        Template::drawTemplate( $gm[ $loginUserType ] , $rec , $loginUserType , $loginUserRank , '' , 'TOP_PAGE_DESIGN' );
			print System::getFoot($gm,$loginUserType,$loginUserRank);
			ob_end_flush();
	    }else if( (isset($_POST["run"]) && isset($_POST['case']) ) || (isset($_GET["run"]) && isset($_GET['case']) ) ){
	        if(isset($_POST["run"]))
	            $name = $_POST['case'];
	        else
	            $name = $_GET['case'];
	           
	        if( !isset( $rp->report_list[$loginUserType]['report'][$name] ) ){
				print System::getHead($gm,$loginUserType,$loginUserRank);
	    	    Template::drawTemplate( $gm[ $loginUserType ], null, 'report', $loginUserRank, '', 'REPORT_CASE_NOT_FOUND' );
				print System::getFoot($gm,$loginUserType,$loginUserRank);
				ob_end_flush();
	            exit();
	        }
	        
	        $array  = $rp->report_list[$loginUserType]['report'][$name];
	        $db = $gm[ $array['table_name'] ]->getDB();
	        $table = $db->getTable();
	        
	        if( isset($array['file_name'])){
		        $file_name = $array['file_name'];
	        }else{
		        $file_name = $name.'.csv';
	        }
	
	        if( isset($array['owner']) && $array['owner'] ){
				if( is_array( $array['owner'] ) )
				{
					foreach( $array['owner'] as $key => $value )
						if( $key == $loginUserType )
			            	$table = $db->searchTable( $table, $value, '=', $LOGIN_ID );
				}
				else
		        	$table	 = $db->searchTable( $table , $array['owner'], '=', $LOGIN_ID );
	        }
	        
	        if( isset($array['search']) && is_array($array['search']) && count($array['search']) ){
	        	foreach( $array['search'] as $col => $param ){
	        		if( isset($_POST[ $col ]) && strlen($_POST[ $col ]) ){
	        			if( is_null($sr) ){	$sr = new Search(  $gm[ $array['table_name'] ], $array['table_name'] ); }
						$table = $sr->searchTable( $db , $table , $col , $param , $_POST[ $col ] );
	        		}
	        	}
	        }
	        
	        if( isset($_POST['m']) && isset($_POST['y']) && strlen($_POST['m']) && strlen($_POST['y']) && is_numeric($_POST['y']) && is_numeric($_POST['m']) ){//期間データ使って、searchした結果をCVSに出力
	            $table	 = $db->searchTable( $table , 'regist', 'b', mktime( 0, 0, 0, $_POST['m'], 1, $_POST['y'] ), mktime( 0, 0, -1, $_POST['m'] + 1, 1, $_POST['y'] )  );
	            
	            $file_name = $_POST['y'].'_'.$_POST['m'].$file_name;
	        }
	        
	        $haed = Array();
	        $colums = Array();
	        foreach( $array['head_name'] as $key => $head_name ){
	            $head[] = $head_name;
	            
	            $discrimination = explode( ':' , $array['colum_name'][$key] );
	
	            $colums[$key] = $rp->checkFunction($discrimination);;
	        }
	        
	        ////////////////////
	        
	        $contents = Array( $head );
	
	        $row	 = $db->getRow( $table );
	        $sum	 = 0;
	        
	        for($i=0; $i<$row; $i++){
	            $rec	 = $db->getRecord( $table, $i );
	            
	            $line = Array();
	            foreach( $colums as $key => $func ){
	                $line = array_merge( $line , (array)$rp->{$func['f']}( $db , $rec , $colums[$key]['v'] ) );
	            }
	            
	            $contents[] = $line;
	            if( count( $rp->next ) ){
		            $contents = array_merge( $contents, $rp->next );
	            	$rp->next = Array();
	            }
	        }
	        
	        if( isset($array['pre']) && is_array($array['pre']) && count($array['pre']) ){
	        	foreach( $array['pre'] as $func ){
	        		$rp->{$func}();
	        	}
	        }
			
			header('Cache-Control: public');
			header('Pragma:');
	        header('Content-Disposition: attachment; filename="'.$file_name.'"');
			header('Content-type: application/x-octet-stream; name="'.$file_name.'"; charset=Shift_JIS');
	
	        $handle = fopen('php://output', 'w');
	        
	        if( strlen( $rp->prefix ) ){ fputs( $handle, $rp->prefix ."\n"); }
	
	        foreach( $contents as $line ){
		        if( fputcsv($handle, $line) === FALSE){
					throw new FileIOException( "Cannot write to file ($filename)" );
		        }
	        }
	    
	        if( strlen( $rp->suffix ) ){ fputs( $handle, "\n" . $rp->suffix ); }
	        
		    fputs($handle, "\n");
			fclose($handle);
			
		}else if(isset($_GET["type"]) && isset($_GET["run"])){
			
			if( isset($rp->report_list[$loginUserType]['table'][$_GET['type']]) ){
	
				$table_data = $rp->report_list[$loginUserType]['table'][$_GET['type']];
				
	            $db = $gm[ $_GET["type"] ]->getDB();
	            $clmList = $db->getClumnNameList();
	            $table = $db->getTable();
	            
	            if($table_data['owner']){
					if( is_array( $table_data['owner'] ) )
					{
						foreach( $table_data['owner'] as $key => $value )
							if( $key == $loginUserType )
				            	$table = $db->searchTable( $table, $value, '=', $LOGIN_ID );
					}
					else
		            	$table = $db->searchTable( $table, $table_data['owner'], '=', $LOGIN_ID );
	            }
	            
	            if( isset($_POST['m']) && isset($_POST['y']) && strlen($_POST['m']) && strlen($_POST['y']) && is_numeric($_POST['y']) && is_numeric($_POST['m']) )//期間データ使って、searchした結果をCVSに出力
	                $table	 = $db->searchTable( $table , 'regist', 'b', mktime( 0, 0, 0, $_POST['m'], 1, $_POST['y'] ), mktime( 0, 0, -1, $_POST['m'] + 1, 1, $_POST['y'] )  );
	            
	            $contents = Array();
	            
	            $row = $db->getRow( $table );
	            for( $i = 0 ; $i < $row ; $i++ ){
	                $rec = $db->getRecord( $table , $i );
	                
	                $line = Array();
	                foreach( $clmList as $clm ){
	                    $line = array_merge( $line , (array)$db->getData( $rec , $clm ) );
					}
	                $contents[] = $line;
				}
				
				$file_name = $table_data['label'];
	        
				header('Cache-Control: public');
				header('Pragma:');
		        header('Content-Disposition: attachment; filename="'.$file_name.'.csv"');
				header('Content-type: application/x-octet-stream; name="'.$file_name.'.csv"; charset=Shift_JIS');
	
				//ファイル出力
		        $handle = fopen('php://output', 'w');
		        
		        if( isset($table_data['column']) ){
		        	fputs($handle,$table_data['column']."\n");
		        }
		        
			    foreach( $contents as $line ){
		        	if( fputcsv($handle, $line) === FALSE){
						throw new FileIOException( "Cannot write to file ($filename)" );
			        }
		        }
		        fputs($handle, "\n");
				fclose($handle);
	
			}else{
	            //error
				header("Location: ".$HOME."index.php");
	        }
	    }else{
			print System::getHead($gm,$loginUserType,$loginUserRank);
	        Template::drawTemplate( $gm[ $loginUserType ], null, 'report', $loginUserRank, '', 'REPORT_DESIGN' );
	
			print System::getFoot($gm,$loginUserType,$loginUserRank);
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