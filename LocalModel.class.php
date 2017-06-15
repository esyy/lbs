<?php

namespace Wei\Model;
use Think\Model;
class LocalModel extends Model {
	protected $autoCheckFields =false;
	
//class LocalModel extends Model {
/*无用户坐标信息时，要跟据IP或取大概坐标信息*/
	public function getxy($getIp=''){
		
        if(!$getIp)$getIp=get_client_ip();//$_SERVER["REMOTE_ADDR"];
		//test
        if($getIp=='127.0.0.1'){
			return array(
					'city'     =>'深圳市',
					'code'     =>'440300',
					'x' 	=>'114.02608',
					'y' 	=>'22.536112',
				);
				
		}
		
		//$resData = M('wxlocal')->where(array('ip'=>$getIp))->find();
		$cache_id='initByCiy_restapiamap_'.$getIp;
		$resData = S($cache_id);// 获取缓存数据
		/**/
		if(!$resData){
			//$content = $this->https_request("http://api.map.baidu.com/location/ip?ak=###############&ip={$getIp}&coor=bd09ll");
			$content = $this->https_request("http://restapi.amap.com/v3/ip?key=################&ip=".$getIp);
			logs('locationIP',$getIp."\r\n高得定位：".session('member.uid').$content);
			$res = json_decode($content,true);
			if($res && isset($res['adcode'])){
				//$s = substr($res['rectangle'],0,strpos($res['rectangle'],';'));
				$s = str_replace(';',',',$res['rectangle']);
				$stemp = explode(',',$s);
				$resData = array(
					'ip' => $getIp,
					/* 'province' =>$res['province'],
					'city'     =>$res['city'],
					'code'     =>$res['adcode'], */
					'province' =>is_array($res['province'])?'':$res['province'],
					'city'     =>is_array($res['city'])?'':$res['city'],
					'code'     =>is_array($res['adcode'])?'':$res['adcode'],
					'x' 	=>(($stemp[0]+$stemp[2])/2),
					'y' 	=>(($stemp[1]+$stemp[3])/2),
					'json' 	=>$content,
				);
				
				S($cache_id, $resData,7200);
				//M('wxlocal')->add($resData);
			}
		}
		return $resData;
    }
	
	
	public function getxyww($getIp=''){
		
        if(!$getIp)$getIp=get_client_ip();//$_SERVER["REMOTE_ADDR"];
		//test
        if($getIp=='127.0.0.1')$getIp='##############';
		
		//$resData = M('wxlocal')->where(array('ip'=>$getIp))->find();
		//$cache_id='initByCity_wei3_'.urlencode($city).$cinemacode.session('member.token');
		//$resData = S($cache_id);// 获取缓存数据
		
		if(!$resData){
			$content = $this->https_request("http://api.map.baidu.com/location/ip?ak=#############&ip={$getIp}&coor=bd09ll");
			$res = json_decode($content,true);
			//S($cache_id, $resData,240);
			if($res && isset($res['content']['point'])){
				$resData = array(
					'ip' => $getIp,
					'province' =>$res['content']['address_detail']['province'],
					'city'     =>$res['content']['address_detail']['city'],
					'x' 	=>$res['content']['point']['x'],
					'y' 	=>$res['content']['point']['y'],
					'json' 	=>$content,
				);
				//M('wxlocal')->add($resData);
			}
		}
		return $resData;
    }

	/* 请求方法 */
	public function https_request($url, $data = '')
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		if ($data) {
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_TIMEOUT, 60);
		$output = curl_exec($curl);
		curl_close($curl);
		return $output;
	}
	
	/**
	*  @desc 根据两点间的经纬度计算距离
	*  @param float $lat 纬度值
	*  @param float $lng 经度值

	function getDistance($lat1, $lng1, $lat2, $lng2)
	{
		 $earthRadius = 6367000; //approximate radius of earth in meters
		 $lat1 = ($lat1 * pi() ) / 180;
		 $lng1 = ($lng1 * pi() ) / 180;

		 $lat2 = ($lat2 * pi() ) / 180;
		 $lng2 = ($lng2 * pi() ) / 180;
		 $calcLongitude = $lng2 - $lng1;
		 $calcLatitude = $lat2 - $lat1;
		 $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);  
		 $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
		 $calculatedDistance = $earthRadius * $stepTwo;

		 return round($calculatedDistance);
	}	*/
	
	function getDistance($lng1, $lat1, $lng2, $lat2) {
		// 将角度转为狐度
		$radLat1 = deg2rad($lat1); //deg2rad()函数将角度转换为弧度
		$radLat2 = deg2rad($lat2);
		$radLng1 = deg2rad($lng1);
		$radLng2 = deg2rad($lng2);
		$a = $radLat1 - $radLat2;
		$b = $radLng1 - $radLng2;
		$s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137 * 1000;
		return round($s);
	}
	/**
     * 计算某个经纬度的周围某段距离的正方形的四个点
     *
     * @param
     *            radius 地球半径 平均6371km
     * @param
     *            lng float 经度
     * @param
     *            lat float 纬度
     * @param
     *            distance float 该点所在圆的半径，该圆与此正方形内切，默认值为1千米
     * @return array 正方形的四个点的经纬度坐标
     */
    public function returnSquarePoint($lng, $lat, $distance = 10000, $radius = 6371)
    {
        $dlng = 2 * asin(sin($distance / (2 * $radius)) / cos(deg2rad($lat)));
        $dlng = rad2deg($dlng);
        
        $dlat = $distance / $radius;
        $dlat = rad2deg($dlat);
        
        return array(
            'left-top' => array(
                'lat' => $lat + $dlat,
                'lng' => $lng - $dlng
            ),
            'right-top' => array(
                'lat' => $lat + $dlat,
                'lng' => $lng + $dlng
            ),
            'left-bottom' => array(
                'lat' => $lat - $dlat,
                'lng' => $lng - $dlng
            ),
            'right-bottom' => array(
                'lat' => $lat - $dlat,
                'lng' => $lng + $dlng
            )
        );
    }
    public function getarea($distance = 1, $radius = 6371)
    {
		
        $dlng = 2 * asin(sin($distance / (2 * $radius)) / cos(deg2rad($lat)));
        $dlng = rad2deg($dlng);
        
        $dlat = $distance / $radius;
        $dlat = rad2deg($dlat);
        
        return array(
            'left-top' => array(
                'lat' => $lat + $dlat,
                'lng' => $lng - $dlng
            ),
            'right-top' => array(
                'lat' => $lat + $dlat,
                'lng' => $lng + $dlng
            ),
            'left-bottom' => array(
                'lat' => $lat - $dlat,
                'lng' => $lng - $dlng
            ),
            'right-bottom' => array(
                'lat' => $lat - $dlat,
                'lng' => $lng + $dlng
            )
        );
    }
	/*
	$array[0]就是用户上传的起点终点坐标数组 
$start = $this->returnSquarePoint($array[0]['start_lng'], $array[0]['start_lat']);
->andwhere([
                    '>',
                    'start_lat',
                    $start['right-bottom']['lat']
                ])
                    ->andWhere([
                    '<',
                    'start_lat',
                        
                    $start['left-top']['lat']
                ])
                    ->andWhere([
                    '>',
                    'start_lng',
                    $start['left-top']['lng']
                ])
                    ->andWhere([
                    '<',
                    'start_lng',
                    $start['right-bottom']['lng']
                ]);
				*/
}
