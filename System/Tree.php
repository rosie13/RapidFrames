<?php 
namespace RapidFrames\System;

/**
 * Data Tree
 * Converts CSV rows and columns into a DataTree Instance
 * @author Mwayi Dzanjalimodzi 
 * @param array $data
 * @param array $head
 * @return array
 */
class DataTree
{
    public function __construct(array $data, array $head)
    {
        $head = array_map("RapidFrames\System\DataTree::normalise",$head);
        $headIndex = array_flip($head);
        $tree = array();
        foreach($data as $row){
            foreach($row as $key => $val){
                $col = $head[$key];
                $slugIndex = $headIndex['slug'];
                $slug = str_replace('/','--',preg_replace('/[\/]+/','/',$row[$slugIndex]));
                //do not allow any empty row
                if(!empty($slug)){
                    $tree[$slug][$col] = $val;
                    $this->{$slug}[$col] = $val;
                }
            }
        }
    }
    
    public static function normalise(&$item)
    {
        return strtolower($item);
    }
}

/**
 * Tree
 * @return object $this
 */
class Tree
{
    public function __construct(DataTree $dataTree)
    {
        $this->setMeta($dataTree);
        $this->setChildren();
        $this->setDescendents();
        return $this;
    }
    
    /**
     * Set the generla Tree meta
     * @param DataTree object $dataTree
     */
    private function setMeta(DataTree $dataTree)
    {
        foreach($dataTree as $symSlug => $obj)
        {
            $data = array();
            $slug = str_replace('--','/',$symSlug);
            $data['permalink'] = $permalink = 'home'===$symSlug?'/':'/'.str_replace('--','/',$symSlug);
            $data['slug'] = $slug;
            $data['symSlug'] = $symSlug;
            $data['title'] = !isset($obj['title']) || !$obj['title']?$this->getTitle($symSlug):$obj['title'];
            $ancestors = (array)$this->getAncestors($slug)?$this->getAncestors($slug):array();
            $data['parent'] = count($ancestors)>0?end($ancestors):false;
            $data['level'] = count($ancestors);
            $data['ancestors'] = $ancestors;
            $this->{$slug} = (object)array_merge($dataTree->{$symSlug},$data);
        }
    }
    
    /**
     * Get Title
     * @param string $slug
     * @return string
     */
    private function getTitle($slug)
    {
        $parts = explode('--',$slug);
        $page = ucwords(end($parts));
        return str_replace('-',' ',$page);
    }
    
    /**
     * Get Children
     * @param string $slug
     * @return array
     */
    private function getChildren($slug)
    {
        $children = array();
        foreach($this as $key => $obj){
            if(isset($this->{$key}['parent']) && $this->{$key}['parent']==$slug)
                $children[] = $key; 
        }
        return $children;
    }
    
    /**
     * Get Parent of slug
     * @param object
     * @return object
     */
    private function setChildren()
    {
        $parent = $this;
        foreach($parent as $pkey => $parentObj){//loop through all
            $this->{$pkey}->children = array();
            foreach($parent as $ckey => $childObj){
                if(isset($this->{$ckey}->parent) && $this->{$ckey}->parent==$pkey)
                    $this->{$pkey}->children[$this->{$ckey}->symSlug] = $ckey;
            }
        }
        return $this;
    }
    
    /**
     * Get Parent of slug
     * @param string symbolic $slug
     * @return string|false
     */
    private function getParent($key)
    {
        if(isset($this->{$key}['parent']))
            return $this->{$key}['parent'];
        return false;
    }

    /**
     * Get meta
     * @param string symbolic $slug
     * @return object|false
     */
    private function getMeta($key)
    {    
         if(isset($this->{$key}))
            return $this->{$key};
         return false;
    }

    /**
     * Get the ancestors
     * @param string symbolic $slug
     * @return array
     */
    private function getAncestors($slug)
    {
        $ancestors = explode('/',$slug);
        $ancestors = array_slice($ancestors,0,count($ancestors)-1);
        if(count($ancestors)===0)
            return array();
        $totalAncestors = count($ancestors);
        $ancestorSlugs = array();
        for($i=$totalAncestors-1; $i>=0; $i--){
            $ancestorSlug = implode('/',array_slice($ancestors,0,count($ancestors)-$i));
            $ancestorSlugs[str_replace('/','--',$ancestorSlug)] = $ancestorSlug;
        }
        return (array)$ancestorSlugs;
    }

    /**
     * Count ancestors
     * @param object $object
     * @return int|bool
     */
    public function countAncestors($object)
    {
        if(isset($object->ancestors))
             return count($object->ancestors);
        return false;
    }
 
    /**
     * Set the descendants
     * @return object $this
     */
    private function setDescendents()
    {
        foreach($this as $slug => $data){
            $this->{$slug}->descendents = array();
            foreach($this as $childSlug => $child){
                if(isset($child->ancestors)&&count($child->ancestors)>0){
                    if(in_array( $slug,$child->ancestors))
                    $this->{$slug}->descendents[$child->symSlug] = $childSlug;
                }
            }
        }
        return $this;
    }
}