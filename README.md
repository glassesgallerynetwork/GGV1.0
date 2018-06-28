# GGV1.0
網站新增模塊：商品導入功能
控制器名稱：commodity_import <br>
//mf.leung 新增函數20180526 <br>
/*commodity_import*/ <br>
// 導入商品\ <br>
控制器的路徑：application\admin\controller\goods.php\commodity_import <br>
後台寫入目錄：admin\conf\menu.php\ <br>
array('name'=>'商城','child'=>array(<br>
				array('name' => '商品','child' => array(<br>
				    array('name' => '商品列表', 'act'=>'goodsList', 'op'=>'Goods'),<br>
				    array('name' => '淘宝导入', 'act'=>'index', 'op'=>'Import'),<br>
					array('name' => '商品分类', 'act'=>'categoryList', 'op'=>'Goods'),<br>
					array('name' => '库存日志', 'act'=>'stock_list', 'op'=>'Goods'),<br>
					array('name' => '商品模型', 'act'=>'goodsTypeList', 'op'=>'Goods'),<br>
					array('name' => '商品规格', 'act' =>'specList', 'op' => 'Goods'),<br>
					array('name' => '品牌列表', 'act'=>'brandList', 'op'=>'Goods'),<br>
					array('name' => '商品属性', 'act'=>'goodsAttributeList', 'op'=>'Goods'),<br>
					array('name' => '评论列表', 'act'=>'index', 'op'=>'Comment'),<br>
					array('name' => '商品咨询', 'act'=>'ask_list', 'op'=>'Comment'),<br>
					array('name' => '商品导入', 'act'=>'commodity_import','op'=>'Goods'),<br>
			))<br>
 該版本是最新的版本<br>
 請直接把goods.php文件敺替就就可了。如有不明白，請聯絡MF.leung<br>
 Email: MF.LEUNG@glassesgallery.com<br>
 程式純自己思維原創，請勿轉發，尊重別人版權<br>
 
                                                                                                                        ---MF.LEUNG
