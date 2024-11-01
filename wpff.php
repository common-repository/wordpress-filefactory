<?php
/**
 * @package WPFileFactory
 * @author PsMan
 * @version 1.1
 */
/*
Plugin Name: WP FileFactory
Plugin URI: http://angrybyte.com/wordpress-plugins/wordpress-filefactory-plugin/
Description: The idea is simple, gain some money instead of losing some bandwidth, this plugin will transfer all your uploaded files to filefactory, this way you will save on bandwidth, make your server run faster, and make a few bucks per each download.
Author: PsMan, Bouzid Nazim Zitouni
Version: 1.1
Author URI: http://angrybyte.com
*/

add_action('admin_menu', 'ffmenu');
add_filter('the_content','ffposts');
add_action('wp_footer','fffooterlink');
//add_action('admin_menu', 'taggatormenu');
//add_action('publish_post', 'autotag');
//add_option("taggatorcs", '1', 'Case sensitivity for taggator?', 'yes');
//add_option("taggatormhw", '1', 'Match whole words?', 'yes');
//add_option("taggatortags", '1', 'Taggator tags', 'yes');
function fffooterlink($content){
	// Please keep this link to support us, if you really want to remove it, please make a donation first, then remove the following line, Thanks! 
		$content .= "<p align='center'>Files hosting provided by <a href='http://www.filefactory.com/affiliates/refer/FCdXVhw' rel='nofollow'> FileFactory.com</a> Via <a href='http://angrybyte.com/wordpress-plugins/wordpress-filefactory-plugin/'> Wordpress FileFactory Plugin </a></p>";
echo $content;
return $content;
		
	}
function ffposts($content){
global $wpdb;
//$serv=str_replace( '%7E', '~', $_SERVER['REQUEST_URI']);
 $pfx=$wpdb->prefix;
 $a= $wpdb->query("CREATE TABLE IF NOT EXISTS `{$pfx}wpff` (
`id` INT NOT NULL AUTO_INCREMENT ,
`url` VARCHAR( 2083 ) NOT NULL ,
`ffurl` VARCHAR( 2083 ) NOT NULL ,
`attid` int NOT NULL ,
PRIMARY KEY ( `id` ) 
) ENGINE = InnoDB");
$res = $wpdb->get_results("select url,ffurl,attid from {$pfx}wpff");
	
foreach($res as $url){
	$atturl=get_attachment_link($url->attid);
	if(!strpos($content,$url->url)==FALSE){
		$content=str_ireplace($url->url,$url->ffurl,$content);
		$content=str_ireplace($atturl,$url->ffurl,$content);
		}
	
}

 return $content;

}

function ffmenu()
{

    add_options_page('WP FileFactory', 'WP-FileFactory', 8, __file__,'wpff_plugin_options');
   // add_options_page('TagGator Auto-Tagger', 'TagGar', 8, __file__,'taggator_plugin_options');
    
}

function urlbreak($url){
	
	$ex=explode("/",$url);
	$ex=$ex[count($ex) -1 ];
	return $ex;
}
function wpff_plugin_options()
{
//duplica(); no longer needed after changing to filters	
global $wpdb;
$serv=str_replace( '%7E', '~', $_SERVER['REQUEST_URI']);
 $pfx=$wpdb->prefix;
 $a= $wpdb->query("CREATE TABLE IF NOT EXISTS `{$pfx}wpff` (
`id` INT NOT NULL AUTO_INCREMENT ,
`url` VARCHAR( 2083 ) NOT NULL ,
`ffurl` VARCHAR( 2083 ) NOT NULL ,
`attid` int NOT NULL ,
PRIMARY KEY ( `id` ) 
) ENGINE = InnoDB");

  $res=$wpdb->get_results("select ID, guid from {$pfx}posts where (instr(post_mime_type,'image')=0) AND (post_type = 'attachment') AND NOT guid in (select url from {$pfx}wpff) ");
 $filelist="";
 foreach($res as $fil){
 	$filelist .= $fil->guid . "\n";
 	
 	
 	}
 if($_POST['dellink']){
 	if (!is_user_logged_in()){
 return("buzz off n00b");
 }else{
 	$delres=$_POST['dellink'];
 	$delres=$wpdb->query("delete from {$pfx}wpff where ffurl ='$delres' LIMIT 1");
 	
 	}
 }
  //---------------------------------------------accept user posted csv
 if($_FILES["usafile"]){
$nofadd=0;
 if (!is_user_logged_in()){
 return("buzz off n00b");
 }else{
 
 if (($_FILES["usafile"]["type"] == "text/csv")||($_FILES["usafile"]["type"] == "text/comma-separated-values"))
 
 {
 	//echo "file is cvs";
 if ($_FILES["usafile"]["error"] > 0)
 {
 echo "Return Code: " . $_FILES["usafile"]["error"] . "<br />";
 }
 else
 {
 	 $basedir=str_replace("wp-admin",'',realpath(dirname($_SERVER['index.php']))) ;
 move_uploaded_file($_FILES["usafile"]["tmp_name"],
 $basedir .  $_FILES["usafile"]["name"]);
 $myfile = $basedir .  $_FILES["usafile"]["name"];
 $file=fopen($myfile,"r");
 //echo $basedir .  $_FILES["usafile"]["name"] . $file;
 while(!feof($file))
  {
  $line =  fgets($file);
  $line= str_ireplace('"','',$line);
  $line= explode(",",$line);
  
  
   foreach($res as $fil){
   	if(strtolower($line[1]) == strtolower(urlbreak($fil->guid))){
   		$psts=$wpdb->get_var("select ffurl from {$pfx}wpff where (ffurl='$line[2]') OR url='$fil->guid' LIMIT 1"); //check both the links FF and wordpress
   		if(!$psts == $line[2]){
   			//we dont already have the file in db, so insert it
   			
   			$psts=$wpdb->query("insert into {$pfx}wpff(url,ffurl,attid) values ('$fil->guid','$line[2]',$fil->ID)"); 
   			$nofadd=$nofadd + 1;
			 //  //nailed it!
   		}
   	//$psts=$wpdb->query("update {$pfx}posts set post_content = REPLACE(post_content, '$fil->guid', '$line[2]') where instr(post_content,'$fil->guid'); "); // not safe I'm better off with a filter'
	  //$psts= $wpdb->query("update {$pfx}posts set post_content =  '$line[2]' ID = $fil->ID); ");
   	}
   	
   	
   }
  }
fclose($file);
 
 }
 }
 }
 echo <<<EORT
 	<div id='message' class='error'><p>Your file is recieved! and $nofadd new FileFactory Links were added! <br /></h2></p></div>	
EORT;
 }
 
 $res=$wpdb->get_results("select ID, guid from {$pfx}posts where (instr(post_mime_type,'image')=0) AND (post_type = 'attachment') AND NOT guid in (select url from {$pfx}wpff) ");
 $filelist="";
 foreach($res as $fil){
 	$filelist .= $fil->guid . "\n";
 	
 	
 	}

	Echo <<<EOFT
	<h2> WordPress FileFactory</h2>
	Hello and welcome to the WP FileFactory, this is were you move all files on your blog to filefactory.com saving on your bandwidth and making some bucks while at it if don't already have a FileFactory account please go here and register for free <br /> <a href="http://www.filefactory.com/affiliates/refer/FCdXVhw">
<img src="http://www.filefactory.com/img/buttons/affiliate/subscribe.png" alt="Join FileFactory Today!" />
</a> <br />
<ol>
<li> If don't already have one, <a href="http://www.filefactory.com/affiliates/refer/FCdXVhw"> create a filefactory account</a> and Logon to it <br /></li>
<li> Copy the list of files in your blog from the bottom of this page. and head to <a href="http://www.filefactory.com/" > filefactory.com </a> and click "Remote" then Past multiple links.<br />
<li> Past the links you copies from here, then upload them.<br /></li>
<li> wait for a while for the uploads to finish, once done go to this page and <a href="http://www.filefactory.com/member/csvExport.php" >download the list of your files </a><br /></li>
<li> Upload that file here.<br /></li>
<li> That's this plugin will replace all links to your locally hosted files with your new filefactory links!<br /></li></ol>

<h3> Files on your blog:</h3><br /> <form> <textarea name='xx' cols='50' rows='10'>$filelist</textarea></form>

<br />

<h3> Upload FileFactory Csv list</h3> <form action='$serv' method='post'
enctype='multipart/form-data'>
<label for='file'><br />Filename:</label>
 <input type='file' name='usafile' id='usafile' /> 
<br />
<input type='submit' name='submit' value='Submit' />
</form>
<br /><form action='$serv' method='post'><h3> Delete Dead Links</h3><br /> if one of the your FileFactory Links is reported dead, you can have it removed from the databse by pasting it here, you can always reupload it if you want to <input type='Text' name='dellink' /><input type='submit' name='submit' value='Delete Link' /></form> <br /><dl>
	<dd><i> FileFactory, www.filefactory.com are registered trademarks for their respective owners,  This plugin is not affiliated with or funded by FileFactory, it is just a free independent work for non-commercial use, If you like my work, please <a href= 'http://angrybyte.com/donate' >think about making a donation </a>, It will help us keep the internet a free place to take and to give </i></dd>
</dl>	
EOFT;
}

function duplica(){
	$dups=0;
//	echo "in dublica";
	global $wpdb;
$serv=str_replace( '%7E', '~', $_SERVER['REQUEST_URI']);
 $pfx=$wpdb->prefix;
  $res=$wpdb->get_results("select guid , post_modified from {$pfx}posts where post_type = 'attachment' ");
 $filelist="";
 $n=0;
 foreach($res as $fil){
 	$pts=explode("/",$fil->guid);
 	$pts=$pts[count($pts) -1 ];
 	//echo "$pts";
 	//echo "Once";
 	for( $xx=0 ; $xx< $n ; $xx++){
 	
 	//	echo  $filelist[0] .$xx . $n;
 		if (strtolower($pts)==strtolower($filelist[$xx])){
 			
 			$upload_dir = wp_upload_dir(strftime($fil->post_modified));
 		echo <<<EORT
 	<div id='message' class='error'><p>Duplicate file names were found, we renamed them, and updated your the database and in-post links accordingly. Don't Worry!'<br /></h2></p></div>	
EORT;
		 	rename($upload_dir[path] . '/' . $pts,$upload_dir[path] . '/' . 'x' .$pts);
		 	$wpdb->query("update {$pfx}posts set guid = '$upload_dir[url]/x$pts' where guid='$fil->guid' ");
		 	$wpdb->query("update {$pfx}posts set post_content = replace(post_content,'$fil->guid','$upload_dir[url]/x$pts') where  instr(post_content,'$fil->guid')");
		 	$dups=1;
 		}
 	}
 	
 	$filelist[$n]= $pts;
 	//echo $filelist[$n] . "---" . $n;
 	$n= $n +1;
 	}
 	if($dups){
 	duplica();	
 	}
}

function rever(){
	
	
}

?>