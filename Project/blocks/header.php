<!DOCTYPE html>
<html xml:lang="en" lang="en">
<head>
    <title><?=isset($this->title)?$this->title:'Prototype';?></title> 
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <link href="/assets/css/bootstrap.min.css" media="screen" rel="stylesheet" type="text/css">
    <link href="/assets/css/bootstrap-responsive.min.css" media="screen" rel="stylesheet" type="text/css">
</head> 

<body>
    <div class="container">
            <div class="row">
                <div class="span12">
                    <br/>
                    <?php if(!isset($this->headerTitle) || isset($this->headerTitle) && $this->headerTitle===true){?>
                    <h1><?=isset($this->title)?$this->title:'Rapidframes';?></h1>
                    <?php }?>