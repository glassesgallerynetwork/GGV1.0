<?php

namespace app\common\logic;
use think\Model;
use think\Db;

class Color extends Model
{
	

	//镜片颜色筛选
	public function color($res){
		$arr=Array();
		switch ($res) {
			case 'brown_transitions':
				$url='lens-color/icon-tsc-brown.png';
				$arr=array('url'=>$url);
				break;

			case 'grey_transitions':
				$url='lens-color/icon-tsc-grey.png';
				$arr=array('url'=>$url);
				break;

			case 'aston':
				$url='lens-color/icon-mc-aston.png';
				$arr=array('url'=>$url);
				break;

			case 'dona':
				$url='lens-color/icon-mc-dona.png';
				$arr=array('url'=>$url);
				break;

			case 'ocean_flash':
				$url='lens-color/icon-mc-ocean-flash.png';
				$arr=array('url'=>$url);
				break;
			
			case 'pine_green':
				$url='lens-color/icon-mc-pine-green.png';
				$arr=array('url'=>$url);
				break;

			case 'pink_panther':
				$url='lens-color/icon-mc-pink-panther.png';
				$arr=array('url'=>$url);
				break;

			case 'tank':
				$url='lens-color/icon-mc-tank.png';
				$arr=array('url'=>$url);
				break;

			case 'brown':
				$url='lens-color/icon-ft-brown.png';
				$arr=array('url'=>$url);
				break;

			case 'green':
				$url='lens-color/icon-ft-green.png';
				$arr=array('url'=>$url);
				break;

			case 'grey':
				$url='lens-color/icon-ft-grey.png';
				$arr=array('url'=>$url);
				break;

			case 'gradient_blue':
				$url='lens-color/icon-gt-blue.png';
				$arr=array('url'=>$url);
				break;

			case 'gradient_brown':
				$url='lens-color/icon-gt-brown.png';
				$arr=array('url'=>$url);
				break;

			case 'gradient_green':
				$url='lens-color/icon-gt-green.png';
				$arr=array('url'=>$url);
				break;

			case 'gradient_grey':
				$url='lens-color/icon-gt-grey.png';
				$arr=array('url'=>$url);
				break;

			case 'gradient_violet':
				$url='lens-color/icon-gt-violet.png';
				$arr=array('url'=>$url);
				break;

			default:
				$arr="";
				break;
		}
		return $arr;
	}


	//镜片颜色联动
	public function overlay_image($data){
		switch ($data) {
			case 'lens-color/icon-tsc-brown.png':
				$overlay='-brown.png';
				break;

			case 'lens-color/icon-tsc-grey.png':
				$overlay='-grey.png';
				break;
			
			case 'lens-color/icon-mc-aston.png':
				$overlay='-aston.jpg';
				break;

			case 'lens-color/icon-mc-dona.png':
				$overlay='-dona.jpg';
				break;

			case 'lens-color/icon-mc-pine-green.png':
				$overlay='-pinegreen.jpg';
				break;

			case 'lens-color/icon-mc-pink-panther.png':
				$overlay='-pinkpanther.jpg';
				break;

			case 'lens-color/icon-mc-tank.png':
				$overlay='-tank.jpg';
				break;

			case 'lens-color/icon-ft-brown.png':
				$overlay='-brown.jpg';
				break;

			case 'lens-color/icon-ft-green.png':
				$overlay='-green.jpg';
				break;

			case 'lens-color/icon-ft-grey.png':
				$overlay='-grey.jpg';
				break;

			case 'lens-color/icon-gt-blue.png':
				$overlay='-gradientblue.jpg';
				break;

			case 'lens-color/icon-gt-brown.png':
				$overlay='-gradientbrown.jpg';
				break;

			case 'lens-color/icon-gt-green.png':
				$overlay='-gradientgreen.jpg';
				break;

			case 'lens-color/icon-gt-grey.png':
				$overlay='-gradientgrey.jpg';
				break;

			case 'lens-color/icon-gt-violet.png':
				$overlay='-gradientviolet.jpg';
				break;

			default:
				# code...
				break;
		}
		return $overlay;
	}
}