<?php new \RapidFrames\System\Sitemap();?>

<pre><code>
<?php
echo <<<HEREDOC
/**
 * Sitemap
 * Generate the entire sitemap tree structure
 * @package rapidframes
 * @author Mwayi Dzanjalimodzi
 * @method printNav
 */
class Sitemap
{
    public function __construct(\$orderby='slug', \$order='asc')
    {
        global \$pagesData;
        \$api = new \API;
        \$slugs = \$api->get('slugs')
                ->orderby(\$orderby)
                ->order(\$order)
                ->format('json')
                ->fetchData();
        \$slugs = json_decode(\$slugs);
        \$this->pages = \$pagesData;
        \$this->printNav(\$slugs);
    }

    /**
     * Print out a side nav
     * @param string \$slugs
     * @param string \$depth
     * @param string \$current
     */
    private function printNav(\$slugs, \$depth = -1, \$current=' ')
    {
        \$subdepth = \$depth + 1;
        if(count(\$slugs)>0){
            printf ('<ul%s>',\$current);
            foreach (\$slugs as \$slug){ 
                \$page = \$this->pages->{\$slug};
                \$page->ancestors = (array)\$page->ancestors;
                if(count(\$page->ancestors)===\$subdepth){
                    printf('<li><a href="%s">%s</a>',\$page->permalink,\$page->title);
                    if(\$page->children>0) 
                        \$this->printNav((array)\$page->children, \$subdepth,\$current);
                    echo '</li>';
                }
            }
            echo '</ul>';
        }
    }
}
HEREDOC
?>
</code>
</pre>