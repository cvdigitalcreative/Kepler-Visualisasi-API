<?php

$app->post('/save/', function ($request, $response, $args) {
    $data = $request->getParsedBody();
    if (empty($data['jsondata']) || empty($data['nama']) || empty($data['bulan']) || empty($data['gardu']) || empty($data['tahun'])) {
        return $response->withJson(['status' => 'Error', 'message' => 'Gagal upload excel data'], 200);
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
    $jsonfinal['fields'][8]['name'] = "Kecamatan";
    $jsonfinal['fields'][8]['format'] = "";
    $jsonfinal['fields'][8]['type'] = "";

    $filename = "./files/" . time() . ".json";
    $jsonhasil = json_decode($data['jsondata'], true);
    $jsonfinal['rows'] = $jsonhasil;
    $myfile = fopen($filename, "w") or die("Unable to open file!");
    fwrite($myfile, json_encode($jsonfinal));
    fclose($myfile);
    $nama = $data['nama'];
    $gardu = $data['gardu'];
    $bulan = $data['bulan'];
    $tahun = $data['tahun'];
    $sql = "INSERT INTO data_excel (nama, path, gardu, bulan, tahun)
                VALUES ('$nama', '$filename', '$gardu', '$bulan', '$tahun')";
    $est = $this->db->prepare($sql);
    $est->execute();

    return $response->withJson(['status' => 'Success', 'message' => 'Berhasil upload excel data'], 200);
});

$app->post('/upload/html/{id}', function ($request, $response, $args) {
    $data = $args['id'];
    $uploadedFiles = $request->getUploadedFiles();
    $uploadedFile = $uploadedFiles['file'];
    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        if ($extension != "html" && $extension != "HTML") {
            return $response->withJson(['status' => 'Error', 'message' => 'Format File Harus HTML'], 200);
        }
        $sql = "SELECT * FROM data_excel
        WHERE id = '$data'";
        $est = $this->db->prepare($sql);
        $est->execute();
        $path = $est->fetch();
        if (!empty($path['html_path'])) {
            unlink($path['html_path']);
        }
        $filename = md5($uploadedFile->getClientFilename()) . time() . $uploadedFile->getClientFilename();
        $directory = "./files/";
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
        $path_name = "./files/" . $filename;
        $sql = "UPDATE data_excel
            SET html_path = '$path_name'
            WHERE id = '$data' ";
        $est = $this->db->prepare($sql);

        if ($est->execute()) {
            return $response->withJson(['status' => 'Success', 'message' => 'Berhasil Upload Data'], 200);
        }
    }
    return $response->withJson(['status' => 'Error', 'message' => 'Gagal Upload Data'], 400);
});

$app->get('/list/data/html/', function ($request, $response) {
    $sql = "SELECT * FROM data_excel";
    $est = $this->db->prepare($sql);
    $est->execute();
    $data = $est->fetchAll();
    $final = [];
    $j = 0;
    for ($i = 0; $i < count($data); $i++) {
        if (!empty($data[$i]['html_path'])) {
            $final[$j] = $data[$i];
            $j++;
        }
    }
    return $response->withJson(['status' => 'Success', 'message' => 'Berhasil Mendapatkan Data', 'data' => $final], 200);
});

$app->delete('/delete/data/{id}', function ($request, $response, $args) {
    $id = $args['id'];
    $sql = "SELECT * FROM data_excel
    WHERE id = '$id'";
    $est = $this->db->prepare($sql);
    $est->execute();
    $path = $est->fetch();
    unlink($path['html_path']);
    unlink($path['path']);
    $sql = "DELETE FROM data_excel
            WHERE id = '$id'";
    $est = $this->db->prepare($sql);
    if ($est->execute()) {
        return $response->withJson(['status' => 'Success', 'message' => 'Berhasil Menghapus Data'], 200);
    }
    return $response->withJson(['status' => 'Error', 'message' => 'Gagal Menghapus Data'], 400);
});

$app->get('/data/', function ($request, $response) {
    $gardu = $request->getQueryParams('gardu');
    $bulan = $request->getQueryParams('bulan');
    $tahun = $request->getQueryParams('tahun');
    $sql = "SELECT data_excel.id, data_excel.nama, data_excel.path, data_excel.html_path, gardu.gardu, bulan.bulan, tahun.tahun FROM data_excel
        INNER JOIN gardu ON gardu.id = data_excel.gardu
        INNER JOIN bulan ON bulan.id = data_excel.bulan
        INNER JOIN tahun ON tahun.id = data_excel.tahun ";

    $gardu = $gardu['gardu'];
    $bulan = $bulan['bulan'];
    $tahun = $tahun['tahun'];

    if (!empty($gardu) || !empty($bulan) || !empty($tahun)) {
        $sql = $sql . "WHERE ";
    }

    if (!empty($gardu)) {
        $sql = $sql . "data_excel.gardu = '$gardu' ";
    }
    if (!empty($gardu) && !empty($bulan)) {
        $sql = $sql . "AND ";
    }

    if (!empty($bulan)) {
        $sql = $sql . "data_excel.bulan = '$bulan' ";
    }

    if ((!empty($gardu) || !empty($bulan)) && !empty($tahun)) {
        $sql = $sql . "AND ";
    }

    if (!empty($tahun)) {
        $sql = $sql . "data_excel.tahun = '$tahun' ";
    }

    
    $est = $this->db->prepare($sql);
    $est->execute();
    return $response->withJson(['status' => 'Success', 'message' => 'Berhasil Mendapatkan Data', 'data' => $est->fetchAll()], 200);
});

$app->get('/list/data/', function ($request, $response) {

    $sql = "SELECT * FROM data_excel";

    $est = $this->db->prepare($sql);
    $est->execute();
    return $response->withJson(['status' => 'Success', 'message' => 'Berhasil Mendapatkan Data', 'data' => $est->fetchAll()], 200);
});

$app->get('/data/bulan/', function ($request, $response) {

    $sql = "SELECT * FROM bulan";

    $est = $this->db->prepare($sql);
    $est->execute();
    return $response->withJson(['status' => 'Success', 'message' => 'Berhasil Mendapatkan Data', 'data' => $est->fetchAll()], 200);
});

$app->get('/data/gardu/', function ($request, $response) {

    $sql = "SELECT * FROM gardu";

    $est = $this->db->prepare($sql);
    $est->execute();
    return $response->withJson(['status' => 'Success', 'message' => 'Berhasil Mendapatkan Data', 'data' => $est->fetchAll()], 200);
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
    if (empty($admin)) {
        return $response->withJson(['status' => 'Error', 'message' => 'Gagal Login Atau Email Password Tidak Ditemukan'], 400);
    }
    return $response->withJson(['status' => 'Success', 'message' => 'Berhasil Login'], 200);
});

$app->get('/data/html/{id}', function ($request, $response, $args) {
    $data = $args['id'];
    $sql = "SELECT * FROM data_excel
            WHERE id = '$data'";
    $est = $this->db->prepare($sql);
    $est->execute();
    $path = $est->fetch();
    return $response->withJson(['status' => 'Success', 'message' => 'Berhasil Mendapatkan Data', 'data' => $path['html_path']], 200);
});

$app->get('/data/{id}', function ($request, $response, $args) {
    $data = $args['id'];
    $sql = "SELECT * FROM data_excel
            WHERE id = '$data'";
    $est = $this->db->prepare($sql);
    $est->execute();
    $path = $est->fetch();
    $myfile1 = fopen($path['path'], "r") or die("Unable to open file!");
    $myfilee = fread($myfile1, filesize($path['path']));
    $myfile = json_decode($myfilee, true);
    return $response->withJson(($myfile), 200);
});
