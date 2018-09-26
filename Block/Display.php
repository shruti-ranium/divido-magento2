<?php
namespace Divido\DividoFinancing\Block;
class Display extends \Magento\Framework\View\Element\Template
{
	public function __construct(\Magento\Framework\View\Element\Template\Context $context)
	{
		parent::__construct($context);

        if(isset($_GET['divido-debug'])) $this->display = true;
    }

    public function getVersion(){
        $debug = $this->getComposer();
        if(isset($debug['version'])){
            return $debug['version'];
        }else return 'Unknown';
    }
    
    public function getComposer(){
        $json = @file_get_contents(__DIR__."../../composer.json");
        if($json){
            $composer = json_decode($json);
            foreach($composer as $key=>$value){
                if(is_string($value)){
                    $return[$key] = $value;
                }
            }
        }else $return = [];
		return $return;
    }

    public function getDisplay(){
        return $this->display;
	}
	
	public function sayHello(){
		return "Hello!";
	}

}
