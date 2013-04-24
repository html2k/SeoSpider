<?php
/**
 * Sitemap
 * h1...
 * title
 * description
 * status
 * alt img
 * title link
 * vCard
 * menu
 * microformats
 */
require_once('simple_html_dom.php');
$seospider = new SeoSpider();

if(isset($_POST['url']))
  echo json_encode($seospider->analyze($_POST['url']));
elseif(isset($_POST['internal_pages']))
  echo json_encode($seospider->analyze());

class SeoSpider{
  private $dom;
  private $db;
  private $domain;
  public $limit;

  function __construct(){
    if(!class_exists('simple_html_dom'))
      die('Missing simple_html_dom');

    if(!class_exists('SQLite3'))
      die('Missing sqlite3');

    $this->db = new SQLite3("SeoSpider.db");

    if(!$this->db)
      die('Missing permission to create database');

    if(isset($_POST['restart']) && $_POST['restart'] === 'true'){
      $create_table = $this->db->exec("DROP TABLE IF EXISTS urls; CREATE TABLE urls (id INTEGER PRIMARY KEY AUTOINCREMENT, url TEXT UNIQUE NOT NULL, visited TEXT CHECK(visited IN ('Y', 'N')))");
      if(!$create_table)
        die('Error creating table');
    }

    $this->dom = new simple_html_dom(null);

    $this->limit = 10;
  }

  function __destruct() {
    $this->db->close();
  }

  public function analyze($url = null){
    if(!is_null($url) && $this->check_url($url)){
      $urls = array($url);
      $this->domain = preg_replace('/http:\/\/([^\/]+).*/','$1',$url);
      if(isset($_POST['internal_pages'])){
        $this->db->exec("INSERT OR IGNORE INTO urls (url,visited) VALUES ('http://{$this->domain}/robots.txt', 'N');
        INSERT OR IGNORE INTO urls (url,visited) VALUES ('http://{$this->domain}/sitemap.xml', 'N')");
      }

    }

    if(!isset($urls)){
      $select_urls = $this->db->query("SELECT url FROM urls WHERE visited = 'N' ORDER BY id ASC LIMIT {$this->limit};");
      while ($record = $select_urls->fetchArray(SQLITE3_ASSOC)) {
        $urls[] = $record['url'];
      }
    }
    if(!count($urls))
      return false;

    $results = $this->fetch_urls($urls);

    if(count($results)){
      $update_query = '';
      $select_urls = $this->db->query("SELECT COUNT() as counter FROM urls");
      $record = $select_urls->fetchArray(SQLITE3_ASSOC);
      $analize['counter'] = $record['counter'];

      foreach($results AS $url=>$array){

        $update_query .= "UPDATE urls SET visited = 'Y' WHERE url LIKE '{$url}';";

        if(isset($array['content']))
          $analize[$url] = $this->report($array);
      }
      if(!$update = $this->db->exec($update_query))
        return false;

      if(isset($analize))
        return $analize;
    }
    $this->__destruct();
    return 'error analization';
  }

  private function check_url($url){
    if(preg_match('/^http:\/\/[-\w\.\/]+$/',$url))
      return $url;
    return false;
  }

  private function report($array){
    if (empty($array['content']) || strlen($array['content']) > MAX_FILE_SIZE)
      return false;

    $this->dom->load($array['content']);
    $html = $this->dom;

    if(!isset($this->domain))
      $this->domain = preg_replace('/http:\/\/([^\/]+).*/','$1',$array['original_url']);

    foreach($html->find("a[href^=http://{$this->domain}/]") AS $link){
      if($this->check_url($link->href)){
        $new_urls_query[] = "('{$link->href}', 'N')";
      }
    }
    if(isset($new_urls_query)){
      $this->db->exec("INSERT OR IGNORE INTO urls (url,visited) VALUES ".implode(';INSERT OR IGNORE INTO urls (url,visited) VALUES ',$new_urls_query) .";");
      if($this->db->lastErrorCode())
        die($this->db->lastErrorMsg());
    }
    $report = array(
      'address' => $array['original_url'],
      'redirect_url' => $array['original_url'] != $array['url'] ? $array['url'] : '',
      'content' => $array['content_type'],
      'status' => $array['http_status'],

      'title' => is_object($html->find('title',0)) ? $html->find('title',0)->innertext : '',
      'title_lenght' => is_object($html->find('title',0)) ? strlen(preg_replace("/&#?[a-z0-9]+;/i","o",$html->find('title',0)->innertext)) : 0,
      'title_items' => count($html->find('title')),

      'meta_description' => is_object($html->find("meta[name='description']",0)) ? $html->find("meta[name='description']",0)->innertext : 0,
      'meta_description_lenght' => is_object($html->find("meta[name='description']",0)) ? strlen(preg_replace("/&#?[a-z0-9]+;/i","o",$html->find("meta[name='description']",0)->innertext)) : '',
      'meta_description_items' => count($html->find("meta[name='description']")),

      'meta_keywords' => is_object($html->find("meta[name='keywords']",0)) ? $html->find("meta[name='keywords']",0)->innertext : '',
      'meta_keywords_lenght' => is_object($html->find("meta[name='keywords']",0)) ? strlen(preg_replace("/&#?[a-z0-9]+;/i","o",$html->find("meta[name='keywords']",0)->innertext)) : 0,
      'meta_keywords_items' => count($html->find("meta[name='keywords']")),

      'h1' => is_object($html->find('h1',0)) ? $html->find('h1',0)->plaintext : '',
      'h1_lenght' => is_object($html->find('h1',0)) ? strlen(preg_replace("/&#?[a-z0-9]+;/i","o",$html->find('h1',0)->plaintext)) : 0,
      'h1_items' => count($html->find("h1")),

      'h2' => is_object($html->find('h2',0)) ? $html->find('h2',0)->plaintext : '',
      'h2_lenght' => is_object($html->find('h2',0)) ? strlen(preg_replace("/&#?[a-z0-9]+;/i","o",$html->find('h2',0)->plaintext)) : 0,
      'h2_items' => count($html->find("h2")),

      'size' => $array['size_download'],
      'word_count' => is_object($html->find('body',0)) ? str_word_count($html->find('body',0)->plaintext) : 0,

      'images_without_alt' => count($html->find("img")) - count($html->find("img[alt]")),

      'outlinks_without_title' => count($html->find("a[href]")) - count($html->find("a[href][title]")),
      'outlinks' => count($html->find("a[href]")),
      'external_outlinks' => count($html->find("a[href]")) - count($html->find("a[href^=http://{$this->domain}/]")),
      'inlinks' => count($html->find("a[href^=http://{$this->domain}/]")),

      'hash' => md5($array['content'])
    );

    return $report;
  }

  private function fetch_urls($urls, $curl_options = array()) {
    $curl_multi = curl_multi_init();
    $handles = array();

    $options = $curl_options + array(
      CURLOPT_HEADER         => true,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_NOBODY         => false,
      CURLOPT_FOLLOWLOCATION => true);

    foreach($urls as $url) {
      $handles[$url] = curl_init($url);
      curl_setopt_array($handles[$url], $options);
      curl_multi_add_handle($curl_multi, $handles[$url]);
    }

    $active = null;
    do {
      $status = curl_multi_exec($curl_multi, $active);
    } while ($status == CURLM_CALL_MULTI_PERFORM);

    while ($active && ($status == CURLM_OK)) {
      if (curl_multi_select($curl_multi) != -1) {
        do {
          $status = curl_multi_exec($curl_multi, $active);
        } while ($status == CURLM_CALL_MULTI_PERFORM);
      }
    }

    if ($status != CURLM_OK) {
      trigger_error("Curl multi read error $status\n", E_USER_WARNING);
    }

    $results = array();
    foreach($handles as $url => $handle) {
      $results[$url] = curl_getinfo($handle);
      $results[$url]['original_url'] = $url;
      if($results[$url]['http_code'] == 200){
        $results[$url]['content'] = curl_multi_getcontent($handle);
        if(preg_match('/^(HTTP\/.*)$/mi',$results[$url]['content'],$matches))
          $results[$url]['http_status'] = $matches[1];
      }
      curl_multi_remove_handle($curl_multi, $handle);
      curl_close($handle);
    }
    curl_multi_close($curl_multi);

    return $results;
  }
}