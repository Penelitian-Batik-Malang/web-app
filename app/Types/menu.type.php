<?php

class MenuItem {
    public $label;
    public $url;
    public $icon;
    public array $subItems = [];

    public function __construct($label, $url = null, $icon = null, array $subItems = []) {
        $this->label = $label;
        $this->url = $url;
        $this->icon = $icon;
        $this->subItems = $subItems;
    }
}