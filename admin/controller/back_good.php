<?php
//mf.leung 新增函數20180526
    /*commodity_import*/
    // 導入商品
    public function commodity_import()
    {
        header('Content-Type:text/html;charset=utf-8');
        $role_id = I('session.admin_id');
        $condition = 'user_name';
        $role_sesssion_name = Db::name('admin')->where($condition,"admin")->select();
        // echo "<pre>";
        // print_r($role_sesssion_name);
        $files = Request::instance()->file('files');
        $post = input("post.");
        $getExtension = Array("0" => "xlsx","1"=>"csv","2"=>"xls");
        $date = date('Ymd');
        $pathinfos ='./pulic/uploads/temp/'.$date;

        if($_POST)
        {
            if(empty(input('post.files')))
            {
                return ajaxReturn(['status' => 0,'msg' => '文檔不能為空']);
            }
            if($role_id == 1 && $role_sesssion_name)
            {
                if($files){
                    //search files
                    $infos = $files->move(ROOT_PATH.'public'.DS.'uploads/temp');
                    $this->file_open($infos->getSaveName(),$infos->getExtension());
                }else{
                    $info->getError();
                }

            }
            else{
                $this->ajaxReturn(['status' => 0,'msg' => '你無權限使用，請聯係管理員']);
            }
        }
        return $this->fetch();
    }

    //mf.leung 新增函數20180530
    /*file_open
    param $files 接受文件或文件路徑的參數
    param $Extension 文件的後綴名字
    文件導入
    */
    public function file_open($files,$Extension)
    {
        $url = "public/uploads/temp/".$files; 
        $lines = array_map('str_getcsv', file($url));
        $Goods = new \app\admin\model\Goods();
        $result_datas = Array();
        $result = Array();
        $GoodsLogic = new GoodsLogic();
        $headers;

        switch($Extension)
        {
            case "csv":
                $result = array("status" => "1","msg" => ".CSV資料表成功導入");
            break;
            default:
               $this->ajaxReturn(['status' => 0,'msg' => ".". $Extension . "不是有效文件，請重新輸入"]);
            break;
        }
        if (count($lines) > 0) {
            $headers = $lines[0];
        }
        for($i=1; $i<count($lines); $i++) {
            $obj = $lines[$i];
            $result_datas[] = array_combine($headers, $obj);
        }

        if(!empty(Db::table("tp_goods")->where("")->select()))
        {
            //更新數據
            $mysql_results = Db::table("tp_goods")->column("sku");

            /*options*/
            //定義新欄目
            $query_name = Db::name('goods_category')->column("name");
            $save_array = array();
            $save_array[] = $query_name;

            $parent_id_path = "0_1";                    
            $defaults = ["name"=>"glasses","mobile_name"=>"glasses","parent_id"=>"0","parent_id_path"=>$parent_id_path,"level"=>"1","sort_order"=>"1","is_show"=>"0","image"=>"","is_hot"=>"0","commission_rate"=>"1"];                    
            $condition["name"] = $defaults["name"];
            $condition["mobile_name"] = $defaults["mobile_name"];                    
            $search_classes = Db::table('tp_goods_category')->where("parent_id",0)->order("id","asc")->find();                    
            $search_son_id = Db::table('tp_goods_category')->where("parent_id",$search_classes["id"])->select();                    
            $counts = Db::table('tp_goods_category')->where("parent_id",$search_classes["id"])->count("id");
            $counts == 1 ? $limits = 1:$limits = ''.($counts -1).',1';                   
            $search = "SELECT * FROM `tp_goods_category` WHERE parent_id = ".$search_classes['id']."  ORDER BY id ASC limit  ".$limits;
            if(Db::query($search)){
                $int = Db::query($search);
                // print_r($int);
                $glasses = "glasses".($int[0]['id'] + 1);
                $glasses2 = ("glasses".($int[0]['id'] + 2));
                $mobiles_name = "glasses".($int[0]['id'] + 1);
                $mobiles_name2 = ("glasses".($int[0]['id'] + 2));
                $parent_id = $int[0]['id'];
                $parent_id2 = ($int[0]['id'] + 1);
                $parent_id_path = '0'.'_'.''.$search_classes['id'].''.'_'.''.($int[0]['id']+1).'';
                $parent_id_path2 = '0'.'_'.''.$parent_id2.''.'_'.''.($int[0]['id']+2).'';
                $datas = [["name"=>$glasses,"mobile_name"=>$mobiles_name,"parent_id"=>$search_classes['id'],"parent_id_path"=>$parent_id_path,"level"=>"2","sort_order"=>"1","is_show"=>"0","image"=>"","is_hot"=>"0","commission_rate"=>"1"],
                ["name"=>$glasses2,"mobile_name"=>$mobiles_name2,"parent_id"=>$parent_id2,"parent_id_path"=>$parent_id_path2,"level"=>"3","sort_order"=>"1","is_show"=>"0","image"=>"","is_hot"=>"0","commission_rate"=>"1"]];
                // print_r($datas);
                array_unshift($save_array[0],$datas[1]['name']);
                foreach($result_datas as $return_sku){
                    $where['sku'] = $return_sku["sku"];
                    $sql = M("goods")->where($where)->getField("goods_name");
                    if($sql){

                     }else{
                        foreach($save_array[0] as $value){
                            $where1["name"] = $value;
                            $MYSQL1 = M("goods_category")->where($where1)->getField("name");
                            //print_r($MYSQL1);
                            if(!$MYSQL1){
                               Db::name('goods_category')->insertAll($datas); 
                               /*print_r($value);*/
                            }
                        }
                    }
                }
                  
            }else{

            }
            foreach($result_datas as $brands_name){
                $datas1[] = $brands_name["brand"];
                $mysql_results = Db::table("tp_goods")->column("sku");
                if(!in_array($brands_name['sku'],$mysql_results))
                {
                    $datas3[] = $brands_name['brand'];
                }
                    
            }

            foreach($result_datas as $results)
            {
                $post_field = Db::table("tp_goods")->where("sku",$results['sku'])->field("goods_id,cat_id,extend_cat_id,brand_id")->select();
                $mysql_results = Db::table("tp_goods")->where("sku",$results['sku'])->column("sku");
                $counts = Db::table('tp_goods_category')->where("parent_id",$search_classes["id"])->count("id");
                $limits = ''.($counts-1).',1';
                $search_brands_class = "SELECT * FROM `tp_goods_category` WHERE parent_id = ".$search_classes['id']."  ORDER BY id ASC limit  ".$limits;
                if(!$mysql_results){
                    for($j = 0;$j<count(array_values(array_unique($datas1)));$j++)
                    {
                        $value = array_values(array_unique($datas1));
                        $condition2["name"] = $value[$j];
                        $search_brands = M('brand')->where($condition2)->field("id,name")->select(); 
                        if(!$search_brands)
                        {
                            $names = $value[$j];
                            $parent_cat_id = $search_classes;
                            $cat_ids1 = Db::query($search_brands_class);
                            $datas = ["name" => $names,
                             "parent_cat_id"=>$parent_cat_id['id'],
                             "cat_id"=>$cat_ids1[0]['id'],
                             "sort"=>'1'];
                            $brands = M('brand');//實例化一個對象
                            $brands->data($datas)->add();
                            $add_save[] = $datas['name'];
                            
                        }else
                        {

                        }

                    }

                    $post_parent_id = Db::query($search_brands_class); //獲得欄目父親下子iD
                    $cat_id = $post_parent_id[0]['id'];
                    $parent_cat_id = $search_classes["parent_id"];//欄目父親ID
                    $goods_name = $result["brand"]." ".$results["sku"]; //商品名稱
                    if(strlen($results["short_description"]) > 240){
                        $goods_remark = substr($results["short_description"],0,240)."....";
                    }else
                    {
                        $goods_remark = $results["short_description"];
                    }
                    $goods_remark; //商品簡單描述
                    $sku = $results["sku"];//商品SKU碼
                    $goods_sn = $results["sku"];
                    $goods_keywords = ''.$results["brand"].','.$goods_name.','.$results['supplier'].''."\n";//商品SEO關鍵詞
                    $Shop_price = "1.00";//本店售價
                    $Market_price = "1.00";//市場價
                    $goods_content = $results['short_description'];
                    $store_count = 1;
                    $condition_brands["parent_cat_id"] = $search_classes['id'];
                    $condition_brands['name'] = $results["brand"];
                    $brands_id = M("brand")->where($condition_brands)->field('id')->select();
                    $datas = [
                        "cat_id"=>$cat_id,
                        "extend_cat_id"=>$parent_cat_id,
                        "goods_sn"=>$goods_sn,
                        "goods_name"=>$goods_name,
                        "brand_id"=>$brands_id[0]["id"],
                        "keywords"=>$goods_keywords,
                        "goods_remark"=>$goods_remark,
                        "goods_content"=>$goods_content,
                        "shop_price"=>$Shop_price,
                        "market_price"=>$Market_price,
                        "is_free_shipping"=>1,
                        "sku"=>$sku];

                    $store_sku[] = $results["sku"];
                    $goods_id = $Goods->getLastInsID();
                    update_stock_log(session('admin_id'),$store_count,array('goods_id'=>$goods_id,'goods_name'=>$goods_name));//库存日志
                    M("goods")->data($datas)->add();
                    $results = ['status'=>'updates',"msg" => "數據更新成功新增了商品","datas"=>$store_sku];
                    
                }
                else{
                    $id = $post_field[0]['goods_id']; //產品ID
                    $cat_id = $post_field[0]['cat_id']; //獲得欄目父親下子iD
                    $parent_cat_id = $post_field[0]['extend_cat_id']; //欄目父親ID
                    $brands_id = $post_field[0]['brand_id']; //獲得品牌ID
                    $goods_name = $result["brand"]." ".$results["sku"]; //商品名稱
                    if(strlen($results["short_description"]) > 240){
                        $goods_remark = substr($results["short_description"],0,240)."....";
                    }else
                    {
                        $goods_remark = $results["short_description"];
                    }
                    
                    $goods_remark; //商品簡單描述
                    $sku = $results["sku"];//商品SKU碼
                    $goods_sn = $results["sku"];
                    $goods_keywords = ''.$results["brand"].','.$goods_name.','.$results['supplier'].''."\n";//商品SEO關鍵詞
                    $Shop_price = "1.00";//本店售價
                    $Market_price = "1.00";//市場價
                    $goods_content = $results['short_description'];
                    $store_count = 1;
                    $datas = [
                        "cat_id"=>$cat_id,
                        "extend_cat_id"=>$parent_cat_id,
                        "goods_sn"=>$goods_sn,
                        "goods_name"=>$goods_name,
                        "brand_id"=>$brands_id,
                        "keywords"=>$goods_keywords,
                        "goods_remark"=>$goods_remark,
                        "goods_content"=>$goods_content,
                        "shop_price"=>$Shop_price,
                        "market_price"=>$Market_price,
                        "is_free_shipping"=>1,
                        "sku"=>$sku];
                    $goods_id = $Goods->getLastInsID();
                    update_stock_log(session('admin_id'),$store_count,array('goods_id'=>$goods_id,'goods_name'=>$goods_name));//库存日志
                    Db::name("goods")->where('goods_id',$id)->update($datas);
                    $results = ['status'=>'updates',"msg" => "數據更新完成","datas"=>""];
                }
            } 
            
        }else
        {

            //default datas 三級欄目創建
            $category = Db::name('goods_category')->column("name");
            $category1 = Db::name('goods_category')->column("mobile_name");
            $parent_id_path = '0_1';
            $defaults = ["name"=>"glasses","mobile_name"=>"glasses","parent_id"=>"0","parent_id_path"=>$parent_id_path,"level"=>"1","sort_order"=>"1","is_show"=>"0","image"=>"","is_hot"=>"0","commission_rate"=>"1"];
            //檢查數數據庫沒有這條數據 return array
            if(!in_array($defaults["name"],$category) || !in_array($defaults["mobile_name"],$category1))
            {
                if(empty($category)){
                    $datas = [
                    ["name"=>"glasses","mobile_name"=>"glasses","parent_id"=>"0","parent_id_path"=>$parent_id_path,"level"=>"1","sort_order"=>"1","is_show"=>"0","image"=>"","is_hot"=>"0","commission_rate"=>"1"],
                    ["name" =>"glasses1","mobile_name"=>"glasses2","parent_id" => "1","parent_id_path" => $parent_id_path."_1","level" => "2","sort_order" => "1","is_show" => "0","image" => "","is_hot" => "0","commission_rate" => "1"],
                    ["name" =>"glasses12","mobile_name" => "glasses22","parent_id" => "2","parent_id_path" => $parent_id_path."_2","level" => "3","sort_order" => "1","is_show" => "0","image" => "","is_hot" => "0","commission_rate" => "1"]];
                    Db::name('goods_category')->insertAll($datas,true);
                    //寫入數據
                }

                
                 
                
            }
            else{
                //echo "匹配到數據";
                $count = Db::query("SELECT count(id) FROM tp_goods_category");
                if($count[0]["count(id)"] == 1)
                {
                    //如果數據篩選出總和返回列表1條數據
                    $count = 1;
                }else{

                    // 如果數據篩選列表超出一條數據
                    // 例如6條數據選擇最後1條
                    // 按照ASC的排序選中
                    $count = ($count[0]['count(id)']-1).',1';
                }
                $mysql1 = Db::query("SELECT * FROM tp_goods_category ORDER BY id ASC limit ".$count);
                $glasses = 'glasses'.($mysql1[0]["id"]+1);
                $mobile_name = 'glasses'.($mysql1[0]["id"]+1);
                $parent_id_path = ('0'.'_'.($mysql1[0]["id"] + 1));
                $datas = ["name"=>$glasses,"mobile_name"=>$mobile_name,"parent_id"=>"0","parent_id_path"=>$parent_id_path,"level"=>"1","sort_order"=>"1","is_show"=>"0","image"=>"","is_hot"=>"0","commission_rate"=>"1"];
                Db::name('goods_category')->insert($datas,true);
                //checkout mysql
                $mysql = "SELECT`name`,`id`,`mobile_name`,`parent_id`,`parent_id_path` FROM tp_goods_category WHERE
                         tp_goods_category.`name` = '".$glasses."' AND tp_goods_category.mobile_name = '".$mobile_name."'";

                $mysql = Db::query($mysql);
               //查詢是否能有二級列表
                if($mysql){
                    $count = Db::query("SELECT count(id) FROM tp_goods_category WHERE parent_id = ".$mysql[0]["parent_id"]." AND id=".$mysql[0]["id"]."");
                    if($count[0]["count(id)"] == 1)
                    {
                        //如果數據篩選出總和返回列表1條數據
                        $count = 1;
                    }else{

                        // 如果數據篩選列表超出一條數據
                        // 例如6條數據選擇最後1條
                        // 按照ASC的排序選中
                        $count = ($count[0]['count(id)']-1).',1';
                    }
                    $mysql1 = "SELECT * FROM tp_goods_category where id = ".$mysql[0]['id']." ORDER BY id ASC limit ".$count;
                    $mysql1 = Db::query($mysql1);

                    //設置二級菜單   
                    $glasses1 = 'glasses'.($mysql1[0]["id"] + 1);
                    $mobile_name1 = 'glasses'.($mysql1[0]["id"] + 1);
                    $parent_id_path1 = ('0'.'_'.''.($mysql1[0]["id"]).''.'_'.''.($mysql1[0]["id"] + 1).'');
                    $parent_id1 = ($mysql1[0]["id"]);
                    //設置三級菜單
                    $glasses2 = 'glasses'.($mysql1[0]["id"] + 2);
                    $mobile_name2 = 'glasses'.($mysql1[0]["id"] + 2);
                    $parent_id_path2 = ('0'.'_'.''.($mysql1[0]["id"] + 1).''.'_'.''.($mysql1[0]["id"] + 2).'');
                    $parent_id2 = ($mysql1[0]["id"] + 1); 

                    $datas = [
                        ["name" => $glasses1,"mobile_name" => $mobile_name1,"parent_id" => $parent_id1,"parent_id_path" => $parent_id_path1,"level" => "2","sort_order" => "1","is_show" => "0","image" => "","is_hot" => "0","commission_rate" => "1"],
                        ["name" => $glasses2,"mobile_name" => $mobile_name2,"parent_id" => $parent_id2,"parent_id_path" => $parent_id_path2,"level" => "3","sort_order" => "1","is_show" => "0","image" => "","is_hot" => "0","commission_rate" => "1"]];
                    Db::name('goods_category')->insertAll($datas,true);
                }
                /*default datas 三級欄目創建END*/

            }
            // 設置或獲得品牌
            //默認取第一個分類
            //Return brands name
            $opt_brands = Db::table('tp_brand')->find();
            $isVailte = true;
            $data_brand = [];
            $brand_update = [];
            $datas1 = [];
            $level1 =  Db::table("tp_goods_category")->where(["name"=>"glasses","mobile_name"=>"glasses"])->find();
            $level2 =  Db::table("tp_goods_category")->where("parent_id",$level1["id"])->limit(1)->find();
            if(!is_null($level1))
            {
                if(empty($opt_brands))
                {
                    foreach($result_datas as $brand_item){   
                        $data_brand[] = $brand_item['brand'];
                    }
                    
                    /*第一次過濾重複的值在用數組儲存這些值
                    由於這些數組的值過濾完之後不一樣在用
                    for在去枚舉一次儲存到數據庫*/
                    $values = array_values(array_unique($data_brand));
                    for($j=0;$j < count($values);$j++){
                        $datas = ["name" => $values[$j],
                                 "parent_cat_id"=>$level1['id'],
                                 "cat_id"=>$level2["id"],
                                 "sort"=>'1'];
                        Db::name("brand")->data($datas)->insert();
                        $datas1[] = $datas['name'];
                    }
                    $results = ['status'=>'add',"msg" => "提交商品品牌成功","datas"=>$datas1];

                }else{
                    // 定義數據
                    $level1 =  Db::table("tp_goods_category")->where(["name"=>"glasses","mobile_name"=>"glasses"])->find();
                    $level2 = Db::table("tp_goods_category")->where("parent_id",$level1['id'])->select();
                    foreach($level2 as $brand_item)
                    {   
                        
                        $brand_name = Db::table("tp_brand")->where(["parent_cat_id"=>$level1['id'],"cat_id"=>$brand_item['id']])->column("name");
                        $count = Db::table("tp_brand")->where(["parent_cat_id"=>$level1['id'],"cat_id"=>$brand_item['id']])->count("id");
                        if($count > 0){
                            foreach($result_datas as $brand_item1){   
                                $data_brand[] = $brand_item1['brand'];
                            }
                            /*更新數據庫要先過濾重複的值*/
                            foreach(array_unique($data_brand) as $values0)
                            {
                                $datas = ["name" => $values0,
                                         "parent_cat_id"=>$level1['id'],
                                         "cat_id"=>$brand_item['id'],
                                         "sort"=>'1'];
                                 $brand_update[] = $datas["name"];
                                if(!in_array($values0,$brand_name)){

                                    //若字段查找不到，那麽就新增新字段到數據庫
                                    $brands = M('brand');//實例化一個對象
                                    $brands->data($datas)->add();
                                     $add_save[] = $values0;
                                     $results = ['status'=>'insert_item',"msg" => "更新商品品牌有","data"=> $add_save];
                                }else{

                                    // 查找數據庫ID
                                    $brands_id = Db::table("tp_brand")->where("name",$datas["name"])->column('id');
                                    $brands = M('brand'); //實例化一個對象
                                    $datas['id'] = $brands_id[0];
                                    $datas['name'] = $values0;
                                    $brands->where("id=".$datas["id"])->data($datas)->save();
                                    $results = ['status'=>'updates',"msg" => "更新商品品牌成功",'data'=>$brand_update];
                                }

                            }

                        }
                    }

                }
            }
            //品牌結束END

            foreach($result_datas as $results)
            {
               $goods_name = $result["brand"]." ".$results["sku"]; //商品名稱
               if(strlen($results["short_description"]) > 240){
                    $goods_remark = substr($results["short_description"],0,240)."....";
               }else
               {
                 $goods_remark = $results["short_description"];
               }
               
               $goods_remark; //商品簡單描述
               $sku = $results["sku"];//商品SKU碼
               $level1 =  Db::table("tp_goods_category")->where(["name" => "glasses","mobile_name"=>"glasses"])->find();
               $level2 = Db::table("tp_goods_category")->where("parent_id",$level1['id'])->select();

               foreach($level2 as $level_id)
               {
                 if(Db::table("tp_goods_category")->where("parent_id",$level_id['id'])->find())
                 {

                    $level3 = Db::table("tp_goods_category")->where("parent_id",$level_id['id'])->select();
                    foreach($level3 as $level3_ids){
                       $level3_params = $level3_ids;

                    }
                    $post_brands = Db::table("tp_brand")->where(["parent_cat_id"=>$level1["id"],"cat_id"=>$level_id['id']])->select();
                    $post_brands2 = Db::table("tp_brand")->where("cat_id",$level_id['id'])->find();
                    $brand_id = Db::table("tp_brand")
                    ->where(["parent_cat_id"=>$level1["id"],"cat_id"=>$level_id['id'],"name"=>$results["brand"]])
                    ->column("id");
                    foreach($post_brands as $request_brands){
                       $post_brands1[] = $request_brands["name"];
                       // $brands_all_datas[] = $request_brands; 
                    }

                 }

                 // 獲得品牌對應的二級分類ID
                 if(!is_null($post_brands))
                 {
                    $cat_id_brand = $post_brands2;
                 }else
                 {
                    print_r($post_brands);
                 }
                    
               }
                
                $extends_id = $level1["parent_id"];//扩展分类id
                $cat_id = $cat_id_brand["cat_id"]; //分类id 
                $brand_id;
                $goods_sn = $results["sku"];
                $goods_keywords = ''.$results["brand"].','.$goods_name.','.$results['supplier'].''."\n";//商品SEO關鍵詞
                $Shop_price = "1.00";//本店售價
                $Market_price = "1.00";//市場價
                $goods_content = $results['short_description'];
                $store_count = 1;
                $datas = [
                    "cat_id"=>$cat_id,
                    "extend_cat_id"=>$extends_id,
                    "goods_sn"=>$goods_sn,
                    "goods_name"=>$goods_name,
                    "brand_id"=>$brand_id[0],
                    "keywords"=>$goods_keywords,
                    "goods_remark"=>$goods_remark,
                    "goods_content"=>$goods_content,
                    "shop_price"=>$Shop_price,
                    "market_price"=>$Market_price,
                    "is_free_shipping"=>1,
                    "sku"=>$sku];
                $goods_id = $Goods->getLastInsID();
                update_stock_log(session('admin_id'),$store_count,array('goods_id'=>$goods_id,'goods_name'=>$goods_name));//库存日志
                Db::name("goods")->data($datas)->insert();
                
            }
            $results = ['status'=>"add","msg" => "導入數據成功現跳轉商品欄目頁",'datas'=> U('Admin/Goods/goodsList')];
        }

        $this->ajaxReturn($results);
    }
?>