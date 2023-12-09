#!/usr/bin/php

<?php

    $file_mono="/tmp/png2hex_mono.png";
    $name = (isset($argv[1]) ? $argv[1] : "./nofoto1.png" );
    $file_hex = ( isset($argv[2]) ? $argv[2] : $name.".txt" );

// die("  $file_mono  $name   $file_hex ");

    // сделать картинку монохромной.png
    image2mono($name,$file_mono,100);


    // отправить в файл
    xprint_png($file_mono,$file_hex);
    unlink($file_mono);

echo "done";

    $s=file_get_contents($file_hex);
    if(!preg_match("/IMAGE\:\s+\[\s+(\d+) x (\d+).+?let image = \[\s+([^\n]+)/si",$s,$m)) die('er1');
//    print_r($m);
//    m[1] => 100
//    m[2] => 100
//    m[3] => 255,255,255,255,255,255,

    $frs="/home/work/RUST/KAMPELA/kampela-firmware/kampela-ui/src/lleo.rs";

    $s=file_get_contents($frs); if(empty($s)) die('er2.0');
    if(!preg_match("/(\s*let nofoto_image = \[\n)([^\]]+)(\s+\]\;)/s",$s,$me)) die('er2.1');

    $me[2]=preg_replace("/\n\s*[^\n\/]+/s",'',$me[2]);
    $me[2].="\n".$m[3]."\n";

    $s=str_replace($me[0],$me[1].$me[2].$me[3],$s);
    file_put_contents($frs,$s);

//    print_r($me);

/*
// IMAGE: [ 100 x 100 ] white: 0
// size: 1250 bytes (0)

let image = [
255,255,255,255,255,255,255,255,255,255,255,255,240,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,
];



    let nofoto_image = [
        // 0,0,0,0,0,95,255,128,0,0,0,0,0,0,0,0,0,127,255,255,224,0,0,0,0,0,0,0,0,63,255,255,255,192,0,0,0,0,0,0,0,31,25
        34,79,255,255,255,251,255,255,255,144,16,10,24,2,127,255,255,255,223,255,255,252,16,24,0,150,47,255,255,253,223,
    ];

*/

    die('OK');

####################################################

// сделать картинку монохромной.png (apt install imagemagick)
function image2mono($file_img,$file_mono,$width=576) {
    exec("/usr/bin/convert ".$file_img." -resize ".$width."x -colors 2 "
	."-normalize -colorspace Gray -dither FloydSteinberg -alpha off -background white -alpha Background ".$file_mono);
}

// напечатать картинку.png на принтере
function xprint_png($file,$file_hex='hex.txt') {

    list($w,$h,$type) = @getimagesize($file);
    if( empty($w) || empty($h) || false===($im=imagecreatefrompng($file)) ) die("\nError: Empty file ".$file);

    $w0 = floor(($w+7)/8); // байт в 8 раз меньше точек
    $min=0xFFFFFF;
	for($y=0;$y<$h;$y++) { // найти сегодняшний код белых точек
	    for($x=0;$x<$w;$x++) {
		$c=imagecolorat($im,$x,$y);
		if($c>$min) break;
		$min=min($c,$min);
	    }
	    if($c>$min) break;
	}


    $BY=array();

    $bit=7; $acc=0;
    // for($y=$h-1;$y>=0;$y--) { 
    for($y=0;$y<$h;$y++) { // прямое/перевернутое
	// for($x=$w-1;$x>=0;$x--) {
	for($x=0;$x<$w;$x++) { // прямое/перевернутое
	    $z=( imagecolorat($im,$x,$y) == $min ? 1 : 0 );
	    $acc = $acc | ($z << $bit);
	    if((--$bit) < 0 ) { $BY[]=$acc; $acc=0; $bit=7; }
	}
	// if($bit!=7) $out.=$acc.", "; // добавить если не кратно
    }

    $o="
// IMAGE: [ $w x $h ] white: $min
// size: ".sizeof($BY)." bytes (".(sizeof($BY)*8 - $w*$h).")

let image = [\n".implode(",",$BY)."\n];\n";


    // mydraw($w,$h,$BY);
    file_put_contents($file_hex,$o);
}


function mydraw($size_x,$size_y,$image) {

# $size_x=10;
# $size_y=10;
# $image=[0b11111111,0b00000111,0b00001000,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1];


    $s='';
    $r=array();
    for($y=0;$y<$size_y;$y++)
     for($x=0;$x<$size_x;$x++) {
	if(!isset($r[$y])) $r[$y]=array();
	if(!isset($r[$y][$x])) $r[$y][$x]='.';
     }

    for($n=0;$n < $size_x*$size_y;$n++) {
        $y = floor($n / $size_x);
        $x = $n % $size_x;
        $i = $n % 8;
	$nn = floor($n/8);

echo "y=$y x=$x nn=$nn i=$i [".(1<<$i)."]\n";

        // let c:bool = image[(n/8) as usize] > 128; // & (1>>i) != 0image[(n/8) as usize] & (1>>i) != 0
        $c = ($image[$nn] & (0b10000000>>$i) ? '#' : '_');
	$r[$y][$x]=$c;
    }


    for($y=0;$y<$size_y;$y++) {
     echo "\n";
     for($x=0;$x<$size_x;$x++) echo $r[$y][$x];
    }
    echo "\nSIZE: $size_x x $size_y\n";
}

?>