<?php
include './vendor/autoload.php';
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;

$binPath = 'C:/Users/AnsellC/Desktop/Encoder/bin';
$watermarkPath = $binPath . "/au.ass";

if( !isset($argv[1]) OR !isset($argv[2]) ) {

    $source = "D:/queue";
    $dest =  "D:/done";

} else {
    $source = $argv[1];
    $dest = $argv[2];
}

$adapter = new Local($source);
$filesystem = new Filesystem($adapter);

$animes = $filesystem->listContents('./');

echo "Found ". count($animes). " anime...\n";


$i = 0;
$total_videos = 0;
foreach($animes AS $anime) {

    echo "Processing: \033[0;32m".$anime['path']."\033[0m\n";
    $files = $filesystem->listContents($anime['path']);
    $animes[$i]['videos'] = $files;
    $total_videos += count($files);

    $s = 'ffmpeg -i "'. $source .'/'. $files[0]['path'] .'" 2>&1 &';

    unset($out);
    exec($s, $out);
    foreach($out AS $line) {
       if (preg_match('/Stream #0/', $line))
        echo $line ."\n";
    }
    echo "SELECT AUDIO: ";
    $animes[$i]['audio_stream'] = fgets(STDIN);

    echo "SELECT SUBTITLE: ";
    $animes[$i]['subtitle_stream'] = fgets(STDIN);
    echo "\n\n\n";

    $i++;
}

flush();
$i = 1;
$t = 1;
foreach($animes AS $anime) {

    $x = 1;
    if(!file_exists($dest . '/'. $anime['path'])) {

        mkdir($dest . '/'. $anime['path']);

    }
    
    foreach($anime['videos'] AS $video) {

        echo "ENCODING: \033[0;32m".$video['basename']."\033[0m {$t} of {$total_videos}\n";
        $video_path = $source .'/'. $video['path'];
        $out_path = $dest .'/'. $anime['path'] .'/'. $video['basename'] .'.mp4';
        $cmd = 'ffmpeg -i "'. $video_path.'"';
        $cmd .= ' -c: libx264 -preset faster -tune animation -crf 23 -profile:v high -level 4.1 -pix_fmt yuv420p -c:a aac -b:a 192k -vf "ass=\''.str_replace(":", "\:", $watermarkPath).'\', subtitles=\''.str_replace(":", "\:", $video_path).'\'" "'.$out_path.'"';
      
        $x++;
        $t++;
        exec($cmd);
        exit;

    }
   
    

   $i++;
}