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
$op = isset($_REQUEST["op"]) ? preg_replace("/[^a-z_]/", "", $_REQUEST["op"]) : "status";
$rec = SQLSelectOne("SELECT * FROM miio_devices WHERE ID=" . $id);
if (!$rec["ID"]) { echo json_encode(array("status"=>"error","msg"=>"Device not found")); exit; }
function afMap($model) {
 $base=array("status"=>array(2,1),"fault"=>array(2,2),"target_time"=>array(2,3),"target_temperature"=>array(2,4),"left_time"=>array(2,5),"recipe_id"=>array(3,1),"recipe_name"=>array(3,2),"work_time"=>array(3,3),"work_temp"=>array(3,4),"appoint_time"=>array(3,5),"food_quanty"=>array(3,6),"preheat_switch"=>array(3,7),"appoint_time_left"=>array(3,8),"recipe_sync"=>array(3,9),"turn_pot"=>array(3,10));
 $actions=array("start_cook"=>array(2,1),"cancel_cooking"=>array(2,2),"pause"=>array(2,3),"start_custom_cook"=>array(3,1),"resume_cooking"=>array(3,2));
 if ($model==="careli.fryer.maf10a" || $model==="careli.fryer.maf14" || $model==="xiaomi.fryer.maf14" || $model==="viomi.fryer.v3") {
  $base["recipe_id"]=array(2,($model==="careli.fryer.maf14"?8:($model==="viomi.fryer.v3"?6:10)));
  $base["recipe_name"]=array(2,($model==="careli.fryer.maf14"?10:($model==="viomi.fryer.v3"?7:11)));
  $base["recipe_sync"]=array(2,($model==="careli.fryer.maf14"?9:12));
  $actions["start_recipe_cook"]=array(2,($model==="careli.fryer.maf14"?4:5));
 }
 if ($model==="careli.fryer.maf534" || $model==="534") {
  $base["recipe_command"]=array(2,11); $base["mode"]=array(2,6);
  $actions["start_recipe_cook"]=array(2,4);
 }
 return array("props"=>$base,"actions"=>$actions);
}
$m=afMap($rec["DEVICE_TYPE"]);
require_once(DIR_MODULES . "xiaomimiio/lib/mipacket.class.php");
require_once(DIR_MODULES . "xiaomimiio/lib/miio.class.php");
$dev=new miIO($rec["IP"],null,$rec["TOKEN"]);
function afSend($dev,$method,$params){ $dev->msgSendRcv($method,$params); $j=json_decode($dev->data,true); return is_array($j)?$j:array("result"=>$dev->data); }
function afProps($dev,$map){ $q=array(); foreach($map as $did=>$p){ $q[]=array("did"=>$did,"siid"=>$p[0],"piid"=>$p[1]); } return afSend($dev,"get_properties",$q); }
function afValues($res){ $out=array(); if(isset($res["result"])&&is_array($res["result"])){foreach($res["result"] as $r){if(is_array($r)&&isset($r["did"])){$out[$r["did"]]=isset($r["value"])?$r["value"]:(isset($r["code"])?$r["code"]:null);}}} return $out; }
if($op==="status"){ $v=afValues(afProps($dev,$m["props"])); echo json_encode(array("status"=>"ok","values"=>$v)); exit; }
if($op==="recipes"){
 $v=afValues(afProps($dev,$m["props"])); $raw=isset($v["recipe_sync"])?$v["recipe_sync"]:"";
 $recipes=array(); $text=is_string($raw)?$raw:json_encode($raw);
 $decoded=json_decode($text,true);
 if(is_array($decoded)){foreach($decoded as $k=>$r){if(is_array($r)){$recipes[]=array("id"=>isset($r["id"])?$r["id"]:(is_string($k)?$k:count($recipes)+1),"name"=>isset($r["name"])?$r["name"]:"","data"=>$r);}else{$recipes[]=array("id"=>$k,"name"=>is_string($r)?$r:"","data"=>$r);}}}
 echo json_encode(array("status"=>"ok","raw"=>$text,"recipes"=>$recipes)); exit;
}
if($op==="set"){
 $did=isset($_POST["did"]) ? preg_replace("/[^a-z_0-9]/","",$_POST["did"]) : "";
 if(!isset($m["props"][$did])){ echo json_encode(array("status"=>"error","msg"=>"Unknown property")); exit; }
 $value=isset($_POST["value"]) ? $_POST["value"] : "";
 if($did==="food_quanty"){$value=max(0,min(5,(int)$value));}
 if($did==="target_time"||$did==="work_time"||$did==="appoint_time"){$value=max(0,min(1440,(int)$value));}
 if($did==="target_temperature"||$did==="work_temp"){$value=max(40,min(230,(int)$value));}
 $p=$m["props"][$did];
 $res=afSend($dev,"set_properties",array(array("did"=>$did,"siid"=>$p[0],"piid"=>$p[1],"value"=>$value)));
 echo json_encode(array("status"=>"ok","result"=>isset($res["result"])?$res["result"]:$res)); exit;
}
if($op==="action"){
 $name=isset($_POST["name"]) ? preg_replace("/[^a-z_]/","",$_POST["name"]) : "";
 if(!isset($m["actions"][$name])){ echo json_encode(array("status"=>"error","msg"=>"Unknown action")); exit; }
 $a=$m["actions"][$name]; $in=array();
 if(isset($_POST["in"])){ $d=json_decode($_POST["in"],true); if(is_array($d))$in=$d; }
 $res=afSend($dev,"action",array(array("siid"=>$a[0],"aiid"=>$a[1],"in"=>$in)));
 echo json_encode(array("status"=>"ok","result"=>isset($res["result"])?$res["result"]:$res)); exit;
}
if($op==="apply"){
 $raw=isset($_POST["recipe_sync"]) ? $_POST["recipe_sync"] : "";
 json_decode($raw,true);
 if(json_last_error()!==JSON_ERROR_NONE){ echo json_encode(array("status"=>"error","msg"=>"Invalid JSON recipe")); exit; }
 $p=$m["props"]["recipe_sync"];
 $res=afSend($dev,"set_properties",array(array("did"=>"recipe_sync","siid"=>$p[0],"piid"=>$p[1],"value"=>$raw)));
 echo json_encode(array("status"=>"ok","result"=>isset($res["result"])?$res["result"]:$res,"msg"=>"Recipe saved. Use Start recipe to apply.")); exit;
}
echo json_encode(array("status"=>"error","msg"=>"Unknown operation"));