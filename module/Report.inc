<?PHP

/*
 * report_list[ ユーザーRANK ]['table']
 *   table名をkeyにして、要素は以下
 * tables[label] = ラベル
 * tables[column] = 1行目に表示するカラムタイトル
 * tables[owner] = false / LOGIN_IDと一致すべきcolumn名
 *
 * report_list[ ユーザーRANK ]['report'] = reports;
 *
 * reportsは以下、出力ファイル名をkeyとするreportの配列
 *
 * report[case_label] = ラベル
 * report[table_name] = テーブル名
 * report[head_name][] = カラム名
 * report[colum_name][] = カラムの内容(カラム名か関数)
 * 以下、上の2つをループ
 * 
 * ---以下は任意---
 * report[owner] = false / LOGIN_IDと一致すべきcolumn名
 * report[search] = Array(  カラム名 => Array( 検索パラメータ ) / 指定カラム名のPOSTパラメータが渡されていると絞り込む
 * report[pre]
 *

 //sample
 $report['case_label'] = '企業ユーザー 一覧';
 $report['table_name'] = 'cUser';

 $report['head_name'][] = 'No';
 $report['colum_name'][] = 'f:no';

 $report['head_name'][] = 'ID';
 $report['colum_name'][] = 'id';

 $report['head_name'][] = '名前';
 $report['colum_name'][] = 'name';

 $head_name[$nameme][] = '登録日時';
 $report['colum_name'][] = 'f:date:regist';

 $reports["cUser_list"] = $report;

 */

class mod_report extends command_base
{
	var $TIME_FORMAT = 'Y m/d H:i:s';
	var $report_list = Array();
	var $function_list = Array();

	//次の行も纏めて出力する時はここに行文字列を入れ込む
	var $next = Array();
	
	//全体より前に出力したい内容をここに
	var $prefix = "";
	
	//全体より後に出力したい内容をここに設定する
	var $suffix = "";

	//コンストラクタ
	function mod_report(){
		$reports = Array();
		
		//▼管理者

		//店舗単位
		$report['case_label'] = '広告主単位 成果レポート';
		$report['file_name'] = '広告主単位成果レポート.csv';
		$report['table_name'] = 'cUser';
		$report['dm'] = false;
		$report['owner'] = false;
		$report['pre'] = Array('draw_time','draw_search');
		
		$report['head_name'][] = 'No';
		$report['colum_name'][] = 'f:no';

		$report['head_name'][] = 'ID';
		$report['colum_name'][] = 'id';

		$report['head_name'][] = '名前';
		$report['colum_name'][] = 'name';

		$report['head_name'][] = '郵便番号';
		$report['colum_name'][] = 'in:(:zip1:):(:s:-:):(:zip2:)';

		$report['head_name'][] = '都道府県';
		$report['colum_name'][] = 'r:prefectures:adds:id:name';
		$report['head_name'][] = '住所';
		$report['colum_name'][] = 'add_sub';
		
		$report['head_name'][] = '電話番号';
		$report['colum_name'][] = 'tel';
		
		$report['head_name'][] = 'メールアドレス';
		$report['colum_name'][] = 'mail';
		
		$report['head_name'][] = '広告アクセス数';
		$report['colum_name'][] = 'f:count_time:access:id:cuser';
		
		$report['head_name'][] = 'クリック広告成果数';
		$report['colum_name'][] = 'f:count_time:click_pay:id:cuser';
		$report['head_name'][] = 'クリック広告成果額';
		$report['colum_name'][] = 'f:sum_time:click_pay:id:cuser:cost';
		
		$report['head_name'][] = 'アフィリエイト広告成果数';
		$report['colum_name'][] = 'f:count_time:pay:id:cuser';
		$report['head_name'][] = 'アフィリエイト広告成果額';
		$report['colum_name'][] = 'f:sum_time:pay:id:cuser:cost';
		
		$report['head_name'][] = '継続広告成果数';
		$report['colum_name'][] = 'f:count_time:continue_pay:id:cuser';
		$report['head_name'][] = '継続広告成果額';
		$report['colum_name'][] = 'f:sum_time:continue_pay:id:cuser:cost';
		
		$report['head_name'][] = '広告成果総額';
		$report['colum_name'][] = 'in:(:calc:(:f:sum_time:click_pay:id:cuser:cost:):(:s:+:):(:f:sum_time:pay:id:cuser:cost:):(:s:+:):(:f:sum_time:continue_pay:id:cuser:cost:):)';
		
		$report['head_name'][] = 'ティア総額';
		$report['colum_name'][] = 'f:sum_time:tier:id:cuser:cost';
		
		$reports["cUser"] = $report;
		
		//広告単位
		$report = Array();
		$report['case_label'] = '広告単位　成果レポート';
		$report['file_name'] = '広告単位成果レポート.csv';
		$report['table_name'] = 'adwares';
		$report['dm'] = false;
		$report['owner'] = false;
		$report['pre'] = Array('draw_time','draw_search');
		
		$report['head_name'][] = 'No';
		$report['colum_name'][] = 'f:no';

		$report['head_name'][] = '広告ID';
		$report['colum_name'][] = 'id';

		$report['head_name'][] = '広告名';
		$report['colum_name'][] = 'name';

		$report['head_name'][] = '広告アクセス数';
		$report['colum_name'][] = 'f:count_time:access:id:adwares';
		
		$report['head_name'][] = 'クリック広告成果数';
		$report['colum_name'][] = 'f:count_time:click_pay:id:adwares';
		$report['head_name'][] = 'クリック広告成果額';
		$report['colum_name'][] = 'f:sum_time:click_pay:id:adwares:cost';
		
		$report['head_name'][] = 'アフィリエイト広告成果数';
		$report['colum_name'][] = 'f:count_time:pay:id:adwares';
		$report['head_name'][] = 'アフィリエイト広告成果額';
		$report['colum_name'][] = 'f:sum_time:pay:id:adwares:cost';
		
		$report['head_name'][] = '継続広告成果数';
		$report['colum_name'][] = 'f:count_time:continue_pay:id:adwares';
		$report['head_name'][] = '継続広告成果額';
		$report['colum_name'][] = 'f:sum_time:continue_pay:id:adwares:cost';
		
		$report['head_name'][] = '広告成果総額';
		$report['colum_name'][] = 'in:(:calc:(:f:sum_time:click_pay:id:adwares:cost:):(:s:+:):(:f:sum_time:pay:id:adwares:cost:):(:s:+:):(:f:sum_time:continue_pay:id:adwares:cost:):)';
		
		$report['head_name'][] = 'ティア総額';
		$report['colum_name'][] = 'f:sum_time:tier:id:adwares:cost';
		
		$reports["adwares"] = $report;
		
		$this->report_list['admin']['report'] = $reports;
		$reports = Array();
		
		//▼広告主
		
		//広告単位
		$report = Array();
		$report['case_label'] = '広告単位　成果レポート';
		$report['file_name'] = '広告単位成果レポート.csv';
		$report['table_name'] = 'adwares';
		$report['dm'] = false;
		$report['owner'] = false;
		$report['pre'] = Array('draw_time','draw_search');
		
		$report['head_name'][] = 'No';
		$report['colum_name'][] = 'f:no';

		$report['head_name'][] = '広告ID';
		$report['colum_name'][] = 'id';

		$report['head_name'][] = '広告名';
		$report['colum_name'][] = 'name';
		
		$report['head_name'][] = '広告アクセス数';
		$report['colum_name'][] = 'f:count_time:access:id:adwares';
		
		$report['head_name'][] = 'クリック広告成果数';
		$report['colum_name'][] = 'f:count_time:click_pay:id:adwares';
		$report['head_name'][] = 'クリック広告成果額';
		$report['colum_name'][] = 'f:sum_time:click_pay:id:adwares:cost';
		
		$report['head_name'][] = 'アフィリエイト広告成果数';
		$report['colum_name'][] = 'f:count_time:pay:id:adwares';
		$report['head_name'][] = 'アフィリエイト広告成果額';
		$report['colum_name'][] = 'f:sum_time:pay:id:adwares:cost';
		
		$report['head_name'][] = '継続広告成果数';
		$report['colum_name'][] = 'f:count_time:continue_pay:id:adwares';
		$report['head_name'][] = '継続広告成果額';
		$report['colum_name'][] = 'f:sum_time:continue_pay:id:adwares:cost';
		
		$report['head_name'][] = '広告成果総額';
		$report['colum_name'][] = 'in:(:calc:(:f:sum_time:click_pay:id:adwares:cost:):(:s:+:):(:f:sum_time:pay:id:adwares:cost:):(:s:+:):(:f:sum_time:continue_pay:id:adwares:cost:):)';
		
		$report['head_name'][] = 'ティア総額';
		$report['colum_name'][] = 'f:sum_time:tier:id:adwares:cost';
		
		$reports["adwares"] = $report;
		
		$this->report_list['cUser']['report'] = $reports;
		$reports = Array();
		
		//▼アフィリエイター
		
		$report['owner'] = false;
		$report['head_name']   = Array();
		$report['colum_name']  = Array();
		
		$report['head_name'][] = 'No';
		$report['colum_name'][] = 'f:no';

		$report['head_name'][] = '広告ID';
		$report['colum_name'][] = 'id';

		$report['head_name'][] = '広告名';
		$report['colum_name'][] = 'name';
		
		$report['head_name'][] = '広告アクセス数';
		$report['colum_name'][] = 'in:(:f:count_time:access:id:adwares:owner:(:s:=:):(:g:LOGIN_ID:):)';
		
		$report['head_name'][] = 'クリック広告成果数';
		$report['colum_name'][] = 'in:(:f:count_time:click_pay:id:adwares:owner:(:s:=:):(:g:LOGIN_ID:):)';
		$report['head_name'][] = 'クリック広告成果額';
		$report['colum_name'][] = 'in:(:f:sum_time:click_pay:id:adwares:cost:owner:(:s:=:):(:g:LOGIN_ID:):)';
		
		$report['head_name'][] = 'アフィリエイト広告成果数';
		$report['colum_name'][] = 'in:(:f:count_time:pay:id:adwares:owner:(:s:=:):(:g:LOGIN_ID:):)';
		$report['head_name'][] = 'アフィリエイト広告成果額';
		$report['colum_name'][] = 'in:(:f:sum_time:pay:id:adwares:cost:owner:(:s:=:):(:g:LOGIN_ID:):)';
		
		$report['head_name'][] = '継続広告成果数';
		$report['colum_name'][] = 'in:(:f:count_time:continue_pay:id:adwares:owner:(:s:=:):(:g:LOGIN_ID:):)';
		$report['head_name'][] = '継続広告成果額';
		$report['colum_name'][] = 'in:(:f:sum_time:continue_pay:id:adwares:cost:owner:(:s:=:):(:g:LOGIN_ID:):)';
		
		$report['head_name'][] = '広告成果総額';
		$report['colum_name'][] = 'in:(:calc:'.
									'(:f:sum_time:click_pay:id:adwares:cost:owner:(:s:=:):(:g:LOGIN_ID:):):(:s:+:):'.
									'(:f:sum_time:pay:id:adwares:cost:owner:(:s:=:):(:g:LOGIN_ID:):):(:s:+:):'.
									'(:f:sum_time:continue_pay:id:adwares:cost:owner:(:s:=:):(:g:LOGIN_ID:):):)';
		
		$report['head_name'][] = 'ティア総額';
		$report['colum_name'][] = 'in:(:f:sum_time:tier:id:adwares:cost:tier:(:s:=:):(:g:LOGIN_ID:):)';
		
		
		$reports["adwares"] = $report;
		$this->report_list['nUser']['report'] = $reports;
		
		
		//tdbからのコピーを許可するtable
        $this->report_list['admin']['table'] = Array( 
        	'nUser' => Array('label' => '一般ユーザー'),
			'access' => Array('label' => 'クリック管理'),
			'click_pay' => Array('label' => 'クリック報酬管理'),
			'pay' => Array('label' => '成功報酬管理'),
			'returnss' => Array('label' => '換金管理'),
			'adwares' => Array('label' => '広告管理')
        );
        
		$this->function_list['s'] = 'string';
		$this->function_list['r'] = 'relational';
		$this->function_list['n'] = 'nextRelational';
		$this->function_list['rep'] = 'repeat';
		$this->function_list['nrep'] = 'nextRepeat';
		$this->function_list['rm'] = 'mrelational';
		$this->function_list['cnt'] = 'count';
		$this->function_list['sum'] = 'sum';
		$this->function_list['ar'] = 'arrayReplace';
		$this->function_list['br'] = 'boolReplace';
		$this->function_list['cct'] = 'concatenation';
		$this->function_list['in'] = 'innerFunction';
		$this->function_list['calc'] = 'calculate';
		$this->function_list['g'] = 'globals';
		
	}

	/***************	 ユーザー定義メソッド	******************/
	
	//countのrelational側のtableにtimestampによる範囲検索をつけたもの。
	function count_time( &$db , $rec , $arg ){
		$rdb = GMList::getDB($arg[0]);
		$table = $rdb->getTable();
	
		$args = &$_POST;
		$a = 'registA';		$b = 'registB';
		$y_a = 'yearA';		$m_a = 'monthA';		$d_a = 'dayA';
		$y_b = 'yearB';		$m_b = 'monthB';		$d_b = 'dayB';
		if( strlen($args[$a])){
			$table = $rdb->searchTable( $table, 'regist', '>', $args[$a] );
		}else if( strlen($args[$y_a]) || strlen($args[$m_a]) || strlen($args[$d_a]) ){
			$time = mktime(0,0,0,
			strlen($args[$m_a]) ? $args[$m_a]-0 : 1,
			strlen($args[$d_a]) ? $args[$d_a]-0 : 1,
			strlen($args[$y_a]) ? $args[$y_a]-0 : 1970
			);
			$table = $rdb->searchTable( $table, 'regist', '>', $time );
			$args[$a] = $time;
		}

		if( strlen($args[$b])){
			$table = $rdb->searchTable( $table, 'regist', '<', $args[$b] );
		}else if( strlen($args[$y_b]) || strlen($args[$m_b]) || strlen($args[$d_b]) ){
			$time = mktime(0,0,0,
			strlen($args[$m_b]) ? $args[$m_b]-0 : 12,
			strlen($args[$d_b]) ? $args[$d_b]+1 : 31,
			strlen($args[$y_b]) ? $args[$y_b]-0 : 2069
			);
			$table = $rdb->searchTable( $table, 'regist', '<', $time );
			$args[$b] = $time;
		}
		
		$table = $rdb->searchTable( $table , $arg[2] , '=' , $db->getData( $rec , $arg[1] ) );
	
		for( $i=0 ; isset( $arg[3+$i] ) ; $i+=3 ){
			$table = $rdb->searchTable( $table , $arg[3+$i] , $arg[4+$i] , $arg[5+$i] );
		}
		
		return (string)$rdb->getRow( $table );
	}
	
	//countのrelational側のtableにtimestampによる範囲検索をつけたもの。
	function sum_time( &$db , $rec , $arg ){
		$rdb = GMList::getDB($arg[0]);
		$table = $rdb->getTable();
		
		$args = &$_POST;
		$a = 'registA';		$b = 'registB';
		$y_a = 'yearA';		$m_a = 'monthA';		$d_a = 'dayA';
		$y_b = 'yearB';		$m_b = 'monthB';		$d_b = 'dayB';
		if( strlen($args[$a])){
			$table = $rdb->searchTable( $table, 'regist', '>', $args[$a] );
		}else if( strlen($args[$y_a]) || strlen($args[$m_a]) || strlen($args[$d_a]) ){
			$time = mktime(0,0,0,
			strlen($args[$m_a]) ? $args[$m_a]-0 : 1,
			strlen($args[$d_a]) ? $args[$d_a]-0 : 1,
			strlen($args[$y_a]) ? $args[$y_a]-0 : 1970
			);
			$table = $rdb->searchTable( $table, 'regist', '>', $time );
			$args[$a] = $time;
		}

		if( strlen($args[$b])){
			$table = $rdb->searchTable( $table, 'regist', '<', $args[$b] );
		}else if( strlen($args[$y_b]) || strlen($args[$m_b]) || strlen($args[$d_b]) ){
			$time = mktime(0,0,0,
			strlen($args[$m_b]) ? $args[$m_b]-0 : 12,
			strlen($args[$d_b]) ? $args[$d_b]+1 : 31,
			strlen($args[$y_b]) ? $args[$y_b]-0 : 2069
			);
			$table = $rdb->searchTable( $table, 'regist', '<', $time );
			$args[$b] = $time;
		}
		
		
		$table = $rdb->searchTable( $table , $arg[2] , '=' , $db->getData( $rec , $arg[1] ) );
		
		for( $i=0 ; isset( $arg[4+$i] ) ; $i+=3 ){
			$table = $rdb->searchTable( $table , $arg[4+$i] , $arg[5+$i] , $arg[6+$i] );
		}
		
		return (string)$rdb->getSum( $arg[3], $table );
	}
	
	
	function draw_time(){
		$this->prefix .= '生成日：'.date('Y m/d').',';
	}
	
	function draw_search(){
		$times = "～";
		$args = &$_POST;
		$a = 'registA';		$b = 'registB';
		$y_a = 'yearA';		$m_a = 'monthA';		$d_a = 'dayA';
		$y_b = 'yearB';		$m_b = 'monthB';		$d_b = 'dayB';
		
		if( strlen($args[$a]) ){
			$times = date('Y m/d',$args[$a]).'～';
		}else if( strlen($args[$y_b]) || strlen($args[$m_b]) || strlen($args[$d_b]) ){
			$time = mktime(0,0,0,
			strlen($args[$m_b]) ? $args[$m_b]-0 : 12,
			strlen($args[$d_b]) ? $args[$d_b]+1 : 31,
			strlen($args[$y_b]) ? $args[$y_b]-0 : 2069
			);
			$times = date('Y m/d',$time).'～';
		}
		
		if( strlen($args[$b]) ){
			$times .= date('Y m/d',$args[$b] - 1);
		}else if( strlen($args[$y_b]) || strlen($args[$m_b]) || strlen($args[$d_b]) ){
			$time = mktime(0,0,0,
			strlen($args[$m_b]) ? $args[$m_b]-0 : 12,
			strlen($args[$d_b]) ? $args[$d_b]+1 : 31,
			strlen($args[$y_b]) ? $args[$y_b]-0 : 2069
			);
			$times .= date('Y m/d',$time);
		}
		
		if( $times != "～" ){
			$this->prefix .= '期間による絞り込み：'.$times.',';
		}
		if( strlen($args['cuser'])){
			$this->prefix .= '広告主による絞り込み：'.$args['cuser'].',';
		}
	}
	
	/************	 Reportモジュール定義メソッド	**************/

	function date(&$db , $rec , &$arg ){
		if( isset($arg[0]) && strlen($arg[0]) ){
			$colum = $arg[0];
		}else{
			$colum = 'regist';
		}
		return date( $this->TIME_FORMAT , $db->getData( $rec , $colum ) );
	}

	function no( &$db , $rec , &$arg ){
		return ++$arg[0];
	}
	function no_init( $arg ){
		$arg[0]=0;
		return $arg;
	}

	//カラムを返す
	function nomal( &$db , $rec , $colum ){
		return $db->getData( $rec , $colum);
	}

	function concatenation( &$db , $rec , $arg ){
		$str = "";
		foreach( $arg as $colum ){
			$str .= $db->getData( $rec , $colum);
		}
		return $str;
	}

	function relational( &$db , $rec , $arg ){
		$rdb = GMList::getDB($arg[0]);

		$rTable = $rdb->getTable();
		$rTable = $rdb->searchTable( $rTable , $arg[ 2 ] , '=' , $db->getData( $rec , $arg[ 1 ] ) );

		if( !$rdb->getRow( $rTable ) )
			return '';

		$rrec = $rdb->getRecord( $rTable , 0 );

		$discrimination = array_slice( $arg, 3);
		$func = $this->checkFunction($discrimination);
		$ret = $this->{$func['f']}( $rdb, $rrec, $func['v'] );
		 
		return $ret ? $ret : '';
	}

	function nextRelational( &$db , $rec , $arg ){
		$rdb = GMList::getDB($arg[0]);
		
		$table = $rdb->searchTable( $rdb->getTable() , $arg[2] , '=' , $db->getData( $rec , $arg[1] ) );
		$rrec = $rdb->getRecord( $table, 0 );
		
		$discrimination = array_slice( $arg, 4);
		$func = $this->checkFunction($discrimination);
		$ret = $this->{$func['f']}( $rdb, $rrec, $func['v'] );
		
		$row = $rdb->getRow( $table );
		if( $row > 1 ){
			for( $i=1; $i<$row; $i++ ){
				$rrec = $rdb->getRecord( $table, $i );
				
				if(!isset($this->next[$i-1])){$this->next[$i-1]=Array();}
				$this->next[$i-1] = array_pad( $this->next[$i-1],$arg[3],'' );
				 
				$this->next[$i-1][] = $this->{$func['f']}( $rdb, $rrec, $func['v'] );
			}
		}
		
		return $ret ? $ret : '';
	}
	
	//multiple relational
	function mrelational( &$db , $rec , $arg ){
		$rdb = GMList::getDB($arg[0]);
		 
		$datas = $db->getData( $rec , $arg[1] );
		$datas_array = explode( '/' , $datas );
		$str = '';
		foreach( $datas_array as $data ){

			$str .= $arg[0]->getData(
			$rdb->getRecord(
			$rdb->searchTable( $rdb->getTable() , $arg[2] , '=' , $data )
			, 0 )
			, $arg[3] ).'/';

		}
		return substr($str , 0 , -1);
	}

	//relationalの記法で指定されたテーブル・カラムに一致する結果の指定したカラムを繰り返し出力する
	function repeat( &$db , $rec , $arg ){
		$rdb = GMList::getDB($arg[0]);
		$columns = array_slice($arg,3);

		$table = $rdb->searchTable( $rdb->getTable() , $arg[2] , '=' , $db->getData( $rec , $arg[1] ) );

		$row = $rdb->getRow( $table );
		if( !$row ){return;}

		$ret = Array();

		for($i;$i<$row;$i++){
			$rec = $rdb->getRecord( $table, $i );
			foreach( $columns as $col ){
				$ret[] = $rdb->getData($rec,$col);
			}
		}
		return $ret;
	}

	//relationalの記法で指定されたテーブル・カラムに一致する結果の数を返す
	function count( &$db , $rec , $arg ){
		$rdb = GMList::getDB($arg[0]);
		 
		return (string)$rdb->getRow(
		$rdb->searchTable(
		$rdb->getTable() , $arg[2] , '=' , $db->getData( $rec , $arg[1] )
		) );
	}

	//relationalの記法で指定されたテーブル・カラムに一致する結果の合計を返す
	function sum( &$db , $rec , $arg ){
		$rdb = GMList::getDB($arg[0]);
		 
		return (string)$rdb->getSum(
		$arg[3],
		$rdb->searchTable(
		$rdb->getTable() , $arg[2] , '=' , $db->getData( $rec , $arg[1] )
		) );
	}

	//ccのarrayValueReplaceに近い
	function arrayReplace( &$db , $rec , $arg ){
		$datas = $db->getData( $rec , $arg[0] );
		$data_array = explode( '/' , $datas );

		$keys = array_flip( explode( '/' , $arg[1] ));
		$vals = explode( '/' , $arg[2] );

		$ret = Array();;
		foreach( $data_array as $data ){
			$ret[] = $vals[ $keys[$data] ];
		}
		return implode('/',$ret);
	}

	//bool値を見て引数1か引数2を出力
	function boolReplace( &$db , $rec , $arg ){
		if($db->getData( $rec , $arg[0] )){ return $arg[1]; }
		else{return $arg[2];}
	}

	//計算
	function calculate( &$db , $rec , $arg ){
		$expression = join($arg,'');
		eval('$ret = '.$expression.';');
		return $ret;
	}
	
	//in等の連結構造内で文字列をそのまま吐きたい
	function string( &$db , $rec , $arg ){
		return $arg[0];
	}
	
	function globals( &$db , $rec , $arg ){
		$ret = "";
		eval( 'global $'.$arg[0].'; $ret = $'.$arg[0].';' );
		return $ret;
	}

	/////////////////////mod tag


	function draw_case_list( &$gm, $rec, $args ){
		global $loginUserType;
		global $loginUserRank;

		$html = Template::getTemplateString( $gm, null, 'report', $loginUserRank, '', 'REPORT_PARTS' , false, null, 'case_list_head' );

		foreach( $this->report_list[$loginUserType]['report'] as $name  => $report ){
			$gm->setVariable( 'name' , $report['case_label'] );
			$gm->setVariable( 'id' , $name );

			if($report['dm'])
			$html .= Template::getTemplateString( $gm, null, 'report', $loginUserRank, '', 'REPORT_PARTS' , false, null, 'case_list_dm');
			else
			$html .= Template::getTemplateString( $gm, null, 'report', $loginUserRank, '', 'REPORT_PARTS' , false, null, 'case_list');

		}
		$html .= Template::getTemplateString( $gm, null, 'report', $loginUserRank, '', 'REPORT_PARTS' , false, null,'case_list_foot');

		$this->addBuffer( $html );
	}

	function draw_type_list( &$gm, $rec, $args ){
		global $loginUserType;
		global $loginUserRank;

		$html = Template::getTemplateString( $gm, null, 'report', $loginUserRank, '', 'REPORT_PARTS' , false, null, 'type_list_head');

		foreach( $this->report_list[$loginUserType]['table'] as $table_name  => $table_data ){
			$gm->setVariable( 'name' , $table_data['label'] );
			$gm->setVariable( 'id' , $table_name );
			 
			$html .= Template::getTemplateString( $gm, null, 'report', $loginUserRank, '', 'REPORT_PARTS' , false, null, 'type_list');
			 
		}
		$html .= Template::getTemplateString( $gm, null, 'report', $loginUserRank, '', 'REPORT_PARTS' , false, null,'type_list_foot');

		$this->addBuffer( $html );
	}

	function user_type_check($user_type){
		return isset($this->report_list[$user_type]);
	}

	//内部で$&beginという引数で分離して呼出しを行なう。
	//内部関数の末尾には&end引数をつける。
	function innerFunction( &$db , $rec , $arg ){
		//ってかこーゆー泥臭い実装望ましくないので、report.php側の呼出しの構造を改修したい。
		$_BEGIN = '(';
		$_BEGIN_BUF = ':begin_buf:';
		$_END = ')';
		
		$begin_stack = Array();
		while( ( $end = array_search( $_END, $arg ) ) !== FALSE ){
			while( ( $begin = array_search( $_BEGIN, $arg ) ) !== FALSE ){
				if($begin>$end){ break; }
				array_push( $begin_stack, $begin );
				$arg[ $begin ] = $_BEGIN_BUF;
			}
			$begin = array_pop($begin_stack);
			
			$discrimination = array_slice( $arg, $begin+1,$end-$begin-1);
			
			$func = $this->checkFunction($discrimination);
			
			$ret = (array)$this->{$func['f']}( $db , $rec , $func['v'] );
			
			array_splice( $arg, $begin, $end-$begin+1, $ret );
		}
		return join($arg,'');
	}

	function checkFunction( $discrimination ){
		if( $discrimination[0] == 'f'){
			$valiable = array_slice($discrimination,2);
			$function = $discrimination[1];
			
			if( method_exists($this,$function.'_init') ){
				$valiable = $this->{$function.'_init'}($valiable);
			}
		}else if( isset( $this->function_list[$discrimination[0]] ) ){
			$valiable = array_slice($discrimination,1);
			$function = $this->function_list[$discrimination[0]];
				
			if( method_exists($this,$function.'_init') ){
				$valiable = $this->{$function.'_init'}($valiable);
			}
		}else{
			$valiable = $discrimination[0];
			$function = 'nomal';
		}
		return Array( 'v' => $valiable, 'f' => $function );
	}
}

?>