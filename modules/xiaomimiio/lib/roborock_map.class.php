<?php
class roborockMap {
 public $version=""; public $width=0; public $height=0; public $blocks=array();
 public $pixels=""; public $robot=array("x"=>0,"y"=>0); public $charger=array("x"=>0,"y"=>0);
 public $path=array(); public $zones=array();
 private function u16($s,$o){$v=unpack("v",substr($s,$o,2));return (int)$v[1];}
 private function u32($s,$o){$v=unpack("V",substr($s,$o,4));return (int)$v[1];}
 public function parse($gz){
  $raw=@gzdecode($gz); if($raw===false||$raw==="") $raw=$gz;
  if(substr($raw,0,2)!=="RR"||strlen($raw)<20) return false;
  $hl=$this->u16($raw,2); if($hl<20||$hl>strlen($raw))$hl=20;
  $this->version=$this->u16($raw,8).".".$this->u16($raw,10); $o=$hl;
  while($o+8<=strlen($raw)){
   $t=$this->u16($raw,$o);$bh=$this->u16($raw,$o+2);$dl=$this->u32($raw,$o+4);
   if($bh<8||$o+$bh+$dl>strlen($raw))break;
   $this->blocks[$t]=substr($raw,$o+$bh,$dl);$o+=$bh+$dl;
  }
  if(isset($this->blocks[2])){
   $b=$this->blocks[2];$this->width=$this->u32($b,12);$this->height=$this->u32($b,8);
   if($this->width>0&&$this->height>0&&$this->width<2000&&$this->height<2000){
    $need=$this->width*$this->height;
    foreach(array(20,28) as $po){$px=substr($b,$po,$need);if(strlen($px)===$need){$this->pixels=$px;break;}}
   }
  }
  if(isset($this->blocks[1])){$b=$this->blocks[1];$this->charger=array("x"=>$this->u32($b,0),"y"=>$this->u32($b,4));}
  if(isset($this->blocks[8])){$b=$this->blocks[8];$this->robot=array("x"=>$this->u32($b,0),"y"=>$this->u32($b,4));}
  if(isset($this->blocks[3])){$b=$this->blocks[3];$n=min($this->u32($b,0),100000);$o=20;
   for($i=0;$i<$n&&$o+4<=strlen($b);$i++){$this->path[]=array("x"=>$this->u16($b,$o),"y"=>$this->u16($b,$o+2));$o+=4;}}
  if(isset($this->blocks[6])){$b=$this->blocks[6];$n=min($this->u32($b,0),10000);$o=4;
   for($i=0;$i<$n&&$o+8<=strlen($b);$i++){$this->zones[]=array("x1"=>$this->u16($b,$o),"y1"=>$this->u16($b,$o+2),"x2"=>$this->u16($b,$o+4),"y2"=>$this->u16($b,$o+6));$o+=8;}}
  return true;
 }
 public function toArray(){return array("version"=>$this->version,"width"=>$this->width,"height"=>$this->height,"robot"=>$this->robot,"charger"=>$this->charger,"zones"=>$this->zones,"path_points"=>count($this->path));}
 public function renderPng($scale=3){
  if($this->width<1||$this->height<1||!function_exists("imagecreatetruecolor"))return "";
  $scale=max(1,min(6,(int)$scale));$w=$this->width*$scale;$h=$this->height*$scale;
  $im=imagecreatetruecolor($w,$h);$white=imagecolorallocate($im,255,255,255);
  $in=imagecolorallocate($im,232,238,244);$wall=imagecolorallocate($im,44,62,80);
  $pc=imagecolorallocate($im,41,128,185);$rc=imagecolorallocate($im,192,57,43);
  $cc=imagecolorallocate($im,39,174,96);$zc=imagecolorallocate($im,230,126,34);
  imagefill($im,0,0,$white);
  for($y=0;$y<$this->height;$y++)for($x=0;$x<$this->width;$x++){
   $i=$y*$this->width+$x;if($i>=strlen($this->pixels))break 2;$v=ord($this->pixels[$i]);if($v===0)continue;
   $c=($v===1||($v&2))?$wall:$in;
   imagefilledrectangle($im,$x*$scale,$y*$scale,$x*$scale+$scale-1,$y*$scale+$scale-1,$c);
  }
  if(count($this->path)>1){$prev=false;foreach($this->path as $pt){
   $px=(int)round($pt["x"]/50)*$scale;$py=(int)round($pt["y"]/50)*$scale;
   if($px>=0&&$py>=0&&$px<$w&&$py<$h){if($prev)imageline($im,$prev[0],$prev[1],$px,$py,$pc);$prev=array($px,$py);}}}
  foreach($this->zones as $z){$x1=(int)round($z["x1"]/50)*$scale;$y1=(int)round($z["y1"]/50)*$scale;$x2=(int)round($z["x2"]/50)*$scale;$y2=(int)round($z["y2"]/50)*$scale;
   imagerectangle($im,max(0,$x1),max(0,$y1),min($w-1,$x2),min($h-1,$y2),$zc);}
  $rx=(int)round($this->robot["x"]/50)*$scale;$ry=(int)round($this->robot["y"]/50)*$scale;
  imagefilledellipse($im,$rx,$ry,max(8,$scale*4),max(8,$scale*4),$rc);
  $cx=(int)round($this->charger["x"]/50)*$scale;$cy=(int)round($this->charger["y"]/50)*$scale;
  imagefilledrectangle($im,$cx-$scale*2,$cy-$scale*2,$cx+$scale*2,$cy+$scale*2,$cc);
  ob_start();imagepng($im);$bin=ob_get_clean();imagedestroy($im);return $bin;
 }
}