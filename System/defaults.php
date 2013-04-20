<!DOCTYPE html>
<html>
    <head>
        <title><?=$this->title?>title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <style>
            *{padding:0;margin:0;}
            body{font-family: helvetica,sans-serif;color:#333;}
            .stage{padding:20px;margin:50px auto;width:600px}
            h1{font-size:50px;}
            h2{font-size:20px;margin:10px 0}
            h3{font-size:17px;margin:10px 0}
            a{color:#333;text-decoration: none}
            footer{margin-top: 100px;font-size:0.8em;}
            code{background:#eee;font-weight:bold;font-size:0.8em}
            #sitemap ul{list-style:none;}
            #sitemap ul li{padding:3px 0 0 20px}
        </style>
    </head>
    <body>
        <div class="stage">
            <h1><?=$this->title?> Not Found</h1>
            <h2>Create a custom Project/layout/<?=$this->slug?>.php file.</h2>
          
            <?php if($this->slug==='sitemap'):?>
                <div id="sitemap">
                <?php new \RapidFrames\System\Sitemap();?>
                </div>
            <?php endif?>
            <footer><a href="http://rapidfram.es">RapidFram.es</a></footer>
        </div>
    </body>
</html>



