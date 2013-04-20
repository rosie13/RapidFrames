<?php 
namespace RapidFrames\System;

/**
 * Page Navigation
 * Get a sidebar listing the descendents 
 * @package rapidframes
 * @author Mwayi Dzanjalimodzi
 * @param string $orderby needs to be the column name
 * @param string $order the asc,desc 
 * @uses \API
 * @method printNav
 */

class Page_Nav extends Tree
{
    public function __construct($orderby='slug', $order='asc')
    {
        global $currentPage, $pagesData;
        if(!isset($currentPage->symSlug))
            return false;
        $api = new \API;
        $page = $api->get('page')
                ->symslug($currentPage->symSlug)
                ->orderby($orderby)
                ->order($order)
                ->format('json')
                ->fetchData();
        $page = json_decode($page);

        $page->ancestors = (array)$page->ancestors;
        $page->ancestors = array_reverse($page->ancestors);
        $masterSymSlug = count($page->ancestors)>0?
                         str_replace('/','--',end($page->ancestors)):$currentPage->symSlug;
        $master = $api->get('page')
                ->symslug($masterSymSlug)
                ->orderby($orderby)
                ->order($order)
                ->format('json')
                ->fetchData();
        $master = json_decode($master);
        
        $this->page = $page;
        $this->current = $currentPage;
        $this->pages = $pagesData;
        if(!isset($master->children))
            return false;
        $this->printNav($master->children);
    }
    
    /**
     * Print out a side nav
     * @param string $orderby
     * @param string $order
     * @uses \API
     */
    public function printNav($slugs, $depth = 0,$current=' ')
    {
        $subdepth = $depth + 1;
        if(count($slugs)>0){
            printf ('<ul%s>',$current);
            foreach ($slugs as $slug){
                $page = $this->pages->{$slug};
                $page->ancestors = (array)$page->ancestors;
                if(count($page->ancestors)==$subdepth){
                    $current = $this->current->slug===$page->slug||
                            in_array($this->current->slug, $page->descendents)?' class="selected" ':' ';
                    printf('<li%s><a href="%s" %s>%s</a>',$current,$page->permalink,$current,$page->title);
                    if($page->children>0) 
                        $this->printNav((array)$page->children, $subdepth,$current);
                    echo '</li>';
                }
            }
            echo '</ul>';
        }
    }
}

/**
 * Sitemap
 * Generate the entire sitemap tree structure
 * @package rapidframes
 * @author Mwayi Dzanjalimodzi
 * @method printNav
 */
class Sitemap extends Tree{
    
    public function __construct($orderby='slug', $order='asc')
    {
        global $pagesData;
        $api = new \API;
        $slugs = $api->get('slugs')
                ->orderby($orderby)
                ->order($order)
                ->format('json')
                ->fetchData();
        $slugs = json_decode($slugs);
        $this->pages = $pagesData;
        $this->printNav($slugs);
    }

    /**
     * Print out a side nav
     * @param string $slugs
     * @param string $depth
     * @param string $current
     */
    private function printNav($slugs, $depth = -1, $current=' ')
    {
        $subdepth = $depth + 1;
        if(count($slugs)>0){
            printf ('<ul%s>',$current);
            foreach ($slugs as $slug){ 
                $page = $this->pages->{$slug};
                $page->ancestors = (array)$page->ancestors;
                if(count($page->ancestors)===$subdepth){
                    printf('<li><a href="%s">%s</a>',$page->permalink,$page->title);
                    if($page->children>0) 
                        $this->printNav((array)$page->children, $subdepth,$current);
                    echo '</li>';
                }
            }
            echo '</ul>';
        }
    }
}

/**
 * Menus
 * Get a menu and list an nth nested tree wrapped with simple mark up
 * @package rapidframes
 * @author Mwayi Dzanjalimodzi
 * @param string $name the name of the loaded csv
 * @param string $orderby needs to be the column name
 * @param string $order the asc,desc 
 * @method printNav
 */
class Menu extends Tree
{    
    public function __construct($name,$orderby='order',$order='asc')
    {   
        global $currentPage;
        $api = new \API;
        // Top level slugs
        $slugs = $api->get('menu-slugs')
                ->name($name)
                ->orderby($orderby)
                ->order($order)
                ->format('json')
                ->level(0)
                ->fetchData();
        $this->slugs = (array)json_decode($slugs);
        // Get menu items
        $links = $api->get('menu')
                ->name($name)
                ->orderby($orderby)
                ->order($order)
                ->format('json')
                ->fetchData();
        $this->links = json_decode($links);
        $this->current = $currentPage;
        if(count($this->slugs)<1)
            return false;
        $this->printNav($this->slugs);
    }
    
    /**
     * Print out a side nav Recurrsively for subdepths
     * @param string $slugs
     * @param string $depth
     * @param string $current
     */
     private function printNav($slugs, $depth = -1, $current='')
     {
        $subdepth = $depth + 1;
        if(count($slugs)>0){
            printf ('<ul class="level%d %s">',$subdepth,$current);
            foreach ($slugs as $slug){ 
                $link = $this->links->{$slug};
                $link->ancestors = (array)$link->ancestors;
                if(count($link->ancestors)===$subdepth){
                    // If url field filled override permalink
                    $url = isset($link->url) && $link->url?$link->url:$link->permalink;
                    // If we are on current page apply class
                    $classes = array();
                    if($this->current->slug===$link->slug)
                    $classes[] = $current = 'active';
                    if($this->current->slug===$link->slug||
                            in_array($this->current->slug, (array)$link->descendents))
                    $classes[] = $current = 'selected';
                    $classes[] = 'level'.$link->level;
                    $classes[] = $link->symSlug;
                    // Target type of link i.e. _blank
                    $target = isset($link->target) && $link->target?" target=\"$link->target\" ":'';
                    printf('<li class="%s"><a href="%s" %s>%s</a>',implode(' ',$classes),$url,$target,$link->title);
                    if(count($link->children)>0) 
                        $this->printNav((array)$link->children, $subdepth,$current);
                    echo '</li>';
                }
            }
            echo '</ul>';
        }
    }
}