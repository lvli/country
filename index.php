<?php
set_time_limit(0);
header("Content-type: text/html; charset=utf-8");
$dir_name = 'similarweb-topsites';

$big_domain = array(
	'google.com',
	'bing.com',
	'baidu.com',
	'ask.com',
);

$country_list = file($dir_name . iconv("UTF-8", "GBK", '/国家列表.txt'));
$country_arr = array();
foreach($country_list as $country){
	$country = iconv('ASCII', 'UTF-8//IGNORE', $country);
	$arr = explode(' ', $country);
	$country_arr[$arr[0]] = str_replace(array('+', '-'), '', $arr[1]);
}

$file_list = array();
if($Handle = opendir($dir_name)) {
	while( false != ($File = readdir($Handle)) ){
		if($File != '.' && $File != '..' && $File != iconv("UTF-8", "GBK", '国家列表.txt') ){
			$file_list[] = $dir_name . '/' . $File;
		}
	}
	closedir($Handle);
}

$data_10 = $data_other = $data_10_no_big = $data_no_country = array();
$share_10 = $share_other = $share_10_no_big = $share_no_country = array();
foreach($file_list as $file_path){
	$content = file_get_contents($file_path);
	$content_arr = json_decode($content, true);
	if(!empty($content_arr['Data']))	foreach($content_arr['Data'] as $v){
		$key = intval(basename($file_path, '.txt'));
		$country = isset($country_arr[$key]) ? $country_arr[$key] : '';
		$one = array(
			'Rank' => $v['Rank'],
			'Domain' => $v['Domain'],
			'Share' => number_format($v['Share'] * 100, 2) . '%',
			'Country' => $country,
		);

		$key_one =  $v['Domain'] . $country;
		if(bccomp($v['Share'], '0.01', 10) > 0){ 	//大于10%的放到一个文件里面
			$share_10[$key_one] = $v['Share'];
			$data_10[$key_one] = $one;
			if(!in_array($v['Domain'], $big_domain)){
				$share_10_no_big[$key_one] = $v['Share'];
				$data_10_no_big[$key_one] = $one;
			}
		}else{
			$share_other[$key_one] =  $v['Share'];
			$data_other[$key_one] = $one;
		}

		$share_no_country[$v['Domain']] = $v['Share'];
		if(!empty($data_no_country[$v['Domain']]['Country'])){
			$one['Country'] = $data_no_country[$v['Domain']]['Country'] . ',' . $one['Country'];
			$one['Country'] = trim($one['Country'], ',');
		}
		$data_no_country[$v['Domain']] = $one;
	}
}

$table_head = array('Rank', 'Domain', 'Share', 'Country');
array_multisort(array_values($share_10), SORT_DESC, $data_10);
array_multisort(array_values($share_10_no_big), SORT_DESC, $data_10_no_big);
array_multisort(array_values($share_other), SORT_DESC, $data_other);
array_multisort(array_values($share_no_country), SORT_DESC, $data_no_country);
array_unshift($data_10, $table_head);
array_unshift($data_10_no_big, $table_head);
array_unshift($share_other, $table_head);
array_unshift($data_no_country, $table_head);

$file = fopen(iconv("UTF-8", "GBK", '统计大于10%的.csv'), 'w');
foreach($data_10 as $val ){
	if(false === fputcsv($file, $val) ){
		die('写入数据失败');
	}
}
fclose($file);

$file = fopen(iconv("UTF-8", "GBK", '统计大于10%的(去掉大网站域名).csv'), 'w');
foreach($data_10_no_big as $val ){
	if(false === fputcsv($file, $val) ){
		die('写入数据失败');
	}
}
fclose($file);

$file = fopen(iconv("UTF-8", "GBK", '统计小于等于10%的.csv'), 'w');
foreach($data_other as $val ){
	if(false === fputcsv($file, $val) ){
		die('写入数据失败');
	}
}
fclose($file);

$file = fopen(iconv("UTF-8", "GBK", '网站域名排行(去重).csv'), 'w');
foreach($data_no_country as $val ){
	if(false === fputcsv($file, $val) ){
		die('写入数据失败');
	}
}
fclose($file);

$file = fopen(iconv("UTF-8", "GBK", '大网站域名列表.txt'), 'w');
foreach($big_domain as $val ){
	if(false === fputs($file, $val . PHP_EOL) ){
		die('写入数据失败');
	}
}
fclose($file);

$base_path = __DIR__ . DIRECTORY_SEPARATOR;
echo "生成文件如下:<br/>" ;
echo $base_path . "统计大于10%的(去掉大网站域名).csv<br/>";
echo $base_path . "网站域名排行(去重).csv<br/>";
echo $base_path . "统计大于10%的.csv<br/>";
echo $base_path . "统计小于等于10%的.csv<br/>";
echo $base_path . "大网站域名列表.txt<br/>";
exit;