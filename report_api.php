<?php

	ob_start();

	try
	{
		include_once 'custom/head_main.php';

		$report     = new ReportEx();
		$case       = $_POST[ 'case' ];
		$exportName = $report->getExportName( $case );

		$report->downloadReport( $case , $exportName );
	}
	catch( Exception $e_ )
	{
		ob_clean();

		//エラーメッセージをログに出力
		$errorManager = new ErrorManager();
		$errorMessage = $errorManager->GetExceptionStr( $e_ );

		$errorManager->OutputErrorLog( $errorMessage );

		//例外に応じてエラーページを出力
		$className = get_class( $e_ );
		ExceptionManager::DrawErrorPage($className );
	}

	ob_end_flush();
