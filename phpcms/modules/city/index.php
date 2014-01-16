<?php
    defined('IN_PHPCMS') or exit('No permission resources.');
    class index {
        function __construct() {
            
        }
        public function init()
        {
            $myvar = 'hello world';
            echo $myvar;
        }
        
        public function mylist()
        {
            $myvar = 'hello world!this is a example!';
            echo $myvar;
        }
    }
?>