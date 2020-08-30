<?php

$app->post('/save/', function ($request, $response, $args) {
    $data = $request->getParsedBody();
    if (empty($data['jsondata'])) {
        return $response->withJson(['status' => 'Error', 'message' => 'Gagal upload excel data'],200);
    }
    if (empty($data['nama'])) {
        return $response->withJson(['status' => 'Error', 'message' => 'Gagal upload excel data'],200);
    }
    // ini_set('memory_limit','-1');
    ini_set('max_execution_time', 300); //300 seconds = 5 minutes
    $jsonfinal['fields'][0]['name'] = "Nama";
    $jsonfinal['fields'][0]['format'] = "";
    $jsonfinal['fields'][0]['type'] = "";
    $jsonfinal['fields'][1]['name'] = "Tanggal";
    $jsonfinal['fields'][1]['format'] = "";
    $jsonfinal['fields'][1]['type'] = "timestamp";
    $jsonfinal['fields'][2]['name'] = "Latitude";
    $jsonfinal['fields'][2]['format'] = "";
    $jsonfinal['fields'][2]['type'] = "real";
    $jsonfinal['fields'][3]['name'] = "Longitude";
    $jsonfinal['fields'][3]['format'] = "";
    $jsonfinal['fields'][3]['type'] = "real";
    $jsonfinal['fields'][4]['name'] = "Amp";
    $jsonfinal['fields'][4]['format'] = "";
    $jsonfinal['fields'][4]['type'] = "";
    $jsonfinal['fields'][5]['name'] = "Teg";
    $jsonfinal['fields'][5]['format'] = "";
    $jsonfinal['fields'][5]['type'] = "";
    $jsonfinal['fields'][6]['name'] = "MW";
    $jsonfinal['fields'][6]['format'] = "";
    $jsonfinal['fields'][6]['type'] = "real";
    $jsonfinal['fields'][7]['name'] = "Cuaca";
    $jsonfinal['fields'][7]['format'] = "";
    $jsonfinal['fields'][7]['type'] = "";
    
    $filename = "../src/file/" . time() . ".json";
    $jsonhasil = json_decode($data['jsondata'],true);
    $jsonfinal['rows'] = $jsonhasil;
    // var_dump($json);
    // $jsonfinal = [];
    // $j = 0 ;
    
    // $myfile1 = fopen("./files/1597630910.json", "r") or die("Unable to open file!");
    // $myfilee = fread($myfile1,filesize("./files/1597630910.json"));
    
    // $myfile = json_decode($myfilee,true);
    
    // for ($i=12; $i < count($myfile); $i+8) { 
    //     if (count($myfile[$i][3] > 10)) {
    //         for ($j=5; $j < count($myfile[$i]); $j++) { 
    //             // $jsonfinal['rows'][]            
    //         }
    //     }
    // }
    // var_dump($jsonfinal);
    // fclose($myfile1);
    $myfile = fopen("../".$filename, "w") or die("Unable to open file!");
    fwrite($myfile, json_encode($jsonfinal));
    fclose($myfile);
    $nama = $data['nama'];
    $sql = "INSERT INTO data_excel (nama, path)
                VALUES ('$nama', '$filename')";
    $est = $this->db->prepare($sql);
    $est->execute();

    return $response->withJson(['status' => 'Success', 'message' => 'Berhasil upload excel data'],200);
});

$app->get('/list/data/', function ($request, $response) {
    $sql = "SELECT * FROM data_excel";
    $est = $this->db->prepare($sql);
    $est->execute();
    return $response->withJson(['status' => 'Success', 'message' => 'Berhasil Mendapatkan Data', 'data' => $est->fetchAll()],200);
});

$app->post('/admin/login/', function ($request, $response) {
    $data = $request->getParsedBody();
    $email = $data['email'];
    $password = $data['password'];
    $sql = "SELECT * FROM admin
            WHERE email = '$email' AND password = '$password'";
    $est = $this->db->prepare($sql);
    $est->execute();
    $admin = $est->fetch();
    if(empty($admin)){
        return $response->withJson(['status' => 'Error', 'message' => 'Gagal Login Atau Email Password Tidak Ditemukan'],400);
    }
    return $response->withJson(['status' => 'Success', 'message' => 'Berhasil Login'],200);
});


$app->get('/data/{id}', function ($request, $response, $args) {
    $data = $args['id'];
    $sql = "SELECT * FROM data_excel
            WHERE id = '$data'";
    $est = $this->db->prepare($sql);
    $est->execute();
    $path = $est->fetch();
    // var_dump($path);
    $myfile1 = fopen("../".$path['path'] , "r") or die("Unable to open file!");
    $myfilee = fread($myfile1,filesize("../".$path['path']));
    $myfile = json_decode($myfilee,true);
    return $response->withJson(($myfile),200);
});