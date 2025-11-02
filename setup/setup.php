<?php

	include_once 'setup/option.php';
	include_once 'setup/confSetting.php';
	include_once 'setup/tableSetting.php';
	include_once 'setup/presetSetting.php';
	include_once 'setup/labelSetting.php';
	include_once 'setup/csvSetting.php';
	include_once 'setup/templateFile.php';
	include_once 'setup/confFile.php';

	chdir( '../' );

	$Option = new Option( 'setup/setup.txt' );
	TemplateFile::Save();
	ConfFile::Save();

?>
