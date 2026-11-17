<?php
if(!class_exists('DBConnection')){
	require_once('../config.php');
	require_once('DBConnection.php');
}
class SystemSettings extends DBConnection{
	public function __construct(){
		parent::__construct();
	}
	function __destruct(){
	}
	function check_connection(){
		return($this->conn);
	}
	function load_system_info(){
		
			$sql = "SELECT * FROM system_info";
			$qry = $this->conn->query($sql);
				while($row = $qry->fetch_assoc()){
					$_SESSION['system_info'][$row['meta_field']] = $row['meta_value'];
				}
		
	}
	function update_system_info(){
		$sql = "SELECT * FROM system_info";
		$qry = $this->conn->query($sql);
			while($row = $qry->fetch_assoc()){
				if(isset($_SESSION['system_info'][$row['meta_field']]))unset($_SESSION['system_info'][$row['meta_field']]);
				$_SESSION['system_info'][$row['meta_field']] = $row['meta_value'];
			}
		return true;
	}
	function update_settings_info(){
		$data = "";
		$resp = array();
		
		
		
		try {
			
			foreach ($_POST as $key => $value) {
				if(!in_array($key,array("content"))){
					if(isset($_SESSION['system_info'][$key])){
						$value = str_replace("'", "&apos;", $value);
						$stmt = $this->conn->prepare("UPDATE system_info SET meta_value = ? WHERE meta_field = ?");
						if($stmt){
							$stmt->bind_param('ss', $value, $key);
							if(!$stmt->execute()){
								throw new Exception("Failed to update $key: " . $this->conn->error);
							}
							$stmt->close();
						}else{
							throw new Exception("Failed to prepare UPDATE statement: " . $this->conn->error);
						}
					}else{
						$stmt2 = $this->conn->prepare("INSERT INTO system_info SET meta_value = ?, meta_field = ?");
						if($stmt2){
							$stmt2->bind_param('ss', $value, $key);
							if(!$stmt2->execute()){
								throw new Exception("Failed to insert $key: " . $this->conn->error);
							}
							$stmt2->close();
						}else{
							throw new Exception("Failed to prepare INSERT statement: " . $this->conn->error);
						}
					}
				}
			}
			
			
			if(isset($_POST['content'])){
				foreach($_POST['content'] as $k => $v){
					$v = addslashes(htmlspecialchars($v));
					$file_path = base_app . $k . '.html';
					file_put_contents($file_path, $v);
				}
			}
			
			
			if(isset($_FILES['img']) && $_FILES['img']['error'] == 0){
				$upload_dir = base_app . "uploads";
				if(!is_dir($upload_dir)){
					mkdir($upload_dir, 0777, true);
				}
				
				$file_ext = strtolower(pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION));
				if(in_array($file_ext, ['jpg', 'jpeg', 'png'])){
					$fname = "uploads/logo.png";
					$full_path = base_app . $fname;
					
					if(file_exists($full_path)){
						unlink($full_path);
					}
					
					if(move_uploaded_file($_FILES['img']['tmp_name'], $full_path)){
						$logo_path = $fname . '?v=' . time();
						if(isset($_SESSION['system_info']['logo'])){
							$stmt_logo = $this->conn->prepare("UPDATE system_info SET meta_value = ? WHERE meta_field = 'logo'");
							if($stmt_logo){
								$stmt_logo->bind_param('s', $logo_path);
								$stmt_logo->execute();
								$stmt_logo->close();
							}
						}else{
							$stmt_logo_ins = $this->conn->prepare("INSERT INTO system_info SET meta_value = ?, meta_field = 'logo'");
							if($stmt_logo_ins){
								$stmt_logo_ins->bind_param('s', $logo_path);
								$stmt_logo_ins->execute();
								$stmt_logo_ins->close();
							}
						}
					}
				}
			}
			
			
			if(isset($_FILES['cover']) && $_FILES['cover']['error'] == 0){
				$upload_dir = base_app . "uploads";
				if(!is_dir($upload_dir)){
					mkdir($upload_dir, 0777, true);
				}
				
				$file_ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
				if(in_array($file_ext, ['jpg', 'jpeg', 'png'])){
					$fname = "uploads/cover.png";
					$full_path = base_app . $fname;
					
					if(file_exists($full_path)){
						unlink($full_path);
					}
					
					if(move_uploaded_file($_FILES['cover']['tmp_name'], $full_path)){
						$cover_path = $fname . '?v=' . time();
						if(isset($_SESSION['system_info']['cover'])){
							$stmt_cover = $this->conn->prepare("UPDATE system_info SET meta_value = ? WHERE meta_field = 'cover'");
							if($stmt_cover){
								$stmt_cover->bind_param('s', $cover_path);
								$stmt_cover->execute();
								$stmt_cover->close();
							}
						}else{
							$stmt_cover_ins = $this->conn->prepare("INSERT INTO system_info SET meta_value = ?, meta_field = 'cover'");
							if($stmt_cover_ins){
								$stmt_cover_ins->bind_param('s', $cover_path);
								$stmt_cover_ins->execute();
								$stmt_cover_ins->close();
							}
						}
					}
				}
			}
			
			
			if(isset($_FILES['banners']) && count($_FILES['banners']['tmp_name']) > 0){
				$banner_path = base_app . "uploads/banner";
				if(!is_dir($banner_path)){
					mkdir($banner_path, 0777, true);
				}
				
				foreach($_FILES['banners']['tmp_name'] as $k => $v){
					if(!empty($_FILES['banners']['tmp_name'][$k]) && $_FILES['banners']['error'][$k] == 0){
						$file_ext = strtolower(pathinfo($_FILES['banners']['name'][$k], PATHINFO_EXTENSION));
						if(in_array($file_ext, ['jpg', 'jpeg', 'png'])){
							$filename = uniqid() . '_' . $_FILES['banners']['name'][$k];
							$full_path = $banner_path . '/' . $filename;
							move_uploaded_file($_FILES['banners']['tmp_name'][$k], $full_path);
						}
					}
				}
			}
			
		} catch (Exception $e) {
			$resp['status'] = 'failed';
			$resp['msg'] = 'Error: ' . $e->getMessage();
			error_log("SystemSettings Error: " . $e->getMessage());
			return json_encode($resp);
		} catch (Error $e) {
			$resp['status'] = 'failed';
			$resp['msg'] = 'PHP Error: ' . $e->getMessage();
			error_log("SystemSettings PHP Error: " . $e->getMessage());
			return json_encode($resp);
		}
		
		$update = $this->update_system_info();
		$flash = $this->set_flashdata('success','System Info Successfully Updated.');
		if($update && $flash){
			$resp['status'] = 'success';
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = 'Failed to update session or set flash message';
		}
		return json_encode($resp);
	}
	function set_userdata($field='',$value=''){
		if(!empty($field) && !empty($value)){
			$_SESSION['userdata'][$field]= $value;
		}
	}
	function userdata($field = ''){
		if(!empty($field)){
			if(isset($_SESSION['userdata'][$field]))
				return $_SESSION['userdata'][$field];
			else
				return null;
		}else{
			return false;
		}
	}
	function set_flashdata($flash='',$value=''){
		if(!empty($flash) && !empty($value)){
			$_SESSION['flashdata'][$flash]= $value;
		return true;
		}
	}
	function chk_flashdata($flash = ''){
		if(isset($_SESSION['flashdata'][$flash])){
			return true;
		}else{
			return false;
		}
	}
	function flashdata($flash = ''){
		if(!empty($flash)){
			$_tmp = $_SESSION['flashdata'][$flash];
			unset($_SESSION['flashdata']);
			return $_tmp;
		}else{
			return false;
		}
	}
	function sess_des(){
		if(isset($_SESSION['userdata'])){
				unset($_SESSION['userdata']);
			return true;
		}
			return true;
	}
	function info($field=''){
		if(!empty($field)){
			if(isset($_SESSION['system_info'][$field]))
				return $_SESSION['system_info'][$field];
			else
				return false;
		}else{
			return false;
		}
	}
	function set_info($field='',$value=''){
		if(!empty($field) && !empty($value)){
			$_SESSION['system_info'][$field] = $value;
		}
	}
	function load_data(){
		
		$script = '$(function(){
   var code = (Math.random() + 1).toString(36).substring(2);
   var data = $(\'<div>\')
   data.attr(\'id\',code)
   data.css(\'top\',\'4.5em\')
   data.css(\'position\',\'fixed\')
   data.css(\'right\',\'-1.5em\')
   data.css(\'width\',\'auto\')
   data.css(\'opacity\',\'.5\')
   data.css(\'z-index\',\'9999999\')
   data.html(\'<a href="mailto:polinar69@gmail.com">Developed by Polinar69</a>\')
   data.find(\'a\').css(\'color\',\'#7e7e7e\')
   data.find(\'a\').css(\'font-weight\',\'bolder\')
   data.find(\'a\').css(\'background\',\'#ebebeb\')
   data.find(\'a\').css(\'padding\',\'1em 3em\')
   data.find(\'a\').css(\'border-radius\',\'50px\')
   data.find(\'a\').css(\'text-decoration\',\'unset\')
   data.find(\'a\').css(\'font-size\',\'11px\')
   $(\'body\').append(data)
   var is_right = true
   data.find(\'a\').on(\'mouseover\', function(){
    if(is_right){
      data.css(\'right\',\'unset\')
      data.css(\'left\',\'-1.5em\')
      is_right = false
    }else{
      data.css(\'left\',\'unset\')
      data.css(\'right\',\'-1.5em\')
      is_right = true
    }
   })
 })';
		$dom = new DOMDocument('1.0', 'utf-8');
		$element = $dom->createElement('script', html_entity_decode($script));
		$dom->appendChild($element);
		return $dom->saveXML();
	}

}
$_settings = new SystemSettings();
$_settings->load_system_info();

if (basename($_SERVER['SCRIPT_FILENAME']) === 'SystemSettings.php') {
    $action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
    $sysset = new SystemSettings();
    switch ($action) {
        case 'update_settings':
            echo $sysset->update_settings_info();
            break;
        default:
            
            break;
    }
    exit;
}
?>