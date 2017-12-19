<?php
error_reporting(E_ALL);
ini_set("memory_limit", "-1");
set_time_limit(0);
include_once 'simple_html_dom.php';

class Crawler
{


  private $context;
  private $useragentstring;

  private $html;
  private $date_format = 'Y-m-d H:i:s';
  private $xml;

  public $data = array();

  public function __construct()
  {
    $opts = array(
      'http'=>array(
        // 'method'=>"GET",
        'header' => "Mozilla/5.0 (Linux; Android 4.4.2; Nexus 4 Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.114 Mobile Safari/537.36"
      )
    );

    $this->context = stream_context_create($opts);


    $this->html = file_get_html('http://www.dumpert.nl/');
  }

  public function fetch_links($pages = 1, $limit = null)
  {

    $limiti = 0;
    for ($page=0; $page < $pages; $page++)
    {
      $this->html = file_get_html('http://www.dumpert.nl/'.$page.'/');

      $links = $this->html->find('a[class=dumpthumb]');


      foreach ($links as $key => $link)
      {
        if($limit == null)
        {
          array_push($this->data, array(
            "href" => $link->href,
            "title" => $link->title
          ));
        }
        else
        {
          if($limiti < $limit)
          {
            array_push($this->data, array(
              "href" => $link->href,
              "title" => $link->title
            ));
          }
          $limiti++;
        }
      }

    }
  }

  public function fetch_details($godeep = true)
  {
    //godeep will also fetch the information of the related videos
    foreach ($this->data as $key => $linkarray)
    {
      $this->html = file_get_html($linkarray['href']);

      $uploadinfo = date($this->date_format, strtotime($this->html->find('p[class=dump-pub]')[0]->plaintext));
      $uploaddescription = $this->html->find('div[class=dump-desc]')[0]->childNodes(2)->plaintext;

      $vidsrc = $this->html->find('div[class=videoplayer]')[0]->attr['data-files'];

      //decode and select right formats
      $vidsrc = json_decode(base64_decode($vidsrc), true);

      //id
      $id = $this->html->find("body")[0]->attr['data-itemid'];

      //get related video links

      $this->html->clear();




      //$videosrc = $this->html->find('video[class=jw-video]')->src;

      $this->data[$key]['details'] = array(
        "id" => $id,
        "title" => $linkarray['title'],
        "description" => $uploaddescription,
        "datetime" => $uploadinfo,
        "video" => $vidsrc
      );

      if($godeep == true)
      {
        $relatedArray = array(); //basic links and titles
        $fetchedRelated = array(); //more in depth info

        $this->xml = simplexml_load_file("http://www.dumpert.nl/related.php?id=".$id);


        foreach ($this->xml->channel->item as $arkey => $item)
        {
          array_push($relatedArray, array(
            "href" => json_decode(json_encode($item->link[0]), true)[0],
            "title" => json_decode(json_encode($item->title[0]), true)[0]
          ));

          $tempCrawler = new self;
          $tempCrawler->data = $relatedArray;
          $tempCrawler->fetch_details(false);
          $fetchedRelated = $tempCrawler->data;
        }

        $this->data[$key]['related'] = $fetchedRelated;
      }
      //tags


      //remove 'title' key from array since it's now set in the details block.
      unset($this->data[$key]['title']);
      //clear memory.
      $this->html->clear();

    }
  }
}
?>
