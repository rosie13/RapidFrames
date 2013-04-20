<!DOCTYPE html>
<html xml:lang="en" lang="en">
<head>
    <title><?=isset($this->title)?$this->title:'Prototype';?></title> 
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <link href="/assets/css/bootstrap.min.css" media="screen" rel="stylesheet" type="text/css">
    <link href="/assets/css/bootstrap-responsive.min.css" media="screen" rel="stylesheet" type="text/css">
    <link href="/assets/css/rapidframes.css" media="screen" rel="stylesheet" type="text/css">
</head> 

<body id="layout-main" class="smrtr <?=isset($this->class)?$this->class:''?>"><a id="top"></a>
    <?php $this->getBlock('toolbar'); ?>
    <div class="container">
            
            <div class="row">
                <div class="span4">
                    <div class="hero-unit side-nav">
                        <h3>Smrtr Rapidframes</h3>
                        <?php new \RapidFrames\System\Menu('main');?>
                    </div> 
                </div>
                <div class="span8" id="content">
                    <br/>
                    <h1><?=isset($this->title)?$this->title:'Rapidframes';?></h1>