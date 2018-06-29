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