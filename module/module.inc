<?PHP
error_log("=== module.inc START ===");

//command_baseの読み込み
error_log("module.inc: Including command_base.php from " . dirname(__DIR__) . "/include/base/command_base.php");
include_once dirname(__DIR__) . "/include/base/command_base.php";
error_log("module.inc: command_base.php included");

error_log("module.inc: Including Util.php from " . dirname(__DIR__) . "/include/base/Util.php");
include_once dirname(__DIR__) . "/include/base/Util.php";
error_log("module.inc: Util.php included");

error_log("module.inc: Including global.php from " . dirname(__DIR__) . "/custom/global.php");
include_once dirname(__DIR__) . "/custom/global.php";
error_log("module.inc: global.php included");

//モジュール設定の読み込み
error_log("module.inc: Including moduleConf.php");
include_once dirname(__DIR__) . '/custom/extends/moduleConf.php';
error_log("module.inc: moduleConf.php included");

//system
include_once __DIR__ . '/reportEx.inc';
include_once __DIR__ . '/thumbnail.inc';
include_once __DIR__ . '/secretAdwares.inc';
include_once __DIR__ . '/accountLock.inc';

error_log("=== module.inc END ===");

?>
