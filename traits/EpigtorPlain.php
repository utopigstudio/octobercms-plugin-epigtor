<?php namespace Utopigs\Epigtor\Traits;

trait EpigtorPlain
{
    private function renderPlain($content)
    {
        //convert nl2br
        $content = nl2br($content);
        //replace paragraphs with break lines
        $content = str_replace(array('<p>','</p>'),array('','<br />'), $content);
        //remove all html tags except break lines
        $content = strip_tags($content, '<br>');
        //remove EOL
        $content = preg_replace( "/\r|\n/", "", $content);
        //remove excess <br> or <br /> from the end of the text
        $content = preg_replace('#(( ){0,}<br( {0,})(/{0,1})>){1,}$#i', '', $content);

        if (!$this->isEditor) {
            return $content;
        }

        if (!$content) {
            $content = "[empty]";
        }

        $this->content = $content;
    }

}