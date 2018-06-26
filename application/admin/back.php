if(!empty(Db::table("tp_postcsv")->where("")->select()))
        {
            //若本地文件有增加的條數，就會獲取最新的條數導入到數據庫
             $duplicateSKU = Array();
             $isValid = true;
            if($lines[0])
            {
                /*刪除頭部的字符串*/
                unset($lines[0]);
                 /*枚舉本地文件SKU碼*/
                 foreach($lines as $products)
                 {
                    $result = Db::table("tp_postcsv")->where("sku",$products[0])->count();
                    //指向某個位置某個值
                    $result0 = Db::table("tp_postcsv")->where("sku",$products[0])->column('sku');
                    /*當前數據表是不是已存在SKU
                        echo 1 存在SKU  echo 0 不存在SKU
                    */
                    if($result > 0) {
                        $duplicateSKU[] = $result0;
                        $isValid = false;
                    }

                 }
            }

            if($isValid)
            {
                foreach($result_datas as $products1)
                {
                    $datas = [
                     "sku"=>$products1["sku"],
                     "model_number"=>$products1["model_number"],
                     "color_code"=>$products1["color_code"],
                     "supplier"=>$products1["supplier"],
                     "inventory_location"=>$products1["inventory_location"],
                     "gender"=>$products1["gender"],
                     "age_group"=>$products1["age_group"],
                     "eyewear_type"=>$products1["eyewear_type"],
                     "frame_type"=>$products1["frame_type"],
                     "color"=>$products1["color"],
                     "short_description"=>$products1["short_description"],
                     "style"=>$products1["style"],
                     "shape"=>$products1["shape"],
                     "front_material"=>$products1["front_material"],
                     "temple_material"=>$products1["temple_material"],
                     "rrp"=>$products1["rrp"],
                     "price"=>$products1["price"],
                     "front_color"=>$products1["front_color"],
                     "temple_color"=>$products1["temple_color"],
                     "mirror"=>$products1["mirror"],
                     "gradient"=>$products1["gradient"],
                     "polarized"=>$products1["polarized"],
                     "customized_plano"=>$products1["customized_plano"],
                     "prescription_available"=>$products1["prescription_available"],
                     "nose_pads_adjustable"=>$products1["nose_pads_adjustable"],
                     "lens_width"=>$products1["lens_width"],
                     "bridge_width"=>$products1["bridge_width"],
                     "temple_length"=>$products1["temple_length"],
                     "lens_height"=>$products1["lens_height"],
                     "fitting_height"=>$products1["fitting_height"],
                     "frame_width"=>$products1["frame_width"],
                     "effective_diameter"=>$products1["effective_diameter"],
                     "surface_curvature"=>$products1["surface_curvature"],
                     "trylive_id_3d"=>$products1["trylive_id_3d"],
                     "active"=>$products1["active"],
                     "stock"=>$products1["stock"]];
                    Db::name("postcsv")->insertAll($datas);
                    $results = ["status"=>"updates","msg"=>"資料更新成功","datas" => $datas];  
                }
                     
            }else{
                $results = ["status"=>"Error","msg"=>"SKU碼已經存在數據表裏","datas" => $duplicateSKU];
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
                //寫入數據
                 $datas = [
                    ["name"=>"glasses","mobile_name"=>"glasses","parent_id"=>"0","parent_id_path"=>$parent_id_path,"level"=>"1","sort_order"=>"1","is_show"=>"0","image"=>"","is_hot"=>"0","commission_rate"=>"1"],
                    ["name" =>"glasses1","mobile_name"=>"glasses2","parent_id" => "1","parent_id_path" => $parent_id_path."_1","level" => "2","sort_order" => "1","is_show" => "0","image" => "","is_hot" => "0","commission_rate" => "1"],
                    ["name" =>"glasses12","mobile_name" => "glasses22","parent_id" => "2","parent_id_path" => $parent_id_path."_2","level" => "3","sort_order" => "1","is_show" => "0","image" => "","is_hot" => "0","commission_rate" => "1"]
                ];
                Db::name('goods_category')->insertAll($datas,true);
            }
            else{

                //checkout mysql
                $mysql = "SELECT`name`,`id`,`mobile_name`,`parent_id_path` FROM tp_goods_category WHERE
                         tp_goods_category.`name` = '".$defaults["name"]."' AND tp_goods_category.mobile_name = '".$defaults["mobile_name"]."'";

                $mysql = Db::query($mysql);
                //查詢是否能有二級列表
                if($mysql){
                    $count = Db::query("SELECT count(id) FROM tp_goods_category WHERE parent_id =".$mysql[0]["id"]."");
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

                    $mysql1 = "SELECT * FROM tp_goods_category WHERE parent_id = ".$mysql[0]["id"]." AND level = 2 order by id asc limit ".$count;
                    //設置二級菜單，若母ID下已有子ID 繼續添加
                    $mysql1 = Db::query($mysql1);         
                    $glasses = "glasses".($mysql1[0]["id"] + 1); //glasses1
                    $glasses1 = "glasses".($mysql1[0]["id"]+ 1)."1"; //glasses21
                    
                    //三級分類
                    $glasses2 = "glasses".($mysql1[0]["id"] + 2); //glasses1
                    $glasses21 = "glasses".($mysql1[0]["id"]+ 2)."1"; //glasses21
                    
                    $mysql3 = Db::name("goods_category")->where("id",$mysql1[0]["id"])->find();
                    // Return parent_path_id id
                    //母ID標識(PARENT_ID)
                    //兒子ID(childs ID)
                    $parent_id = $mysql3['parent_id'];

                    $parent_id_path = $mysql[0]['parent_id_path'].'_'.($mysql1[0]["id"]+1);
                    $parent_id_path2 = $mysql1[0]['parent_id_path']."_".$mysql3['id'];
                    $datas = [
                        ["name" => $glasses,"mobile_name" => $glasses1,"parent_id" => $parent_id,"parent_id_path" => $parent_id_path,"level" => "2","sort_order" => "1","is_show" => "0","image" => "","is_hot" => "0","commission_rate" => "1"],
                        ["name" => $glasses2,"mobile_name" => $glasses21,"parent_id" => $parent_id+1,"parent_id_path" => $parent_id_path2,"level" => "3","sort_order" => "1","is_show" => "0","image" => "","is_hot" => "0","commission_rate" => "1"]
                    ];
                    Db::name('goods_category')->insertAll($datas,true);
                }
                /*default datas 三級欄目創建END*/


                // $names = Db::query($mysql);
                // $results = ['status' => 0,'msg' =>$names[0]['name']."頂級分類已經存在" ,'names' => $names[0]['name'],'mobile_names'=>$names[0]['mobile_name']];

            }
            foreach($result_datas as $results)
            {

               $names = $result["brand"]." ".$results["sku"]; //商品名稱
               $short_description;
               dump($results["short_description"]);
               return;

               $datas = ["sku"=>$results["sku"],
                         "model_number"=>$results["model_number"],
                         "color_code"=>$results["color_code"],
                         "supplier"=>$results["supplier"],
                         "inventory_location"=>$results["inventory_location"],
                         "gender"=>$results["gender"],
                         "age_group"=>$results["age_group"],
                         "eyewear_type"=>$results["eyewear_type"],
                         "frame_type"=>$results["frame_type"],
                         "color"=>$results["color"],
                         "short_description"=>$results["short_description"],
                         "style"=>$results["style"],
                         "shape"=>$results["shape"],
                         "front_material"=>$results["front_material"],
                         "temple_material"=>$results["temple_material"],
                         "rrp"=>$results["rrp"],
                         "price"=>$results["price"],
                         "front_color"=>$results["front_color"],
                         "temple_color"=>$results["temple_color"],
                         "mirror"=>$results["mirror"],
                         "gradient"=>$results["gradient"],
                         "polarized"=>$results["polarized"],
                         "customized_plano"=>$results["customized_plano"],
                         "prescription_available"=>$results["prescription_available"],
                         "nose_pads_adjustable"=>$results["nose_pads_adjustable"],
                         "lens_width"=>$results["lens_width"],
                         "bridge_width"=>$results["bridge_width"],
                         "temple_length"=>$results["temple_length"],
                         "lens_height"=>$results["lens_height"],
                         "fitting_height"=>$results["fitting_height"],
                         "frame_width"=>$results["frame_width"],
                         "effective_diameter"=>$results["effective_diameter"],
                         "surface_curvature"=>$results["surface_curvature"],
                         "trylive_id_3d"=>$results["trylive_id_3d"],
                         "active"=>$results["active"],
                         "stock"=>$results["stock"]];

                print_r($datas);
                return;
                Db::name("postcsv")
                ->data($datas)
                ->insert();
            }
            $results = ["status"=>"add","msg"=>"提交資料成功"]; 
        }
        $this->ajaxReturn($results);










        foreach($result_datas as $results)
            {

               $goods_name = $result["brand"]." ".$results["sku"]; //商品名稱
               
               if(strlen($results["short_description"]) > 255){
                    $goods_remark = substr($results["short_description"],0,255)."....";
               }else
               {
                 $goods_remark = $results["short_description"];
               }
               
               $goods_remark; //商品簡單描述
               $sku = $results["sku"];

               $level1 =  Db::table("tp_goods_category")->where(["name" => "glasses","mobile_name"=>"glasses"])->find();
               $level2 = Db::table("tp_goods_category")->where("parent_id",$level1['id'])->select();
               foreach($level2 as $level_id)
               {
                 if(Db::table("tp_goods_category")->where("parent_id",$level_id['id'])->find())
                 {

                    $level3 = Db::table("tp_goods_category")->where("parent_id",$level_id['id'])->find();
                    print_r($level3);
                 }else
                 {
                    echo false;
                 }
               }
               $extends_id = $level1["parent_id"];//扩展分类id
               $cat_id = $level3['id'];//分类id
               
               $datas = ["sku"=>$results["sku"],
                         "model_number"=>$results["model_number"],
                         "color_code"=>$results["color_code"],
                         "supplier"=>$results["supplier"],
                         "inventory_location"=>$results["inventory_location"],
                         "gender"=>$results["gender"],
                         "age_group"=>$results["age_group"],
                         "eyewear_type"=>$results["eyewear_type"],
                         "frame_type"=>$results["frame_type"],
                         "color"=>$results["color"],
                         "short_description"=>$results["short_description"],
                         "style"=>$results["style"],
                         "shape"=>$results["shape"],
                         "front_material"=>$results["front_material"],
                         "temple_material"=>$results["temple_material"],
                         "rrp"=>$results["rrp"],
                         "price"=>$results["price"],
                         "front_color"=>$results["front_color"],
                         "temple_color"=>$results["temple_color"],
                         "mirror"=>$results["mirror"],
                         "gradient"=>$results["gradient"],
                         "polarized"=>$results["polarized"],
                         "customized_plano"=>$results["customized_plano"],
                         "prescription_available"=>$results["prescription_available"],
                         "nose_pads_adjustable"=>$results["nose_pads_adjustable"],
                         "lens_width"=>$results["lens_width"],
                         "bridge_width"=>$results["bridge_width"],
                         "temple_length"=>$results["temple_length"],
                         "lens_height"=>$results["lens_height"],
                         "fitting_height"=>$results["fitting_height"],
                         "frame_width"=>$results["frame_width"],
                         "effective_diameter"=>$results["effective_diameter"],
                         "surface_curvature"=>$results["surface_curvature"],
                         "trylive_id_3d"=>$results["trylive_id_3d"],
                         "active"=>$results["active"],
                         "stock"=>$results["stock"]];
            }
            return;
            $results = ["status"=>"add","msg"=>"提交資料成功"]; 