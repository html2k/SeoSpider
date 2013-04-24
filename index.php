<html>
  <head>
    <link rel="stylesheet" href="http://code.jquery.com/ui/1.10.2/themes/smoothness/jquery-ui.css" />
    <style>
      body{font-size: 10px;}
      #accordion ul,#robots{display: none;}
      #counter{position: fixed; right: 10px; top: 10px;color: white;background-color: rgb(194, 0, 0);padding: 10px 20px;border-radius: 20px;}
      .bar{
        background-color: rgba(255,30,30,1);
        width: 0;
        height: 5px;
      }
      #slider{
        width: 90%;
      }
      a.ui-slider-handle{
        left: 100%;
        width: 35px;
        text-align: center;
        text-decoration: none;
      }
    </style>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
    <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.2/jquery-ui.min.js"></script>
    <script src="SeoSpider.js"></script>
  </head>
  <body>
    <form id="SeoSpider" method="POST" action="SeoSpider.php">
      <label for="url" class="ui-widget">Url:</label>
      <input type="text" id="url" name="url" size="155" placeholder="http://www.sito.it/" class="ui-widget"><br/>
      <label for="internal_pages">Check to follow internal links</label>
      <input type="checkbox" id="internal_pages" name="internal_pages">
      <label for="sub_domains">Check to follow sub domain links</label>
      <input type="checkbox" id="sub_domains" name="sub_domains">
      <input type="submit">
    </form>

    <div id="slider"></div>

    <div id="accordion" class="ui-accordion ui-widget ui-helper-reset">
      <div id="robots"><h3 class="ui-accordion-header ui-helper-reset ui-state-default ui-corner-all ui-accordion-icons">ROBOTS.TXT<div class="bar" style="width: 100%"></div></h3><ul><li class="ui-state-error">NOT FOUND</li></ul></div>
    </div>

  <div id="counter"><span id="partial"></span> / <span id="total"></span></div>
  </body>
</html>