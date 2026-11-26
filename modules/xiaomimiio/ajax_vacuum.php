<?php
header("Content-Type: application/json; charset=utf-8");
chdir(dirname(__FILE__) . "/../../");
include_once("./config.php");
include_once("./lib/loader.php");
include_once("./load_settings.php");
$session = new session("prj");
include_once(DIR_MODULES . "control_access/control_access.class.php");
$access = new control_access();
if (!$access->checkAccess("xiaomimiio")) { http_response_code(403); echo json_encode(array("status"=>"error","msg"=>"Access denied")); exit; }
$id = isset($_REQUEST["id"]) ? (int)$_REQUEST["id"] : 0;
$op = isset($_REQUEST["op"]) ? preg_replace("/[^a-z_]/", "", $_REQUEST["op"]) : "";
$rec = SQLSelectOne("SELECT * FROM miio_devices WHERE ID=" . $id);
if (!$rec["ID"]) { echo json_encode(array("status"=>"error","msg"=>"Device not found")); exit; }
require_once(DIR_MODULES . "xiaomimiio/lib/mipacket.class.php");
require_once(DIR_MODULES . "xiaomimiio/lib/miio.class.php");
require_once(DIR_MODULES . "xiaomimiio/lib/roborock_map.class.php");
$miio = new miIO($rec["IP"], null, $rec["TOKEN"]);
function vacuumReply($miio, $method, $params) {
 $miio->msgSendRcv($method, $params);
 $raw = $miio->data;
 $json = json_decode($raw, true);
 return is_array($json) ? $json : array("result"=>$raw);
}
if ($op === "map") {
 $res = vacuumReply($miio, "get_map_v1", array());
 $b64 = isset($res["result"]) ? $res["result"] : "";
 if (is_array($b64)) { $b64 = reset($b64); }
 $binary = base64_decode((string)$b64, true);
 $map = new roborockMap();
 if ($binary === false || !$map->parse($binary)) { echo json_encode(array("status"=>"error","msg"=>"Map decode failed")); exit; }
 $dir = ROOT . "cms/cached/xiaomimiio";
 if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
 $png = $map->renderPng(3);
 $file = $dir . "/vacuum-" . $id . ".png";
 if ($png !== "" && file_put_contents($file, $png) !== false) {
  $url = BASE_URL . "/cms/cached/xiaomimiio/vacuum-" . $id . ".png";
 } else { $url = ""; }
 echo json_encode(array("status"=>"ok","map"=>$map->toArray(),"url"=>$url));
 exit;
}
if ($op === "rooms") {
 $res = vacuumReply($miio, "get_room_mapping", array());
 $rooms = array();
 if (isset($res["result"]) && is_array($res["result"])) {
  foreach ($res["result"] as $pair) { if (is_array($pair) && count($pair) >= 2) { $rooms[] = array("room_id"=>(int)$pair[0],"segment"=>(int)$pair[1]); } }
 }
 echo json_encode(array("status"=>"ok","rooms"=>$rooms));
 exit;
}
if ($op === "clean") {
 $method = isset($_POST["method"]) ? preg_replace("/[^a-z_]/", "", $_POST["method"]) : "";
 $allowed = array("app_segment_clean","app_zoned_clean","app_goto_target","app_spot","app_start","app_pause","app_stop","app_charge");
 if (!in_array($method, $allowed, true)) { echo json_encode(array("status"=>"error","msg"=>"Method not allowed")); exit; }
 $params = array();
 if (isset($_POST["params"])) {
  $decoded = json_decode($_POST["params"], true);
  if (is_array($decoded)) { $params = $decoded; }
 }
 $res = vacuumReply($miio, $method, $params);
 echo json_encode(array("status"=>"ok","result"=>isset($res["result"]) ? $res["result"] : $res));
 exit;
}
echo json_encode(array("status"=>"error","msg"=>"Unknown operation"));